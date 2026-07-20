<?php

require_once __DIR__ . '/app.php';

/*
| ============================================
| CONFIGURACIÓN BASE DE DATOS
| ============================================
*/

// Cargar variables de entorno
$env_file = __DIR__ . '/../../.env.php';
if (!file_exists($env_file)) {
    die('❌ CRÍTICO: Archivo .env.php no encontrado. Verifica que exista en la raíz del proyecto.');
}
require_once $env_file;

// Definir variables de entorno requeridas con valores por defecto
$required_env = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'finanzas_app',
    'DB_USER' => 'root',
    'DB_PASS' => '',
];

// Validar y cargar cada variable
foreach ($required_env as $key => $default) {
    if (!isset($_ENV[$key]) || empty($_ENV[$key])) {
        $_ENV[$key] = $default;
    }
}

// Obtener credenciales
$db_host = $_ENV['DB_HOST'];
$db_port = isset($_ENV['DB_PORT']) ? (int) $_ENV['DB_PORT'] : 3306;
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

// Validar que al menos tengamos DB_NAME
if (empty($db_name)) {
    die('❌ CRÍTICO: DB_NAME no está configurada en .env.php');
}

try {
    // Opciones PDO seguras: no emular prepares y usar excepciones
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Soporte opcional para conexión TLS (si defines DB_SSL_CA en .env.php)
    if (!empty($_ENV['DB_SSL_CA'])) {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
    }

    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdoOptions);

    // 📝 Log de conexión exitosa en desarrollo
    if (APP_DEBUG) {
        error_log("✅ Conexión BD exitosa: {$db_name}@{$db_host}");
    }

} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("❌ Error BD: " . $e->getMessage());
    }
    
    // En producción, log privado sin exponer detalles
    error_log("DB Connection Error: " . $e->getMessage());
    die("❌ Error de conexión con la base de datos. Por favor, intenta más tarde.");
}