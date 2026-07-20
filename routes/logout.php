<?php
require_once __DIR__ . '/../app/config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("Location: /finanzas_app/views/auth/login.php?error=Método no permitido");
	exit;
}

$token = $_POST['_csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
	header("Location: /finanzas_app/views/auth/login.php?error=Token CSRF inválido");
	exit;
}

// Vaciar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();
// Eliminar la cookie de sesión en el cliente
$cookieParams = session_get_cookie_params();
setcookie(
	session_name(),
	'',
	[
		'expires' => time() - 42000,
		'path' => $cookieParams['path'] ?? '/',
		'domain' => $cookieParams['domain'] ?? '',
		'secure' => $cookieParams['secure'] ?? false,
		'httponly' => $cookieParams['httponly'] ?? true,
		'samesite' => $cookieParams['samesite'] ?? 'Lax',
	]
);

// Redirigir al login
header("Location: /finanzas_app/views/auth/login.php");
exit;
