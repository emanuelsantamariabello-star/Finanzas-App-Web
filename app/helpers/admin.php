<?php

require_once __DIR__ . '/redirect.php';

function adminUserIds(): array
{
    $adminIds = $_ENV['ADMIN_USER_IDS'] ?? '';

    if (!$adminIds) {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn($id) => (int) trim($id),
        explode(',', $adminIds)
    )));
}

function currentUserIsAdmin(): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    return in_array((int) $_SESSION['user_id'], adminUserIds(), true);
}

function requireAdmin(): void
{
    if (!currentUserIsAdmin()) {
        redirect(DASHBOARD_PATH, ['error' => 'No tienes permisos para acceder a esta sección']);
    }
}
