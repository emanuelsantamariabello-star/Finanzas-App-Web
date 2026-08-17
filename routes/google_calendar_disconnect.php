<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';
require_once __DIR__ . '/../app/helpers/redirect.php';
require_once __DIR__ . '/../app/helpers/google_calendar_oauth.php';
require_once __DIR__ . '/../app/config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectError('Acción no permitida.', CALENDAR_PATH);
}

verifyCsrf();

try {
    disconnectGoogleCalendarIntegration($pdo, (int) $_SESSION['user_id']);
    redirect(CALENDAR_PATH, ['success' => 'Google Calendar desconectado correctamente']);
} catch (Throwable $exception) {
    error_log('Google Calendar OAuth: no se pudo desconectar | ' . $exception->getMessage());
    redirectError('No se pudo desconectar Google Calendar. Intenta nuevamente.', CALENDAR_PATH);
}
