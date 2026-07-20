<?php

require_once __DIR__ . '/redirect.php';

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        redirect(LOGIN_PATH);
    }
}