<?php

/* ======================================================
   REDIRECCIÓN GENERAL
   ====================================================== */

function redirect(string $path, array $params = []): void
{
    $url = $path;

    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= '?' . $query;
    }

    header("Location: {$url}");
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