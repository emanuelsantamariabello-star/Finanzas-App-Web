<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/redirect.php';
require_once __DIR__ . '/../app/helpers/google_calendar_oauth.php';

requireAuth();

try {
    redirect(googleCalendarAuthorizationUrl((int) $_SESSION['user_id']), [], 302);
} catch (Throwable $exception) {
    error_log('Google Calendar OAuth: no se pudo iniciar la conexión | ' . $exception->getMessage());
    redirectError('No se pudo iniciar la conexión con Google Calendar.', CALENDAR_PATH);
}
