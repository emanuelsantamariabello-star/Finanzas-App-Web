<?php

const FINANCIAL_ACCOUNT_TYPES = [
    'banco',
    'billetera_digital',
    'efectivo',
    'ahorro',
    'credito',
    'otra',
];

const FINANCIAL_ACCOUNT_STATUSES = [
    'activa',
    'inactiva',
];

function financialAccountTypeLabels(): array
{
    return [
        'banco' => 'Cuenta bancaria',
        'billetera_digital' => 'Billetera digital',
        'efectivo' => 'Efectivo',
        'ahorro' => 'Ahorro',
        'credito' => 'Crédito',
        'otra' => 'Otra',
    ];
}

function normalizeFinancialAccountData(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $type = (string) ($data['type'] ?? 'otra');
    $institution = trim((string) ($data['institution'] ?? ''));
    $initialBalance = $data['initial_balance'] ?? 0;
    $currency = strtoupper(trim((string) ($data['currency'] ?? 'COP')));
    $status = (string) ($data['status'] ?? 'activa');

    if ($name === '' || mb_strlen($name) > 100) {
        throw new InvalidArgumentException('El nombre de la cuenta es obligatorio y debe tener máximo 100 caracteres.');
    }

    if (!in_array($type, FINANCIAL_ACCOUNT_TYPES, true)) {
        throw new InvalidArgumentException('El tipo de cuenta no es válido.');
    }

    if (mb_strlen($institution) > 100) {
        throw new InvalidArgumentException('La institución debe tener máximo 100 caracteres.');
    }

    if (!is_numeric($initialBalance)) {
        throw new InvalidArgumentException('El saldo inicial debe ser numérico.');
    }

    $initialBalance = round((float) $initialBalance, 2);
    if (abs($initialBalance) > 999999999999.99) {
        throw new InvalidArgumentException('El saldo inicial supera el valor permitido.');
    }

    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        throw new InvalidArgumentException('La moneda debe usar un código de tres letras.');
    }

    if (!in_array($status, FINANCIAL_ACCOUNT_STATUSES, true)) {
        throw new InvalidArgumentException('El estado de la cuenta no es válido.');
    }

    return [
        'name' => $name,
        'type' => $type,
        'institution' => $institution !== '' ? $institution : null,
        'initial_balance' => $initialBalance,
        'currency' => $currency,
        'status' => $status,
    ];
}

function getFinancialAccounts(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT id, user_id, name, type, institution, initial_balance, currency, status, created_at, updated_at
        FROM financial_accounts
        WHERE user_id = :user_id
        ORDER BY status = 'activa' DESC, name ASC, id ASC
    ");
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createFinancialAccount(PDO $pdo, int $userId, array $data): int
{
    $account = normalizeFinancialAccountData($data);
    $stmt = $pdo->prepare("
        INSERT INTO financial_accounts (user_id, name, type, institution, initial_balance, currency, status)
        VALUES (:user_id, :name, :type, :institution, :initial_balance, :currency, :status)
    ");
    $stmt->execute([
        'user_id' => $userId,
        'name' => $account['name'],
        'type' => $account['type'],
        'institution' => $account['institution'],
        'initial_balance' => $account['initial_balance'],
        'currency' => $account['currency'],
        'status' => $account['status'],
    ]);

    return (int) $pdo->lastInsertId();
}

function updateFinancialAccount(PDO $pdo, int $userId, int $accountId, array $data): void
{
    validateFinancialAccountOwnership($pdo, $accountId, $userId);
    $account = normalizeFinancialAccountData($data);
    $stmt = $pdo->prepare("
        UPDATE financial_accounts
        SET name = :name,
            type = :type,
            institution = :institution,
            initial_balance = :initial_balance,
            currency = :currency,
            status = :status
        WHERE id = :id
          AND user_id = :user_id
    ");
    $stmt->execute([
        'name' => $account['name'],
        'type' => $account['type'],
        'institution' => $account['institution'],
        'initial_balance' => $account['initial_balance'],
        'currency' => $account['currency'],
        'status' => $account['status'],
        'id' => $accountId,
        'user_id' => $userId,
    ]);
}

function deleteFinancialAccount(PDO $pdo, int $userId, int $accountId): void
{
    validateFinancialAccountOwnership($pdo, $accountId, $userId);
    $stmt = $pdo->prepare("
        DELETE FROM financial_accounts
        WHERE id = :id
          AND user_id = :user_id
    ");
    $stmt->execute([
        'id' => $accountId,
        'user_id' => $userId,
    ]);
}

function validateFinancialAccountOwnership(PDO $pdo, int $accountId, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM financial_accounts
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $accountId,
        'user_id' => $userId,
    ]);

    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Cuenta no encontrada o no autorizada.');
    }
}
