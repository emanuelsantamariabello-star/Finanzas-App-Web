<?php

require_once __DIR__ . '/google_calendar_oauth.php';
require_once __DIR__ . '/financial_events.php';

function syncFinancialEventsToGoogleCalendar(PDO $pdo, int $userId): array
{
    $integration = googleCalendarIntegrationByUser($pdo, $userId);
    if (!$integration || $integration['status'] !== 'conectada') {
        throw new RuntimeException('Google Calendar no está conectado.');
    }

    $events = getFinancialEventsForGoogleSync($pdo, $userId);
    $result = ['created' => 0, 'updated' => 0, 'failed' => 0, 'total' => count($events)];

    foreach ($events as $event) {
        try {
            $mapping = googleCalendarEventMapping($pdo, (int) $integration['id'], (int) $event['id']);
            $payload = buildGoogleCalendarEventPayload($event);
            $providerEventId = trim((string) ($mapping['provider_event_id'] ?? ''));
            $response = null;

            if ($providerEventId !== '') {
                [$status, $response] = googleCalendarApiRequest(
                    $pdo,
                    $userId,
                    'PUT',
                    '/calendars/primary/events/' . rawurlencode($providerEventId) . '?sendUpdates=none',
                    $payload
                );

                if ($status === 404) {
                    $providerEventId = '';
                } elseif ($status < 200 || $status >= 300) {
                    throw new RuntimeException(googleCalendarApiErrorMessage($response));
                } else {
                    $result['updated']++;
                }
            }

            if ($providerEventId === '') {
                [$status, $response] = googleCalendarApiRequest(
                    $pdo,
                    $userId,
                    'POST',
                    '/calendars/primary/events?sendUpdates=none',
                    $payload
                );

                if ($status < 200 || $status >= 300) {
                    throw new RuntimeException(googleCalendarApiErrorMessage($response));
                }

                $result['created']++;
            }

            if (!is_array($response) || empty($response['id'])) {
                throw new RuntimeException('Google no devolvió el identificador del evento.');
            }

            saveGoogleCalendarEventMapping(
                $pdo,
                (int) $integration['id'],
                (int) $event['id'],
                (string) $response['id'],
                (string) ($response['etag'] ?? '')
            );
        } catch (Throwable $exception) {
            $result['failed']++;
            saveGoogleCalendarEventSyncError(
                $pdo,
                (int) $integration['id'],
                (int) $event['id'],
                $exception->getMessage()
            );
        }
    }

    return $result;
}

