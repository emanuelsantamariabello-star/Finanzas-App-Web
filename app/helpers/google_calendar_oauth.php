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
