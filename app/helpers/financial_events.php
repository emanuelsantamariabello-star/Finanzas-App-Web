<?php

const FINANCIAL_EVENT_TYPES = [
    'pago',
    'ingreso_esperado',
    'gasto_programado',
    'cuota',
    'deuda',
    'suscripcion',
    'recordatorio',
    'otro',
];

const FINANCIAL_EVENT_STATUSES = [
    'pendiente',
    'completado',
    'cancelado',
];

const FINANCIAL_EVENT_RECURRENCES = [
    'none',
    'daily',
    'weekly',
    'monthly',
    'yearly',
];

function financialEventTypeLabels(): array
{
    return [
        'pago' => 'Pago',
        'ingreso_esperado' => 'Ingreso esperado',
        'gasto_programado' => 'Gasto programado',
        'cuota' => 'Cuota',
        'deuda' => 'Deuda',
        'suscripcion' => 'Suscripción',
        'recordatorio' => 'Recordatorio',
        'otro' => 'Otro',
    ];
}

function financialEventStatusLabels(): array
{
    return [
        'pendiente' => 'Pendiente',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
        'vencido' => 'Vencido',
    ];
}

function financialEventRecurrenceLabels(): array
{
    return [
        'none' => 'No se repite',
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
    ];
}

function isValidFinancialEventDate(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate && $parsedDate->format('Y-m-d') === $date;
}

function isValidFinancialEventTime(?string $time): bool
{
    if (!$time) {
        return true;
    }

    $parsedTime = DateTime::createFromFormat('H:i', $time);

    return $parsedTime && $parsedTime->format('H:i') === $time;
}

function normalizeFinancialEventAmount($amount): ?float
{
    if ($amount === null || $amount === '') {
        return null;
    }

    if (!is_numeric($amount) || (float) $amount < 0) {
        throw new InvalidArgumentException('Monto inválido.');
    }

    return round((float) $amount, 2);
}

function normalizeFinancialEventReminder($days): ?int
{
    if ($days === null || $days === '') {
        return null;
    }

    if (!is_numeric($days)) {
        return null;
    }

    $days = (int) $days;

    return $days >= 0 ? $days : null;
}

