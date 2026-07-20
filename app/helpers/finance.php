<?php

function getFinancialTotals(PDO $pdo, int $userId, ?string $startDate = null, ?string $endDate = null): array
{
    $incomeSql = "
        SELECT IFNULL(SUM(amount), 0)
        FROM incomes
        WHERE user_id = :user_id
    ";

    $expenseSql = "
        SELECT IFNULL(SUM(e.amount), 0)
        FROM expenses e
        INNER JOIN incomes i ON i.id = e.income_id
        WHERE i.user_id = :user_id
    ";

    $params = ['user_id' => $userId];

    if ($startDate && $endDate) {
        $incomeSql .= " AND income_date BETWEEN :inicio AND :fin";
        $expenseSql .= " AND i.income_date BETWEEN :inicio AND :fin";
        $params['inicio'] = $startDate;
        $params['fin'] = $endDate;
    }

    $stmt = $pdo->prepare($incomeSql);
    $stmt->execute($params);
    $incomes = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare($expenseSql);
    $stmt->execute($params);
    $expenses = (float) $stmt->fetchColumn();

    return [
        'ingresos' => $incomes,
        'gastos' => $expenses,
        'saldo' => $incomes - $expenses,
    ];
}

function isValidFinanceDate(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate && $parsedDate->format('Y-m-d') === $date;
}

function resolveFinanceDateFilter(?string $startDate, ?string $endDate): array
{
    if (!isValidFinanceDate($startDate) || !isValidFinanceDate($endDate) || $startDate > $endDate) {
        return [
            'inicio' => null,
            'fin' => null,
            'activo' => false,
        ];
    }

    return [
        'inicio' => $startDate,
        'fin' => $endDate,
        'activo' => true,
    ];
}

function getIncomeCount(PDO $pdo, int $userId, ?string $startDate = null, ?string $endDate = null): int
{
    $sql = "
        SELECT COUNT(*)
        FROM incomes
        WHERE user_id = :user_id
    ";

    $params = ['user_id' => $userId];

    if ($startDate && $endDate) {
        $sql .= " AND income_date BETWEEN :inicio AND :fin";
        $params['inicio'] = $startDate;
        $params['fin'] = $endDate;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function getIncomeSummaries(PDO $pdo, int $userId, int $limit, int $offset, ?string $startDate = null, ?string $endDate = null): array
{
    $sql = "
        SELECT
            i.id,
            i.amount AS ingreso_total,
            i.type,
            i.income_date,
            IFNULL(SUM(e.amount), 0) AS total_gastos,
            (i.amount - IFNULL(SUM(e.amount), 0)) AS saldo
        FROM incomes i
        LEFT JOIN expenses e ON i.id = e.income_id
        WHERE i.user_id = :user_id
    ";

    if ($startDate && $endDate) {
        $sql .= " AND i.income_date BETWEEN :inicio AND :fin";
    }

    $sql .= "
        GROUP BY i.id
        ORDER BY i.income_date DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    if ($startDate && $endDate) {
        $stmt->bindValue(':inicio', $startDate);
        $stmt->bindValue(':fin', $endDate);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBalanceEvolution(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT
            i.income_date AS fecha,
            SUM(i.amount - IFNULL(expense_totals.total_gastos, 0)) AS saldo_dia
        FROM incomes i
        LEFT JOIN (
            SELECT income_id, SUM(amount) AS total_gastos
            FROM expenses
            GROUP BY income_id
        ) expense_totals ON i.id = expense_totals.income_id
        WHERE i.user_id = :user_id
        GROUP BY i.income_date
        ORDER BY i.income_date ASC
    ");
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCoachBreakdown(PDO $pdo, int $userId, ?string $startDate = null, ?string $endDate = null, bool $countRows = false): array
{
    $aggregate = $countRows ? 'COUNT(*)' : 'SUM(e.amount)';
    $sql = "
        SELECT reflection_type, {$aggregate} as total
        FROM expenses e
        INNER JOIN incomes i ON i.id = e.income_id
        WHERE i.user_id = :user_id
    ";

    $params = ['user_id' => $userId];

    if ($startDate && $endDate) {
        $sql .= " AND i.income_date BETWEEN :inicio AND :fin";
        $params['inicio'] = $startDate;
        $params['fin'] = $endDate;
    } else {
        $sql .= "
            AND MONTH(e.expense_date) = MONTH(CURRENT_DATE())
            AND YEAR(e.expense_date) = YEAR(CURRENT_DATE())
        ";
    }

    $sql .= " GROUP BY reflection_type";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $result = [
        'necesario' => 0,
        'gusto' => 0,
    ];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (isset($result[$row['reflection_type']])) {
            $result[$row['reflection_type']] = (float) $row['total'];
        }
    }

    return $result;
}

function getCoachPercentages(array $coachData): array
{
    $necesarios = $coachData['necesario'] ?? 0;
    $gustos = $coachData['gusto'] ?? 0;
    $total = $necesarios + $gustos;

    return [
        'necesarios' => $necesarios,
        'gustos' => $gustos,
        'total' => $total,
        'porc_necesarios' => $total > 0 ? round(($necesarios / $total) * 100) : 0,
        'porc_gustos' => $total > 0 ? round(($gustos / $total) * 100) : 0,
    ];
}

function getExpenseDetails(PDO $pdo, int $userId, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare("
        SELECT e.expense_date, e.amount, e.note, i.type
        FROM expenses e
        INNER JOIN incomes i ON i.id = e.income_id
        WHERE i.user_id = :user_id
          AND i.income_date BETWEEN :inicio AND :fin
        ORDER BY e.expense_date ASC
    ");
    $stmt->execute([
        'user_id' => $userId,
        'inicio' => $startDate,
        'fin' => $endDate,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
