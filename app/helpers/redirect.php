<?php

/* ======================================================
   REDIRECCIÓN GENERAL
   ====================================================== */

function redirect(string $path, array $params = [], int $statusCode = 303): void
{
    $url = $path;

    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= '?' . $query;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate');
    header("Location: {$url}", true, $statusCode);
    exit;
}

/* ======================================================
   REDIRECCIÓN CON ERROR
   ====================================================== */

function redirectError(string $message, string $path = null): void
{
    if (!$path) {
        $path = LOGIN_PATH;
    }

    redirect($path, [
        'error' => $message
    ]);
}
