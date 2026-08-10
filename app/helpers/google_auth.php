<?php

function googleClientId(): string
{
    return trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? ''));
}

function googleAuthIsConfigured(): bool
{
    return googleClientId() !== '';
}

function verifyGoogleIdToken(string $credential): array
{
    $credential = trim($credential);

    if (!googleAuthIsConfigured()) {
        throw new RuntimeException('Google no está configurado correctamente.');
    }

    $parts = explode('.', $credential);
    if (count($parts) !== 3) {
        throw new RuntimeException('Credencial de Google inválida.');
    }

    $headerJson = base64UrlDecode($parts[0]);
    $payloadJson = base64UrlDecode($parts[1]);
    $signature = base64UrlDecode($parts[2]);

    if ($headerJson === false || $payloadJson === false || $signature === false) {
        throw new RuntimeException('Credencial de Google inválida.');
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    if (!is_array($header) || !is_array($payload) || $signature === false) {
        throw new RuntimeException('Credencial de Google inválida.');
    }

    if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
        throw new RuntimeException('Firma de Google inválida.');
    }

    $publicKey = googlePublicKeyForKid((string) $header['kid']);
    $signedData = $parts[0] . '.' . $parts[1];
    $verified = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);

    if ($verified !== 1) {
        throw new RuntimeException('No se pudo verificar la firma de Google.');
    }

    $issuer = $payload['iss'] ?? '';
    if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
        throw new RuntimeException('Emisor de Google inválido.');
    }

    if (($payload['aud'] ?? '') !== googleClientId()) {
        throw new RuntimeException('Client ID de Google inválido.');
    }

    if (empty($payload['exp']) || (int) $payload['exp'] < time()) {
        throw new RuntimeException('La credencial de Google expiró.');
    }

    if (empty($payload['sub']) || empty($payload['email'])) {
        throw new RuntimeException('Google no entregó los datos necesarios.');
    }

    if (empty($payload['email_verified']) || $payload['email_verified'] !== true) {
        throw new RuntimeException('El correo de Google no está verificado.');
    }

    return $payload;
}

function generateGoogleUsername(PDO $pdo, array $payload): string
{
    $base = trim((string) ($payload['given_name'] ?? ''));

    if ($base === '') {
        $base = trim((string) ($payload['name'] ?? ''));
    }

    if ($base === '' && !empty($payload['email'])) {
        $base = explode('@', (string) $payload['email'])[0];
    }

    $base = preg_replace('/[^\p{L}\p{N}_\-\s]/u', '', $base);
    $base = preg_replace('/\s+/', ' ', trim((string) $base));

    if ($base === '') {
        $base = 'Usuario';
    }

    $base = mb_substr($base, 0, 90);
    $candidate = $base;
    $counter = 2;

    while (usernameExists($pdo, $candidate)) {
        $suffix = (string) $counter;
        $candidate = mb_substr($base, 0, 100 - strlen($suffix)) . $suffix;
        $counter++;
    }

    return $candidate;
}

function usernameExists(PDO $pdo, string $username): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);

    return (bool) $stmt->fetch();
}

function googleIdentityBySub(PDO $pdo, string $sub): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email
        FROM auth_identities ai
        INNER JOIN users u ON u.id = ai.user_id
        WHERE ai.provider = 'google'
          AND ai.provider_user_id = :provider_user_id
        LIMIT 1
    ");
    $stmt->execute(['provider_user_id' => $sub]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function emailExists(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    return (bool) $stmt->fetch();
}

function startUserSession(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
}

function base64UrlDecode(string $value)
{
    $remainder = strlen($value) % 4;
    if ($remainder) {
        $value .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function googlePublicKeyForKid(string $kid): string
{
    $certs = fetchGoogleCertificates();

    if (empty($certs['keys']) || !is_array($certs['keys'])) {
        throw new RuntimeException('No se pudieron leer los certificados de Google.');
    }

    foreach ($certs['keys'] as $key) {
        if (($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
            return rsaJwkToPem($key);
        }
    }

    throw new RuntimeException('Certificado de Google no encontrado.');
}

function fetchGoogleCertificates(): array
{
    $url = 'https://www.googleapis.com/oauth2/v3/certs';
    $json = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $json = curl_exec($ch);
        curl_close($ch);
    }

    if (!$json) {
        $json = @file_get_contents($url);
    }

    $certs = json_decode((string) $json, true);
    if (!is_array($certs)) {
        throw new RuntimeException('No se pudo conectar con Google para verificar la credencial.');
    }

    return $certs;
}

function rsaJwkToPem(array $key): string
{
    if (empty($key['n']) || empty($key['e'])) {
        throw new RuntimeException('Certificado de Google inválido.');
    }

    $modulus = base64UrlDecode($key['n']);
    $exponent = base64UrlDecode($key['e']);

    if ($modulus === false || $exponent === false) {
        throw new RuntimeException('Certificado de Google inválido.');
    }

    $components = [
        encodeAsn1Integer($modulus),
        encodeAsn1Integer($exponent),
    ];

    $rsaPublicKey = encodeAsn1Sequence(implode('', $components));
    $publicKeyInfo = encodeAsn1Sequence(
        encodeAsn1Sequence(
            encodeAsn1ObjectIdentifier('1.2.840.113549.1.1.1') .
            encodeAsn1Null()
        ) .
        encodeAsn1BitString($rsaPublicKey)
    );

    return "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split(base64_encode($publicKeyInfo), 64, "\n") .
        "-----END PUBLIC KEY-----\n";
}

function encodeAsn1Length(int $length): string
{
    if ($length < 128) {
        return chr($length);
    }

    $encoded = '';
    while ($length > 0) {
        $encoded = chr($length & 0xff) . $encoded;
        $length >>= 8;
    }

    return chr(0x80 | strlen($encoded)) . $encoded;
}

function encodeAsn1Sequence(string $value): string
{
    return "\x30" . encodeAsn1Length(strlen($value)) . $value;
}

function encodeAsn1Integer(string $value): string
{
    $value = ltrim($value, "\x00");
    if ($value === '') {
        $value = "\x00";
    }

    if ((ord($value[0]) & 0x80) !== 0) {
        $value = "\x00" . $value;
    }

    return "\x02" . encodeAsn1Length(strlen($value)) . $value;
}

function encodeAsn1ObjectIdentifier(string $oid): string
{
    $parts = array_map('intval', explode('.', $oid));
    $encoded = chr($parts[0] * 40 + $parts[1]);

    for ($i = 2; $i < count($parts); $i++) {
        $value = $parts[$i];
        $bytes = chr($value & 0x7f);
        while ($value >>= 7) {
            $bytes = chr(($value & 0x7f) | 0x80) . $bytes;
        }
        $encoded .= $bytes;
    }

    return "\x06" . encodeAsn1Length(strlen($encoded)) . $encoded;
}

function encodeAsn1Null(): string
{
    return "\x05\x00";
}

function encodeAsn1BitString(string $value): string
{
    $value = "\x00" . $value;

    return "\x03" . encodeAsn1Length(strlen($value)) . $value;
}
