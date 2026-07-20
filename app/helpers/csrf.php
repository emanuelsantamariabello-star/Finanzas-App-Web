<?php

function csrfToken()
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function verifyCsrf()
{
    $token = $_POST['_csrf'] ?? '';

    if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        redirectError('Token CSRF inválido');
    }
}