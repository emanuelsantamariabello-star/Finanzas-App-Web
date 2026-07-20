<?php

function isValidReportDate(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate && $parsedDate->format('Y-m-d') === $date;
}

function getUserIncomeDateRange(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT MIN(income_date), MAX(income_date)
        FROM incomes
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);

    [$startDate, $endDate] = $stmt->fetch(PDO::FETCH_NUM);

    if (!$startDate || !$endDate) {
        return null;
    }

    return [
        'inicio' => $startDate,
        'fin' => $endDate,
    ];
}

function resolveReportPeriod(PDO $pdo, int $userId, ?string $period, ?string $from = null, ?string $to = null): ?array
{
    $today = date('Y-m-d');

    if ($period === 'mes_actual') {
        return [
            'inicio' => date('Y-m-01'),
            'fin' => $today,
            'nombre' => 'Mes actual',
        ];
    }

    if ($period === 'mes_anterior') {
        return [
            'inicio' => date('Y-m-01', strtotime('first day of last month')),
            'fin' => date('Y-m-t', strtotime('last day of last month')),
            'nombre' => 'Mes anterior',
        ];
    }

    if ($period === 'personalizado') {
        if (!isValidReportDate($from) || !isValidReportDate($to) || $from > $to) {
            return null;
        }

        return [
            'inicio' => $from,
            'fin' => $to,
            'nombre' => 'Período personalizado',
        ];
    }

    if ($period === 'todo') {
        $dateRange = getUserIncomeDateRange($pdo, $userId);

        if (!$dateRange) {
            return null;
        }

        return [
            'inicio' => $dateRange['inicio'],
            'fin' => $dateRange['fin'],
            'nombre' => 'Todo el historial',
        ];
    }

    return null;
}
