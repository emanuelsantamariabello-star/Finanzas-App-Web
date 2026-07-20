<?php

function requirePost($action, $acciones_post) {
    if (in_array($action, $acciones_post) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirectError('Acción no permitida');
    }
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect(LOGIN_PATH);
    }

}


function redirectSuccess($url, $message) {
    header("Location: {$url}?success=" . urlencode($message));
    exit;
}

/* =========================
   VALIDACIONES DE PROPIEDAD
   ========================= */

function validateIncomeOwnership($pdo, $income_id, $user_id) {
    $stmt = $pdo->prepare("
        SELECT id FROM incomes 
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $income_id,
        'user_id' => $user_id
    ]);

    if (!$stmt->fetch()) {
        redirectError('Acción no autorizada');
    }
}

function validateExpenseOwnership($pdo, $expense_id, $user_id) {
    $stmt = $pdo->prepare("
        SELECT e.id
        FROM expenses e
        INNER JOIN incomes i ON i.id = e.income_id
        WHERE e.id = :id AND i.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $expense_id,
        'user_id' => $user_id
    ]);

    if (!$stmt->fetch()) {
        redirectError('Acción no autorizada');
    }
}

