<?php
// Simple checker de requisitos para despliegue
header('Content-Type: text/plain; charset=utf-8');

$checks = [];

// PHP version
$checks['php_version'] = [
    'required' => '8.0',
    'current' => PHP_VERSION,
    'ok' => version_compare(PHP_VERSION, '8.0', '>=')
];

// Extensions
$exts = ['pdo_mysql', 'mbstring', 'fileinfo', 'openssl'];
foreach ($exts as $ext) {
    $checks["ext_{$ext}"] = [
        'required' => true,
        'loaded' => extension_loaded($ext),
        'ok' => extension_loaded($ext)
    ];
}

// GD or Imagick (at least one)
$checks['ext_gd'] = extension_loaded('gd');
$checks['ext_imagick'] = extension_loaded('imagick');
$checks['graphics_ok'] = $checks['ext_gd'] || $checks['ext_imagick'];

// dompdf presence
$checks['dompdf_exists'] = file_exists(__DIR__ . '/../vendor/dompdf/autoload.inc.php') || file_exists(__DIR__ . '/../vendor/autoload.php');

// Logs writable
$logDir = __DIR__ . '/../logs';
$checks['logs_dir_exists'] = is_dir($logDir);
$checks['logs_writable'] = is_writable($logDir);

// Recommended php.ini settings
$checks['display_errors'] = ini_get('display_errors');
$checks['session_auto_start'] = ini_get('session.auto_start');
// OPcache
$checks['ext_opcache'] = extension_loaded('opcache');
$checks['opcache_enable'] = ini_get('opcache.enable');
$checks['opcache_enable_cli'] = ini_get('opcache.enable_cli');

// Summarize
$ok = true;
foreach ($checks as $k => $v) {
    if (is_array($v)) {
        if (isset($v['ok']) && !$v['ok']) $ok = false;
    } elseif ($k === 'graphics_ok') {
        if ($v !== true) $ok = false;
    } elseif ($k === 'dompdf_exists') {
        if ($v !== true) $ok = false;
    } elseif ($k === 'logs_writable') {
        if ($v !== true) $ok = false;
    }
}

// Output
echo "Finanzas App - Comprobador de requisitos\n";
echo "=================================\n";
foreach ($checks as $k => $v) {
    if (is_array($v)) {
        $status = ($v['ok'] ?? false) ? 'OK' : 'MISSING';
        echo sprintf("% -30s : %s\n", $k, $status);
        foreach ($v as $kk => $vv) {
            echo "    {$kk}: {$vv}\n";
        }
    } else {
        if ($k === 'graphics_ok') {
            echo sprintf("% -30s : %s\n", 'graphics (gd|imagick)', $v ? 'OK' : 'MISSING');
            echo "    gd: " . ($checks['ext_gd'] ? 'loaded' : 'no') . '\n';
            echo "    imagick: " . ($checks['ext_imagick'] ? 'loaded' : 'no') . '\n';
            continue;
        }

        if ($k === 'dompdf_exists') {
            echo sprintf("% -30s : %s\n", 'vendor/dompdf', $v ? 'FOUND' : 'MISSING');
            continue;
        }

        if ($k === 'ext_opcache') {
            echo sprintf("% -30s : %s\n", 'opcache extension', $v ? 'loaded' : 'missing');
            echo "    opcache.enable: " . (ini_get('opcache.enable') ? '1' : '0') . "\n";
            echo "    opcache.enable_cli: " . (ini_get('opcache.enable_cli') ? '1' : '0') . "\n";
            continue;
        }

        if ($k === 'logs_dir_exists' || $k === 'logs_writable') {
            echo sprintf("% -30s : %s\n", $k, $v ? 'YES' : 'NO');
            continue;
        }

        echo sprintf("% -30s : %s\n", $k, (string)$v);
    }
}

echo "\nResultado: ".($ok ? "OK - Puedes desplegar" : "FALTAN REQUISITOS - Revisa lo indicado")."\n";

exit($ok ? 0 : 1);