function getFinancialEventsForGoogleSync(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM financial_events\n        WHERE user_id = :user_id\n          AND status <> 'cancelado'\n        ORDER BY event_date ASC, event_time ASC, id ASC\n    ");
    $stmt->execute(['user_id' => $userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $monthlyIds = array_column(array_filter(
        $events,
        static fn (array $event): bool => $event['recurrence_type'] === 'monthly'
    ), 'id');
    $rulesByEvent = getFinancialEventMonthlyRulesForIds($pdo, $monthlyIds);

    foreach ($events as &$event) {
        $eventId = (int) $event['id'];
        $event = applyFinancialEventMonthlyRules($event, $rulesByEvent[$eventId] ?? []);
    }
    unset($event);

    return $events;
}

function buildGoogleCalendarEventPayload(array $event): array
{
    $timezone = new DateTimeZone(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'America/Bogota');
    $hasTime = !empty($event['event_time']);
    $startDate = (string) $event['event_date'];
    $payload = [
        'summary' => (string) $event['title'],
        'description' => googleCalendarEventDescription($event),
        'visibility' => 'private',
        'transparency' => 'transparent',
        'extendedProperties' => [
            'private' => [
                'finanzasAppEventId' => (string) $event['id'],
                'finanzasAppSource' => 'finanzas_app_web',
            ],
        ],
    ];

    if ($hasTime) {
        $start = new DateTimeImmutable($startDate . ' ' . $event['event_time'], $timezone);
        $end = $start->modify('+1 hour');
        $payload['start'] = ['dateTime' => $start->format(DateTimeInterface::RFC3339), 'timeZone' => $timezone->getName()];
        $payload['end'] = ['dateTime' => $end->format(DateTimeInterface::RFC3339), 'timeZone' => $timezone->getName()];
    } else {
        $start = new DateTimeImmutable($startDate, $timezone);
        $payload['start'] = ['date' => $start->format('Y-m-d')];
        $payload['end'] = ['date' => $start->modify('+1 day')->format('Y-m-d')];
    }

    $recurrenceRule = googleCalendarRecurrenceRule($event, $hasTime, $timezone);
    if ($recurrenceRule !== null) {
        $payload['recurrence'] = [$recurrenceRule];
    }

    if ($event['reminder_days_before'] !== null) {
        $minutes = min(40320, max(0, (int) $event['reminder_days_before'] * 1440));
        $payload['reminders'] = [
            'useDefault' => false,
            'overrides' => [['method' => 'popup', 'minutes' => $minutes]],
        ];
    } else {
        $payload['reminders'] = ['useDefault' => true];
    }

    return $payload;
}

function googleCalendarRecurrenceRule(array $event, bool $hasTime, DateTimeZone $timezone): ?string
{
    $type = (string) ($event['recurrence_type'] ?? 'none');
    if ($type === 'none') {
        return null;
    }

    $frequencies = ['daily' => 'DAILY', 'weekly' => 'WEEKLY', 'monthly' => 'MONTHLY', 'yearly' => 'YEARLY'];
    if (!isset($frequencies[$type])) {
        return null;
    }

    $parts = ['FREQ=' . $frequencies[$type], 'INTERVAL=' . max(1, (int) ($event['recurrence_interval'] ?? 1))];

    if ($type === 'monthly') {
        $days = $event['monthly_rule_days'] ?? [];
        if ($days === []) {
            $days[] = !empty($event['recurrence_is_last_day'])
                ? 0
                : (int) ($event['recurrence_day_of_month'] ?: date('j', strtotime($event['event_date'])));
        }
        $monthDays = array_map(static fn (int $day): int => $day === 0 ? -1 : $day, array_map('intval', $days));
        $parts[] = 'BYMONTHDAY=' . implode(',', array_values(array_unique($monthDays)));
    }

    if (!empty($event['recurrence_ends_at'])) {
        if ($hasTime) {
            $until = new DateTimeImmutable($event['recurrence_ends_at'] . ' 23:59:59', $timezone);
            $parts[] = 'UNTIL=' . $until->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        } else {
            $parts[] = 'UNTIL=' . date('Ymd', strtotime($event['recurrence_ends_at']));
        }
    }

    return 'RRULE:' . implode(';', $parts);
}

function googleCalendarEventDescription(array $event): string
{
    $typeLabels = financialEventTypeLabels();
    $statusLabels = financialEventStatusLabels();
    $lines = [
        'Evento sincronizado desde Finanzas App.',
        'Tipo: ' . ($typeLabels[$event['event_type']] ?? 'Evento'),
        'Estado: ' . ($statusLabels[$event['status']] ?? $event['status']),
    ];

    if ($event['amount'] !== null) {
        $lines[] = 'Monto: $' . number_format((float) $event['amount'], 2, ',', '.');
    }

    if (trim((string) ($event['description'] ?? '')) !== '') {
        $lines[] = '';
        $lines[] = trim((string) $event['description']);
    }

    return implode("\n", $lines);
}

function googleCalendarApiRequest(PDO $pdo, int $userId, string $method, string $path, ?array $payload = null): array
{
    $accessToken = googleCalendarAccessToken($pdo, $userId);
    [$status, $response] = googleCalendarJsonRequest($method, 'https://www.googleapis.com/calendar/v3' . $path, $accessToken, $payload);

    if ($status === 401) {
        $accessToken = googleCalendarAccessToken($pdo, $userId, true);
        [$status, $response] = googleCalendarJsonRequest($method, 'https://www.googleapis.com/calendar/v3' . $path, $accessToken, $payload);
    }

    return [$status, $response];
}

function googleCalendarAccessToken(PDO $pdo, int $userId, bool $forceRefresh = false): string
{
    $integration = googleCalendarIntegrationByUser($pdo, $userId);
    if (!$integration || empty($integration['refresh_token_encrypted'])) {
        throw new RuntimeException('La conexión con Google Calendar debe renovarse.');
    }

    $expiresAt = !empty($integration['token_expires_at']) ? strtotime($integration['token_expires_at']) : 0;
    if (!$forceRefresh && !empty($integration['access_token_encrypted']) && $expiresAt > time() + 60) {
        return decryptGoogleCalendarToken((string) $integration['access_token_encrypted']);
    }

    $config = googleCalendarOAuthConfig();
    [$status, $body] = googleCalendarPostForm('https://oauth2.googleapis.com/token', [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => decryptGoogleCalendarToken((string) $integration['refresh_token_encrypted']),
        'grant_type' => 'refresh_token',
    ]);
    $tokens = json_decode($body, true);

    if ($status !== 200 || !is_array($tokens) || empty($tokens['access_token'])) {
        $stmt = $pdo->prepare("UPDATE external_integrations SET status = 'error' WHERE id = :id");
        $stmt->execute(['id' => $integration['id']]);
        throw new RuntimeException('Google rechazó la renovación de la autorización. Conecta el calendario nuevamente.');
    }

    $expiresAt = date('Y-m-d H:i:s', time() + max(0, (int) ($tokens['expires_in'] ?? 0)));
    $stmt = $pdo->prepare("\n        UPDATE external_integrations\n        SET access_token_encrypted = :access_token,\n            token_expires_at = :token_expires_at,\n            status = 'conectada'\n        WHERE id = :id\n    ");
    $stmt->execute([
        'access_token' => encryptGoogleCalendarToken((string) $tokens['access_token']),
        'token_expires_at' => $expiresAt,
        'id' => $integration['id'],
    ]);

    return (string) $tokens['access_token'];
}

function googleCalendarJsonRequest(string $method, string $url, string $accessToken, ?array $payload): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL es necesaria para sincronizar Google Calendar.');
    }

    $curl = curl_init($url);
    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $accessToken];
    $options = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if ($payload !== null) {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = $encoded;
    }

    $options[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($curl, $options);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('No se pudo contactar Google Calendar: ' . $error);
    }

    $response = json_decode((string) $body, true);

    return [$status, is_array($response) ? $response : []];
}

