<?php

function getLatestIncomeDate(PDO $pdo, int $userId): ?string
{
    $stmt = $pdo->prepare("
        SELECT MAX(income_date)
        FROM incomes
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);

    $incomeDate = $stmt->fetchColumn();

    return $incomeDate ?: null;
}

function getActiveSystemNotifications(PDO $pdo): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, message, type, created_at
            FROM system_notifications
            WHERE is_active = 1
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute();
    } catch (PDOException $exception) {
        return [];
    }

    $notifications = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $notifications[] = [
            'id' => 'system-' . $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'source' => 'system',
            'created_at' => $row['created_at'],
        ];
    }

    return $notifications;
}

function getMonthlyPaymentReminderDate(string $latestIncomeDate, DateTimeImmutable $today): DateTimeImmutable
{
    $incomeDay = (int) date('j', strtotime($latestIncomeDate));
    $daysInCurrentMonth = (int) $today->format('t');
    $targetDay = min($incomeDay, $daysInCurrentMonth);
    $targetDate = $today->setDate((int) $today->format('Y'), (int) $today->format('m'), $targetDay);

    if ($today > $targetDate) {
        $nextMonth = $today->modify('first day of next month');
        $targetDay = min($incomeDay, (int) $nextMonth->format('t'));
        $targetDate = $nextMonth->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('m'), $targetDay);
    }

    return $targetDate;
}

function getDashboardNotifications(PDO $pdo, int $userId): array
{
    $notifications = getActiveSystemNotifications($pdo);
    $latestIncomeDate = getLatestIncomeDate($pdo, $userId);

    if (!$latestIncomeDate) {
        return $notifications;
    }

    $today = new DateTimeImmutable('today');
    $reminderDate = getMonthlyPaymentReminderDate($latestIncomeDate, $today);
    $daysUntil = (int) $today->diff($reminderDate)->format('%r%a');

    if (!in_array($daysUntil, [5, 4, 3, 0], true)) {
        return $notifications;
    }

    $message = $daysUntil === 0
        ? 'Hoy coincide con tu fecha habitual de ingreso. Recuerda registrar tus ingresos y gastos.'
        : "Faltan {$daysUntil} días para tu fecha habitual de ingreso. Recuerda mantener actualizado tu control financiero.";

    $notifications[] = [
        'id' => 'income-reminder-' . $reminderDate->format('Y-m-d'),
        'title' => 'Recordatorio financiero',
        'message' => $message,
        'type' => 'info',
        'source' => 'finance',
        'date' => $reminderDate->format('Y-m-d'),
        'days_until' => $daysUntil,
    ];

    return $notifications;
}
