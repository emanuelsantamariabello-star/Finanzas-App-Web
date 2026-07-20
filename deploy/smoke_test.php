<?php
// Smoke tests basicos antes del lanzamiento
// Uso: php deploy/smoke_test.php

header('Content-Type: text/plain; charset=utf-8');

echo "Finanzas App - Smoke Tests\n";
echo "========================\n";

$root = dirname(__DIR__);
$ok = true;

echo "[1/6] Comprobando requisitos (deploy/check_requirements.php)...\n";
$cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/deploy/check_requirements.php');
exec($cmd, $out, $code);
echo implode("\n", $out) . "\n";
if ($code !== 0) {
    echo "-> Algunos requisitos faltan (ver arriba).\n";
    $ok = false;
} else {
    echo "-> Requisitos OK.\n";
}

echo "\n[2/6] Verificando archivos criticos...\n";
$files = [
    '/index.php',
    '/web.php',
    '/public/css/styles.css',
    '/public/js/main.js',
    '/app/config/app.php',
    '/app/helpers/mailer.php',
    '/vend0r/phpmailer/src/PHPMailer.php',
];

foreach ($files as $file) {
    $path = $root . $file;
    echo sprintf(" - %s: %s\n", $file, file_exists($path) ? 'FOUND' : 'MISSING');
    if (!file_exists($path)) {
        $ok = false;
    }
}

echo "\n[3/6] Verificando .env.php y conexion a la base de datos...\n";
$envFile = $root . '/.env.php';
if (!file_exists($envFile)) {
    echo " - .env.php: MISSING (debes crear y rellenar .env.php en la raiz)\n";
    $ok = false;
} else {
    require $envFile;

    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '3306';
    $dbName = $_ENV['DB_NAME'] ?? '';
    $dbUser = $_ENV['DB_USER'] ?? '';
    $dbPass = $_ENV['DB_PASS'] ?? '';

    echo " - DB host: {$dbHost}:{$dbPort}\n";

    if (empty($dbName) || empty($dbUser)) {
        echo " -> DB_NAME o DB_USER no configurados en .env.php\n";
        $ok = false;
    } else {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            echo " -> Conexion a la base de datos: OK\n";

            $stmt = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'reflection_type'");
            if ($stmt->fetch()) {
                echo " -> Columna expenses.reflection_type: OK\n";
            } else {
                echo " -> Columna expenses.reflection_type: MISSING\n";
                $ok = false;
            }
        } catch (PDOException $exception) {
            echo " -> Conexion a la base de datos: ERROR\n";
            if (getenv('SMOKE_DEBUG') === '1') {
                echo "    Detalle: " . $exception->getMessage() . "\n";
            }
            $ok = false;
        }
    }
}

echo "\n[4/6] Verificando directorio de logs...\n";
$logDir = $root . '/logs';
echo ' - logs exists: ' . (is_dir($logDir) ? 'YES' : 'NO') . "\n";
echo ' - logs writable: ' . (is_writable($logDir) ? 'YES' : 'NO') . "\n";
if (!is_dir($logDir) || !is_writable($logDir)) {
    $ok = false;
}

echo "\n[5/6] Verificando dependencias incluidas...\n";
$dompdfAutoload1 = $root . '/vendor/dompdf/autoload.inc.php';
$dompdfAutoload2 = $root . '/vendor/autoload.php';
if (file_exists($dompdfAutoload1) || file_exists($dompdfAutoload2)) {
    echo " - dompdf: FOUND\n";
} else {
    echo " - dompdf: MISSING (revisa vendor/)\n";
    $ok = false;
}

$phpmailer = $root . '/vend0r/phpmailer/src/PHPMailer.php';
if (file_exists($phpmailer)) {
    echo " - PHPMailer: FOUND\n";
} else {
    echo " - PHPMailer: MISSING (revisa vend0r/)\n";
    $ok = false;
}

echo "\n[6/6] Verificando assets publicos...\n";
$css = $root . '/public/css/styles.css';
$js = $root . '/public/js/main.js';
echo ' - styles.css: ' . (file_exists($css) ? 'FOUND' : 'MISSING') . "\n";
echo ' - main.js: ' . (file_exists($js) ? 'FOUND' : 'MISSING') . "\n";
if (!file_exists($css) || !file_exists($js)) {
    $ok = false;
}

echo "\nResumen: " . ($ok ? "OK - Listo para desplegar" : "FALTAN ELEMENTOS - Revisa lo indicado") . "\n";

exit($ok ? 0 : 1);
