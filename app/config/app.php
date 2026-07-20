<?php
// ===============================
// CONFIGURACIÓN DE SESIÓN
// ===============================

// Solo configurar si la sesión NO está activa
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

   // Nombre de cookie específico para mayor claridad
   session_name('finanzas_session');

   session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,      // true SOLO en HTTPS
        'httponly' => true,           // JS no puede leer cookies
        'samesite' => 'Lax',          // seguro para formularios
    ]);

    session_start();
}

/* ======================================================
   CONFIGURACIÓN GLOBAL DE LA APP
   ====================================================== */

// Nombre de la aplicación
define('APP_NAME', 'Finanzas App');

// Helpers globales
require_once __DIR__ . '/../helpers/escape.php';

// Variables de entorno opcionales usadas por la configuracion global.
$env_file = __DIR__ . '/../../.env.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

/* ======================================================
   ENTORNO
   ====================================================== */
/*
| true  = entorno LOCAL (desarrollo)
| false = entorno PRODUCCIÓN
*/
define('APP_DEBUG', false); // TEMPORAL para debug

// ===============================
// HEADERS DE SEGURIDAD
// ===============================

// Evitar que el sitio sea cargado en iframes (clickjacking)
header('X-Frame-Options: SAMEORIGIN');

// Evitar sniffing de tipos MIME
header('X-Content-Type-Options: nosniff');

// Protección básica XSS (navegadores antiguos)
header('X-XSS-Protection: 1; mode=block');

// Política de referencias
header('Referrer-Policy: strict-origin-when-cross-origin');

// Política de permisos (desactivar cosas no usadas)
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

/* ======================================================
   MANEJO DE ERRORES (LOCAL vs PRODUCCIÓN)
   ====================================================== */

if (APP_DEBUG) {
    // 🧪 DESARROLLO: Mostrar todos los errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // 🔒 PRODUCCIÓN: Ocultar errores y loguear
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    
    // Loguear errores en archivo en lugar de mostrar
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/errors.log');
    
    // Crear directorio logs si no existe
    $log_dir = __DIR__ . '/../../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
}

/* ======================================================
   RUTAS BASE
   ====================================================== */

$basePath = $_ENV['APP_BASE_PATH'] ?? '';
$basePath = '/' . trim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}

define('BASE_PATH', $basePath);

/* ======================================================
   RUTAS PRINCIPALES
   ====================================================== */

define('DASHBOARD_PATH', BASE_PATH . '/views/dashboard/index.php');
define('LOGIN_PATH',     BASE_PATH . '/views/auth/login.php');
define('PROFILE_PATH',   BASE_PATH . '/views/profile/index.php');
define('PROFILE_EDIT_PATH',   BASE_PATH . '/views/profile/edit.php');
define('PROFILE_PASSWORD_PATH', BASE_PATH . '/views/profile/password.php');
define('EXPENSES_PATH',  BASE_PATH . '/views/expenses/index.php');
define('EXPENSE_CREATE_PATH', BASE_PATH . '/views/expenses/create.php');
define('EXPENSE_EDIT_PATH',   BASE_PATH . '/views/expenses/edit.php');
define('EXPENSES_INDEX_PATH', BASE_PATH . '/views/expenses/index.php');
define('INCOME_CREATE_PATH', BASE_PATH . '/views/incomes/create.php');
define('INCOME_EDIT_PATH', BASE_PATH . '/views/incomes/edit.php');
define('REPORTS_PATH', BASE_PATH . '/views/reports/index.php');
define('REPORT_GRAPHS_PATH', BASE_PATH . '/views/reports/graficas.php');
define('ADMIN_NOTIFICATIONS_PATH', BASE_PATH . '/views/admin/notifications.php');
define('REGISTER_PATH', BASE_PATH . '/views/auth/register.php');
/* ======================================================
   RUTAS DE FORMULARIOS Y RECURSOS
   ====================================================== */

define('WEB_ROUTE', BASE_PATH . '/web.php');
define('LOGOUT_ROUTE',   BASE_PATH . '/routes/logout.php');

define('ASSETS_URL',     BASE_PATH . '/public');
define('JS_URL',         BASE_PATH . '/public/js');
define('CSS_URL',        BASE_PATH . '/public/css');
define('REPORTS_URL',    BASE_PATH . '/public/reports');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
