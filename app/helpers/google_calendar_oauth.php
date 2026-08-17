<?php

function googleCalendarOAuthConfig(): array
{
    return [
        'client_id' => trim((string) ($_ENV['GOOGLE_CALENDAR_CLIENT_ID'] ?? '')),
        'client_secret' => trim((string) ($_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] ?? '')),
        'redirect_uri' => trim((string) ($_ENV['GOOGLE_CALENDAR_REDIRECT_URI'] ?? '')),
        'scope' => trim((string) ($_ENV['GOOGLE_CALENDAR_SCOPE'] ?? 'https://www.googleapis.com/auth/calendar.events')),
    ];
}

function googleCalendarOAuthIsConfigured(): bool
{
    $config = googleCalendarOAuthConfig();

    return $config['client_id'] !== ''
        && $config['client_secret'] !== ''
        && $config['redirect_uri'] !== ''
        && $config['scope'] !== ''
        && googleCalendarTokenEncryptionKeyIsConfigured();
}

function googleCalendarAuthorizationUrl(int $userId): string
{
    if (!googleCalendarOAuthIsConfigured()) {
        throw new RuntimeException('Google Calendar no está configurado correctamente.');
    }

    $state = bin2hex(random_bytes(32));
    $verifier = googleCalendarBase64UrlEncode(random_bytes(64));
    $challenge = googleCalendarBase64UrlEncode(hash('sha256', $verifier, true));

    $_SESSION['google_calendar_oauth'] = [
        'user_id' => $userId,
        'state' => $state,
        'verifier' => $verifier,
        'created_at' => time(),
    ];

    $config = googleCalendarOAuthConfig();
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => $config['scope'],
        'access_type' => 'offline',
        'include_granted_scopes' => 'true',
        'prompt' => 'consent',
        'state' => $state,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function consumeGoogleCalendarOAuthRequest(int $userId, string $state): string
{
    $request = $_SESSION['google_calendar_oauth'] ?? null;
    unset($_SESSION['google_calendar_oauth']);

    if (!is_array($request)
        || (int) ($request['user_id'] ?? 0) !== $userId
        || empty($request['state'])
        || !hash_equals((string) $request['state'], $state)
        || empty($request['verifier'])
        || time() - (int) ($request['created_at'] ?? 0) > 600
    ) {
        throw new RuntimeException('La solicitud de conexión con Google expiró o no es válida.');
    }

    return (string) $request['verifier'];
}

function exchangeGoogleCalendarAuthorizationCode(string $code, string $verifier): array
{
    $config = googleCalendarOAuthConfig();
    [$status, $body] = googleCalendarPostForm('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
        'code_verifier' => $verifier,
    ]);

    $response = json_decode($body, true);
    if ($status !== 200 || !is_array($response) || empty($response['access_token'])) {
        throw new RuntimeException('Google no pudo completar la conexión con Calendar.');
    }

    return $response;
}

function googleCalendarIntegrationByUser(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM external_integrations\n        WHERE user_id = :user_id\n          AND provider = 'google_calendar'\n        LIMIT 1\n    ");
    $stmt->execute(['user_id' => $userId]);
    $integration = $stmt->fetch();

    return $integration ?: null;
}

function saveGoogleCalendarIntegration(PDO $pdo, int $userId, array $tokens): void
{
    $current = googleCalendarIntegrationByUser($pdo, $userId);
    $refreshToken = trim((string) ($tokens['refresh_token'] ?? ''));

    if ($refreshToken === '' && empty($current['refresh_token_encrypted'])) {
        throw new RuntimeException('Google no entregó el permiso de acceso permanente. Intenta conectar nuevamente.');
    }

    $refreshTokenEncrypted = $refreshToken !== ''
        ? encryptGoogleCalendarToken($refreshToken)
        : $current['refresh_token_encrypted'];
    $expiresAt = date('Y-m-d H:i:s', time() + max(0, (int) ($tokens['expires_in'] ?? 0)));
    $scopes = trim((string) ($tokens['scope'] ?? googleCalendarOAuthConfig()['scope']));

    $stmt = $pdo->prepare("\n        INSERT INTO external_integrations (\n            user_id, provider, access_token_encrypted, refresh_token_encrypted,\n            token_expires_at, scopes, status\n        ) VALUES (\n            :user_id, 'google_calendar', :access_token, :refresh_token,\n            :token_expires_at, :scopes, 'conectada'\n        )\n        ON DUPLICATE KEY UPDATE\n            access_token_encrypted = VALUES(access_token_encrypted),\n            refresh_token_encrypted = VALUES(refresh_token_encrypted),\n            token_expires_at = VALUES(token_expires_at),\n            scopes = VALUES(scopes),\n            status = 'conectada',\n            provider_account_id = NULL,\n            provider_account_email = NULL\n    ");
    $stmt->execute([
        'user_id' => $userId,
        'access_token' => encryptGoogleCalendarToken((string) $tokens['access_token']),
        'refresh_token' => $refreshTokenEncrypted,
        'token_expires_at' => $expiresAt,
        'scopes' => $scopes,
    ]);
}