function googleCalendarEventMapping(PDO $pdo, int $integrationId, int $eventId): ?array
{
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM calendar_event_sync\n        WHERE integration_id = :integration_id\n          AND event_id = :event_id\n        LIMIT 1\n    ");
    $stmt->execute(['integration_id' => $integrationId, 'event_id' => $eventId]);
    $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

    return $mapping ?: null;
}

function saveGoogleCalendarEventMapping(PDO $pdo, int $integrationId, int $eventId, string $providerEventId, string $etag): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO calendar_event_sync (\n            integration_id, event_id, provider_calendar_id, provider_event_id,\n            provider_etag, sync_status, last_synced_at, last_error\n        ) VALUES (\n            :integration_id, :event_id, 'primary', :provider_event_id,\n            :provider_etag, 'sincronizado', :last_synced_at, NULL\n        )\n        ON DUPLICATE KEY UPDATE\n            provider_event_id = VALUES(provider_event_id),\n            provider_etag = VALUES(provider_etag),\n            sync_status = 'sincronizado',\n            last_synced_at = VALUES(last_synced_at),\n            last_error = NULL\n    ");
    $stmt->execute([
        'integration_id' => $integrationId,
        'event_id' => $eventId,
        'provider_event_id' => $providerEventId,
        'provider_etag' => $etag !== '' ? $etag : null,
        'last_synced_at' => date('Y-m-d H:i:s'),
    ]);
}

function saveGoogleCalendarEventSyncError(PDO $pdo, int $integrationId, int $eventId, string $message): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO calendar_event_sync (integration_id, event_id, sync_status, last_error)\n        VALUES (:integration_id, :event_id, 'error', :last_error)\n        ON DUPLICATE KEY UPDATE\n            sync_status = 'error',\n            last_error = VALUES(last_error)\n    ");
    $stmt->execute([
        'integration_id' => $integrationId,
        'event_id' => $eventId,
        'last_error' => mb_substr($message, 0, 1000),
    ]);
}

function googleCalendarSyncSummary(PDO $pdo, int $integrationId): array
{
    $stmt = $pdo->prepare("\n        SELECT\n            SUM(sync_status = 'sincronizado') AS synced_count,\n            SUM(sync_status = 'error') AS error_count,\n            MAX(last_synced_at) AS last_synced_at\n        FROM calendar_event_sync\n        WHERE integration_id = :integration_id\n    ");
    $stmt->execute(['integration_id' => $integrationId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'synced_count' => (int) ($summary['synced_count'] ?? 0),
        'error_count' => (int) ($summary['error_count'] ?? 0),
        'last_synced_at' => $summary['last_synced_at'] ?? null,
    ];
}

function googleCalendarApiErrorMessage(array $response): string
{
    $message = trim((string) ($response['error']['message'] ?? ''));

    return $message !== '' ? 'Google Calendar: ' . $message : 'Google Calendar rechazó la sincronización del evento.';
}
