<?php
/**
 * Escapa salidas HTML de forma segura.
 */
function e($value)
{
    if (is_array($value)) {
        return array_map('e', $value);
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
