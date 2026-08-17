<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';
require_once __DIR__ . '/../app/helpers/redirect.php';
require_once __DIR__ . '/../app/helpers/google_calendar_sync.php';
require_once __DIR__ . '/../app/config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectError('Acción no permitida.', CALENDAR_PATH);
}

verifyCsrf();

try {
    $result = syncFinancialEventsToGoogleCalendar($pdo, (int) $_SESSION['user_id']);
    $message = "Sincronización completada: {$result['created']} creados y {$result['updated']} actualizados";
    if ($result['failed'] > 0) {
        $message .= ". {$result['failed']} eventos requieren revisión";
    }

    redirect(CALENDAR_PATH, [$result['failed'] > 0 ? 'error' : 'success' => $message]);
} catch (Throwable $exception) {
    error_log('Google Calendar: fallo en sincronización manual | ' . $exception->getMessage());
    redirectError('No se pudo sincronizar con Google Calendar. Revisa la conexión e intenta nuevamente.', CALENDAR_PATH);
}