function disconnectGoogleCalendarIntegration(PDO $pdo, int $userId): void
{
    $integration = googleCalendarIntegrationByUser($pdo, $userId);
    if (!$integration) {
        return;
    }

    $encryptedToken = $integration['refresh_token_encrypted'] ?: $integration['access_token_encrypted'];
    if ($encryptedToken) {
        $token = decryptGoogleCalendarToken((string) $encryptedToken);
        [$status] = googleCalendarPostForm('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        if (!in_array($status, [200, 400], true)) {
            throw new RuntimeException('No se pudo revocar la conexión con Google. Intenta nuevamente.');
        }
    }

    $stmt = $pdo->prepare("\n        DELETE FROM external_integrations\n        WHERE user_id = :user_id\n          AND provider = 'google_calendar'\n    ");
    $stmt->execute(['user_id' => $userId]);
}

function googleCalendarPostForm(string $url, array $fields): array
{
    $body = http_build_query($fields);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException('No se pudo conectar con Google Calendar: ' . $error);
        }

        return [$status, (string) $response];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        throw new RuntimeException('No se pudo conectar con Google Calendar.');
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return [$status, (string) $response];
}

function googleCalendarBase64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function googleCalendarTokenEncryptionKeyIsConfigured(): bool
{
    try {
        googleCalendarTokenEncryptionKey();
        return true;
    } catch (RuntimeException $exception) {
        return false;
    }
}

function encryptGoogleCalendarToken(string $token): string
{
    if ($token === '') {
        throw new InvalidArgumentException('El token no puede estar vacío.');
    }

    $cipher = 'aes-256-gcm';
    $iv = random_bytes(openssl_cipher_iv_length($cipher));
    $tag = '';
    $encrypted = openssl_encrypt(
        $token,
        $cipher,
        googleCalendarTokenEncryptionKey(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($encrypted === false) {
        throw new RuntimeException('No se pudo cifrar el token de Google Calendar.');
    }

    return base64_encode(json_encode([
        'version' => 1,
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'value' => base64_encode($encrypted),
    ], JSON_THROW_ON_ERROR));
}

function decryptGoogleCalendarToken(string $payload): string
{
    $decodedPayload = base64_decode($payload, true);
    if ($decodedPayload === false) {
        throw new RuntimeException('El token cifrado no tiene un formato válido.');
    }

    try {
        $data = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException('El token cifrado no tiene un formato válido.', 0, $exception);
    }

    if (!is_array($data) || ($data['version'] ?? null) !== 1) {
        throw new RuntimeException('La versión del token cifrado no es compatible.');
    }

    $iv = base64_decode((string) ($data['iv'] ?? ''), true);
    $tag = base64_decode((string) ($data['tag'] ?? ''), true);
    $encrypted = base64_decode((string) ($data['value'] ?? ''), true);

    if ($iv === false || $tag === false || $encrypted === false) {
        throw new RuntimeException('El token cifrado está incompleto.');
    }

    $token = openssl_decrypt(
        $encrypted,
        'aes-256-gcm',
        googleCalendarTokenEncryptionKey(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($token === false) {
        throw new RuntimeException('No se pudo descifrar el token de Google Calendar.');
    }

    return $token;
}

function googleCalendarTokenEncryptionKey(): string
{
    $encodedKey = trim((string) ($_ENV['OAUTH_TOKEN_ENCRYPTION_KEY'] ?? ''));
    $key = base64_decode($encodedKey, true);

    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('OAUTH_TOKEN_ENCRYPTION_KEY debe ser una clave de 32 bytes codificada en Base64.');
    }

    return $key;
}
