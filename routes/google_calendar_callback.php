<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/redirect.php';
require_once __DIR__ . '/../app/helpers/google_calendar_oauth.php';
require_once __DIR__ . '/../app/config/database.php';

requireAuth();

if (!empty($_GET['error'])) {
    unset($_SESSION['google_calendar_oauth']);
    redirectError('La conexión con Google Calendar fue cancelada.', CALENDAR_PATH);
}

try {
    $state = trim((string) ($_GET['state'] ?? ''));
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($state === '' || $code === '') {
        throw new RuntimeException('Google no entregó los datos de autorización requeridos.');
    }

    $userId = (int) $_SESSION['user_id'];
    $verifier = consumeGoogleCalendarOAuthRequest($userId, $state);
    $tokens = exchangeGoogleCalendarAuthorizationCode($code, $verifier);
    saveGoogleCalendarIntegration($pdo, $userId, $tokens);

    redirect(CALENDAR_PATH, ['success' => 'Google Calendar conectado correctamente']);
} catch (Throwable $exception) {
    error_log('Google Calendar OAuth: fallo en callback | ' . $exception->getMessage());
    redirectError('No se pudo completar la conexión con Google Calendar. Intenta nuevamente.', CALENDAR_PATH);
}
