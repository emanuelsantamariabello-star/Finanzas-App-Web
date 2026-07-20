<?php

/* ======================================================
   OBTENER ACCIÓN
   ====================================================== */

function action(): ?string
{
    if (isset($_POST['action']) && $_POST['action'] !== '') {
        return $_POST['action'];
    }

    if (isset($_GET['action']) && $_GET['action'] !== '') {
        return $_GET['action'];
    }

    return null;
}

/* ======================================================
   GET SEGURO
   ====================================================== */

function get(string $key, $default = null, bool $sanitizeFlag = true)
{
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        return $sanitizeFlag ? sanitize($_GET[$key]) : $_GET[$key];
    }

    return $default;
}

/* ======================================================
   POST SEGURO
   ====================================================== */

function post(string $key, $default = null, bool $sanitizeFlag = true)
{
    if (isset($_POST[$key]) && $_POST[$key] !== '') {
        return $sanitizeFlag ? sanitize($_POST[$key]) : $_POST[$key];
    }

    return $default;
}

/* ======================================================
   REQUEST GENÉRICO (GET > POST)
   ====================================================== */

function request(string $key, $default = null, bool $sanitizeFlag = true)
{
    if (isset($_POST[$key]) && $_POST[$key] !== '') {
        return $sanitizeFlag ? sanitize($_POST[$key]) : $_POST[$key];
    }

    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        return $sanitizeFlag ? sanitize($_GET[$key]) : $_GET[$key];
    }

    return $default;
}


/**
 * Sanitiza valores de entrada de forma recursiva.
 * - Strings: trim, remover null bytes y tags HTML.
 * - Arrays: aplica recursivamente.
 */
function sanitize($value)
{
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $k => $v) {
            $clean[$k] = sanitize($v);
        }
        return $clean;
    }

    if (is_string($value)) {
        $v = trim($value);
        $v = str_replace("\0", '', $v);
        $v = strip_tags($v);
        return $v;
    }

    return $value;
}