function createFinancialEvent(PDO $pdo, int $userId, array $data): int
{
    $title = trim((string) ($data['title'] ?? ''));
    $eventType = (string) ($data['event_type'] ?? 'otro');
    $amount = normalizeFinancialEventAmount($data['amount'] ?? null);
    $eventDate = (string) ($data['event_date'] ?? '');
    $eventTime = trim((string) ($data['event_time'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $status = (string) ($data['status'] ?? 'pendiente');
    $recurrenceType = (string) ($data['recurrence_type'] ?? 'none');
    $recurrenceEndsAt = trim((string) ($data['recurrence_ends_at'] ?? ''));
    $reminderDaysBefore = normalizeFinancialEventReminder($data['reminder_days_before'] ?? null);
    $recurrenceIsLastDay = !empty($data['recurrence_is_last_day']) ? 1 : 0;

    if ($title === '') {
        throw new InvalidArgumentException('El título del evento es obligatorio.');
    }

    if (!in_array($eventType, FINANCIAL_EVENT_TYPES, true)) {
        throw new InvalidArgumentException('Tipo de evento inválido.');
    }

    if (!isValidFinancialEventDate($eventDate)) {
        throw new InvalidArgumentException('Fecha de evento inválida.');
    }

    if (!isValidFinancialEventTime($eventTime)) {
        throw new InvalidArgumentException('Hora de evento inválida.');
    }

    if (!in_array($status, FINANCIAL_EVENT_STATUSES, true)) {
        throw new InvalidArgumentException('Estado inválido.');
    }

    if (!in_array($recurrenceType, FINANCIAL_EVENT_RECURRENCES, true)) {
        throw new InvalidArgumentException('Recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && !isValidFinancialEventDate($recurrenceEndsAt)) {
        throw new InvalidArgumentException('Fecha final de recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && $recurrenceEndsAt < $eventDate) {
        throw new InvalidArgumentException('La recurrencia no puede terminar antes de la fecha inicial.');
    }

    $recurrenceDayOfMonth = null;
    if ($recurrenceType === 'monthly' && !$recurrenceIsLastDay) {
        $recurrenceDayOfMonth = (int) date('j', strtotime($eventDate));
    }

    $stmt = $pdo->prepare("
        INSERT INTO financial_events (
            user_id,
            title,
            event_type,
            amount,
            event_date,
            event_time,
            description,
            status,
            recurrence_type,
            recurrence_interval,
            recurrence_day_of_month,
            recurrence_is_last_day,
            recurrence_ends_at,
            reminder_days_before
        )
        VALUES (
            :user_id,
            :title,
            :event_type,
            :amount,
            :event_date,
            :event_time,
            :description,
            :status,
            :recurrence_type,
            1,
            :recurrence_day_of_month,
            :recurrence_is_last_day,
            :recurrence_ends_at,
            :reminder_days_before
        )
    ");

    $stmt->execute([
        'user_id' => $userId,
        'title' => $title,
        'event_type' => $eventType,
        'amount' => $amount,
        'event_date' => $eventDate,
        'event_time' => $eventTime !== '' ? $eventTime : null,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'recurrence_type' => $recurrenceType,
        'recurrence_day_of_month' => $recurrenceDayOfMonth,
        'recurrence_is_last_day' => $recurrenceIsLastDay,
        'recurrence_ends_at' => $recurrenceEndsAt !== '' ? $recurrenceEndsAt : null,
        'reminder_days_before' => $reminderDaysBefore,
    ]);

    return (int) $pdo->lastInsertId();
}

function updateFinancialEvent(PDO $pdo, int $userId, int $eventId, array $data): void
{
    validateFinancialEventOwnership($pdo, $eventId, $userId);

    $title = trim((string) ($data['title'] ?? ''));
    $eventType = (string) ($data['event_type'] ?? 'otro');
    $amount = normalizeFinancialEventAmount($data['amount'] ?? null);
    $eventDate = (string) ($data['event_date'] ?? '');
    $eventTime = trim((string) ($data['event_time'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $status = (string) ($data['status'] ?? 'pendiente');
    $recurrenceType = (string) ($data['recurrence_type'] ?? 'none');
    $recurrenceEndsAt = trim((string) ($data['recurrence_ends_at'] ?? ''));
    $reminderDaysBefore = normalizeFinancialEventReminder($data['reminder_days_before'] ?? null);
    $recurrenceIsLastDay = !empty($data['recurrence_is_last_day']) ? 1 : 0;

    if ($title === '') {
        throw new InvalidArgumentException('El título del evento es obligatorio.');
    }

    if (!in_array($eventType, FINANCIAL_EVENT_TYPES, true)) {
        throw new InvalidArgumentException('Tipo de evento inválido.');
    }

    if (!isValidFinancialEventDate($eventDate)) {
        throw new InvalidArgumentException('Fecha de evento inválida.');
    }

    if (!isValidFinancialEventTime($eventTime)) {
        throw new InvalidArgumentException('Hora de evento inválida.');
    }

    if (!in_array($status, FINANCIAL_EVENT_STATUSES, true)) {
        throw new InvalidArgumentException('Estado inválido.');
    }

    if (!in_array($recurrenceType, FINANCIAL_EVENT_RECURRENCES, true)) {
        throw new InvalidArgumentException('Recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && !isValidFinancialEventDate($recurrenceEndsAt)) {
        throw new InvalidArgumentException('Fecha final de recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && $recurrenceEndsAt < $eventDate) {
        throw new InvalidArgumentException('La recurrencia no puede terminar antes de la fecha inicial.');
    }

    $recurrenceDayOfMonth = null;
    if ($recurrenceType === 'monthly' && !$recurrenceIsLastDay) {
        $recurrenceDayOfMonth = (int) date('j', strtotime($eventDate));
    }

    $stmt = $pdo->prepare("
        UPDATE financial_events
        SET title = :title,
            event_type = :event_type,
            amount = :amount,
            event_date = :event_date,
            event_time = :event_time,
            description = :description,
            status = :status,
            recurrence_type = :recurrence_type,
            recurrence_day_of_month = :recurrence_day_of_month,
            recurrence_is_last_day = :recurrence_is_last_day,
            recurrence_ends_at = :recurrence_ends_at,
            reminder_days_before = :reminder_days_before
        WHERE id = :id
          AND user_id = :user_id
    ");

    $stmt->execute([
        'title' => $title,
        'event_type' => $eventType,
        'amount' => $amount,
        'event_date' => $eventDate,
        'event_time' => $eventTime !== '' ? $eventTime : null,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'recurrence_type' => $recurrenceType,
        'recurrence_day_of_month' => $recurrenceDayOfMonth,
        'recurrence_is_last_day' => $recurrenceIsLastDay,
        'recurrence_ends_at' => $recurrenceEndsAt !== '' ? $recurrenceEndsAt : null,
        'reminder_days_before' => $reminderDaysBefore,
        'id' => $eventId,
        'user_id' => $userId,
    ]);
}

function deleteFinancialEvent(PDO $pdo, int $userId, int $eventId): void
{
    validateFinancialEventOwnership($pdo, $eventId, $userId);

    $stmt = $pdo->prepare("
        DELETE FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);
}

function validateFinancialEventOwnership(PDO $pdo, int $eventId, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);

    if (!$stmt->fetch()) {
        throw new RuntimeException('Evento no encontrado o no autorizado.');
    }
}

function getFinancialEvent(PDO $pdo, int $userId, int $eventId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    return $event ?: null;
}

function getFinancialEventsForRange(PDO $pdo, int $userId, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM financial_events
        WHERE user_id = :user_id
          AND event_date <= :end_date
          AND (recurrence_ends_at IS NULL OR recurrence_ends_at >= :start_date)
        ORDER BY event_date ASC, event_time ASC, id ASC
    ");
    $stmt->execute([
        'user_id' => $userId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $events = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $event) {
        $events = array_merge($events, expandFinancialEventOccurrences($event, $startDate, $endDate));
    }

    usort($events, static function (array $a, array $b): int {
        return [$a['occurrence_date'], $a['event_time'] ?? '', $a['id']] <=> [$b['occurrence_date'], $b['event_time'] ?? '', $b['id']];
    });

    return $events;
}

function expandFinancialEventOccurrences(array $event, string $startDate, string $endDate): array
{
    if ($event['status'] === 'cancelado') {
        return [];
    }

    $occurrences = [];
    $current = new DateTimeImmutable($event['event_date']);
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $recurrenceEnd = !empty($event['recurrence_ends_at']) ? new DateTimeImmutable($event['recurrence_ends_at']) : $end;
    $limit = $recurrenceEnd < $end ? $recurrenceEnd : $end;
    $recurrenceType = $event['recurrence_type'] ?? 'none';

    while ($current <= $limit) {
        if ($current >= $start) {
            $occurrences[] = financialEventOccurrenceFromEvent($event, $current);
        }

        if ($recurrenceType === 'none') {
            break;
        }

        $next = nextFinancialEventOccurrenceDate($event, $current);
        if ($next <= $current) {
            break;
        }

        $current = $next;
    }

    return $occurrences;
}

function nextFinancialEventOccurrenceDate(array $event, DateTimeImmutable $current): DateTimeImmutable
{
    $interval = max(1, (int) ($event['recurrence_interval'] ?? 1));

    if ($event['recurrence_type'] === 'daily') {
        return $current->modify("+{$interval} day");
    }

    if ($event['recurrence_type'] === 'weekly') {
        return $current->modify("+{$interval} week");
    }

    if ($event['recurrence_type'] === 'yearly') {
        return $current->modify("+{$interval} year");
    }

    if ($event['recurrence_type'] === 'monthly') {
        $nextMonth = $current->modify("first day of +{$interval} month");

        if (!empty($event['recurrence_is_last_day'])) {
            return $nextMonth->modify('last day of this month');
        }

        $day = (int) ($event['recurrence_day_of_month'] ?: $current->format('j'));
        $day = min($day, (int) $nextMonth->format('t'));

        return $nextMonth->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('m'), $day);
    }

    return $current;
}

function financialEventOccurrenceFromEvent(array $event, DateTimeImmutable $date): array
{
    $occurrenceDate = $date->format('Y-m-d');
    $status = $event['status'];

    if ($status === 'pendiente' && $occurrenceDate < date('Y-m-d')) {
        $status = 'vencido';
    }

    return [
        'id' => (int) $event['id'],
        'title' => $event['title'],
        'event_type' => $event['event_type'],
        'amount' => $event['amount'] !== null ? (float) $event['amount'] : null,
        'occurrence_date' => $occurrenceDate,
        'event_time' => $event['event_time'],
        'description' => $event['description'],
        'status' => $status,
        'recurrence_type' => $event['recurrence_type'],
        'reminder_days_before' => $event['reminder_days_before'] !== null ? (int) $event['reminder_days_before'] : null,
        'is_recurring' => $event['recurrence_type'] !== 'none',
    ];
}

function getUpcomingFinancialEventNotifications(PDO $pdo, int $userId, int $daysAhead = 7): array
{
    $today = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
    $events = getFinancialEventsForRange($pdo, $userId, $today, $endDate);
    $notifications = [];

    foreach ($events as $event) {
        if (!in_array($event['status'], ['pendiente', 'vencido'], true)) {
            continue;
        }

        $daysUntil = (int) (new DateTimeImmutable($today))->diff(new DateTimeImmutable($event['occurrence_date']))->format('%r%a');
        $reminderDaysBefore = $event['reminder_days_before'];

        if ($daysUntil > 0 && $reminderDaysBefore === null && $daysUntil > 1) {
            continue;
        }

        if ($daysUntil > 0 && $reminderDaysBefore !== null && $daysUntil > $reminderDaysBefore) {
            continue;
        }

        if ($daysUntil < 0) {
            $message = 'Tienes un evento financiero vencido pendiente por revisar.';
        } elseif ($daysUntil === 0) {
            $message = 'Tienes un evento financiero programado para hoy.';
        } elseif ($daysUntil === 1) {
            $message = 'Tienes un evento financiero programado para mañana.';
        } else {
            $message = "Tienes un evento financiero programado en {$daysUntil} días.";
        }

        $notifications[] = [
            'id' => 'financial-event-' . $event['id'] . '-' . $event['occurrence_date'],
            'title' => $event['title'],
            'message' => $message,
            'type' => $event['status'] === 'vencido' ? 'warning' : 'info',
            'source' => 'financial_event',
            'date' => $event['occurrence_date'],
            'days_until' => $daysUntil,
        ];
    }

    return $notifications;
}
