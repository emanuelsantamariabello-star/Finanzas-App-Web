<?php

require_once '../../app/config/app.php';
require_once '../../app/config/database.php';
require_once '../../app/helpers/notifications.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'notifications' => [],
        'count' => 0,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$notifications = getDashboardNotifications($pdo, (int) $_SESSION['user_id']);

echo json_encode([
    'ok' => true,
    'notifications' => $notifications,
    'count' => count($notifications),
], JSON_UNESCAPED_UNICODE);
