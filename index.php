<?php
require_once __DIR__ . '/app/config/app.php';

// Si el usuario ya está logueado, ir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . DASHBOARD_PATH);
    exit;
}

// Si no está logueado, ir al login
header("Location: " . LOGIN_PATH);
exit;
