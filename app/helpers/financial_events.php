<?php

const FINANCIAL_EVENT_TYPES = [
    'pago',
    'ingreso_esperado',
    'gasto_programado',
    'cuota',
    'deuda',
    'suscripcion',
    'recordatorio',
    'otro',
];

const FINANCIAL_EVENT_STATUSES = [
    'pendiente',
    'completado',
    'cancelado',
];

const FINANCIAL_EVENT_RECURRENCES = [
    'none',
    'daily',
    'weekly',
    'monthly',
    'yearly',
];

const FINANCIAL_EVENT_EXPENSE_TYPES = [
    'pago',
    'gasto_programado',
    'cuota',
    'deuda',
    'suscripcion',
];

function financialEventTypeLabels(): array
{
    return [
        'pago' => 'Pago',
        'ingreso_esperado' => 'Ingreso esperado',
        'gasto_programado' => 'Gasto programado',
        'cuota' => 'Cuota',
        'deuda' => 'Deuda',
        'suscripcion' => 'Suscripción',
        'recordatorio' => 'Recordatorio',
        'otro' => 'Otro',
    ];
}

function financialEventStatusLabels(): array
{
    return [
        'pendiente' => 'Pendiente',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
        'vencido' => 'Vencido',
    ];
}

function financialEventRecurrenceLabels(): array
{
    return [
        'none' => 'No se repite',
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
    ];
}

function isValidFinancialEventDate(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate && $parsedDate->format('Y-m-d') === $date;
}

function isValidFinancialEventTime(?string $time): bool
{
    if (!$time) {
        return true;
    }

    $parsedTime = DateTime::createFromFormat('H:i', $time);

    return $parsedTime && $parsedTime->format('H:i') === $time;
}

function normalizeFinancialEventAmount($amount): ?float
{
    if ($amount === null || $amount === '') {
        return null;
    }

    if (!is_numeric($amount) || (float) $amount < 0) {
        throw new InvalidArgumentException('Monto inválido.');
    }

    return round((float) $amount, 2);
}

function normalizeFinancialEventReminder($days): ?int
{
    if ($days === null || $days === '') {
        return null;
    }

    if (!is_numeric($days)) {
        return null;
    }

    $days = (int) $days;

    return $days >= 0 ? $days : null;
}

function normalizeFinancialEventRecurrenceInterval($interval): int
{
    if ($interval === null || $interval === '') {
        return 1;
    }

    if (!is_numeric($interval) || floor((float) $interval) !== (float) $interval) {
        throw new InvalidArgumentException('El intervalo de recurrencia debe ser un número entero.');
    }

    $interval = (int) $interval;
    if ($interval < 1 || $interval > 999) {
        throw new InvalidArgumentException('El intervalo de recurrencia debe estar entre 1 y 999.');
    }

    return $interval;
}

function createFinancialEvent(PDO $pdo, int $userId, array $data): int
{
    $title = trim((string) ($data['title'] ?? ''));
    $eventType = (string) ($data['event_type'] ?? 'otro');
    $amount = normalizeFinancialEventAmount($data['amount'] ?? null);
    $eventDate = (string) ($data['event_date'] ?? '');
    $eventTime = trim((string) ($data['event_time'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $status = (string) ($data['status'] ?? 'pendiente');
    $recurrenceType = (string) ($data['recurrence_type'] ?? 'none');
    $recurrenceInterval = normalizeFinancialEventRecurrenceInterval($data['recurrence_interval'] ?? 1);
    $recurrenceEndsAt = trim((string) ($data['recurrence_ends_at'] ?? ''));
    $reminderDaysBefore = normalizeFinancialEventReminder($data['reminder_days_before'] ?? null);
    $recurrenceIsLastDay = !empty($data['recurrence_is_last_day']) ? 1 : 0;

    if ($title === '') {
        throw new InvalidArgumentException('El título del evento es obligatorio.');
    }

    if (!in_array($eventType, FINANCIAL_EVENT_TYPES, true)) {
        throw new InvalidArgumentException('Tipo de evento inválido.');
    }

    if (!isValidFinancialEventDate($eventDate)) {
        throw new InvalidArgumentException('Fecha de evento inválida.');
    }

    if (!isValidFinancialEventTime($eventTime)) {
        throw new InvalidArgumentException('Hora de evento inválida.');
    }

    if (!in_array($status, FINANCIAL_EVENT_STATUSES, true)) {
        throw new InvalidArgumentException('Estado inválido.');
    }

    if (!in_array($recurrenceType, FINANCIAL_EVENT_RECURRENCES, true)) {
        throw new InvalidArgumentException('Recurrencia inválida.');
    }

    if ($recurrenceType === 'none') {
        $recurrenceInterval = 1;
    }

    if ($recurrenceEndsAt !== '' && !isValidFinancialEventDate($recurrenceEndsAt)) {
        throw new InvalidArgumentException('Fecha final de recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && $recurrenceEndsAt < $eventDate) {
        throw new InvalidArgumentException('La recurrencia no puede terminar antes de la fecha inicial.');
    }

    $recurrenceDayOfMonth = null;
    if ($recurrenceType === 'monthly' && !$recurrenceIsLastDay) {
        $recurrenceDayOfMonth = (int) date('j', strtotime($eventDate));
    }

    $stmt = $pdo->prepare("
        INSERT INTO financial_events (
            user_id,
            title,
            event_type,
            amount,
            event_date,
            event_time,
            description,
            status,
            recurrence_type,
            recurrence_interval,
            recurrence_day_of_month,
            recurrence_is_last_day,
            recurrence_ends_at,
            reminder_days_before
        )
        VALUES (
            :user_id,
            :title,
            :event_type,
            :amount,
            :event_date,
            :event_time,
            :description,
            :status,
            :recurrence_type,
            :recurrence_interval,
            :recurrence_day_of_month,
            :recurrence_is_last_day,
            :recurrence_ends_at,
            :reminder_days_before
        )
    ");

    $stmt->execute([
        'user_id' => $userId,
        'title' => $title,
        'event_type' => $eventType,
        'amount' => $amount,
        'event_date' => $eventDate,
        'event_time' => $eventTime !== '' ? $eventTime : null,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'recurrence_type' => $recurrenceType,
        'recurrence_interval' => $recurrenceInterval,
        'recurrence_day_of_month' => $recurrenceDayOfMonth,
        'recurrence_is_last_day' => $recurrenceIsLastDay,
        'recurrence_ends_at' => $recurrenceEndsAt !== '' ? $recurrenceEndsAt : null,
        'reminder_days_before' => $reminderDaysBefore,
    ]);

    return (int) $pdo->lastInsertId();
}

function updateFinancialEvent(PDO $pdo, int $userId, int $eventId, array $data): void
{
    validateFinancialEventOwnership($pdo, $eventId, $userId);

    $title = trim((string) ($data['title'] ?? ''));
    $eventType = (string) ($data['event_type'] ?? 'otro');
    $amount = normalizeFinancialEventAmount($data['amount'] ?? null);
    $eventDate = (string) ($data['event_date'] ?? '');
    $eventTime = trim((string) ($data['event_time'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $status = (string) ($data['status'] ?? 'pendiente');
    $recurrenceType = (string) ($data['recurrence_type'] ?? 'none');
    $recurrenceInterval = normalizeFinancialEventRecurrenceInterval($data['recurrence_interval'] ?? 1);
    $recurrenceEndsAt = trim((string) ($data['recurrence_ends_at'] ?? ''));
    $reminderDaysBefore = normalizeFinancialEventReminder($data['reminder_days_before'] ?? null);
    $recurrenceIsLastDay = !empty($data['recurrence_is_last_day']) ? 1 : 0;

    if ($title === '') {
        throw new InvalidArgumentException('El título del evento es obligatorio.');
    }

    if (!in_array($eventType, FINANCIAL_EVENT_TYPES, true)) {
        throw new InvalidArgumentException('Tipo de evento inválido.');
    }

    if (!isValidFinancialEventDate($eventDate)) {
        throw new InvalidArgumentException('Fecha de evento inválida.');
    }

    if (!isValidFinancialEventTime($eventTime)) {
        throw new InvalidArgumentException('Hora de evento inválida.');
    }

    if (!in_array($status, FINANCIAL_EVENT_STATUSES, true)) {
        throw new InvalidArgumentException('Estado inválido.');
    }

    if (!in_array($recurrenceType, FINANCIAL_EVENT_RECURRENCES, true)) {
        throw new InvalidArgumentException('Recurrencia inválida.');
    }

    if ($recurrenceType === 'none') {
        $recurrenceInterval = 1;
    }

    if ($recurrenceEndsAt !== '' && !isValidFinancialEventDate($recurrenceEndsAt)) {
        throw new InvalidArgumentException('Fecha final de recurrencia inválida.');
    }

    if ($recurrenceEndsAt !== '' && $recurrenceEndsAt < $eventDate) {
        throw new InvalidArgumentException('La recurrencia no puede terminar antes de la fecha inicial.');
    }

    $recurrenceDayOfMonth = null;
    if ($recurrenceType === 'monthly' && !$recurrenceIsLastDay) {
        $recurrenceDayOfMonth = (int) date('j', strtotime($eventDate));
    }

    $stmt = $pdo->prepare("
        UPDATE financial_events
        SET title = :title,
            event_type = :event_type,
            amount = :amount,
            event_date = :event_date,
            event_time = :event_time,
            description = :description,
            status = :status,
            recurrence_type = :recurrence_type,
            recurrence_interval = :recurrence_interval,
            recurrence_day_of_month = :recurrence_day_of_month,
            recurrence_is_last_day = :recurrence_is_last_day,
            recurrence_ends_at = :recurrence_ends_at,
            reminder_days_before = :reminder_days_before
        WHERE id = :id
          AND user_id = :user_id
    ");

    $stmt->execute([
        'title' => $title,
        'event_type' => $eventType,
        'amount' => $amount,
        'event_date' => $eventDate,
        'event_time' => $eventTime !== '' ? $eventTime : null,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'recurrence_type' => $recurrenceType,
        'recurrence_interval' => $recurrenceInterval,
        'recurrence_day_of_month' => $recurrenceDayOfMonth,
        'recurrence_is_last_day' => $recurrenceIsLastDay,
        'recurrence_ends_at' => $recurrenceEndsAt !== '' ? $recurrenceEndsAt : null,
        'reminder_days_before' => $reminderDaysBefore,
        'id' => $eventId,
        'user_id' => $userId,
    ]);
}

function deleteFinancialEvent(PDO $pdo, int $userId, int $eventId): void
{
    validateFinancialEventOwnership($pdo, $eventId, $userId);

    $stmt = $pdo->prepare("
        DELETE FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);
}

function validateFinancialEventOwnership(PDO $pdo, int $eventId, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);

    if (!$stmt->fetch()) {
        throw new RuntimeException('Evento no encontrado o no autorizado.');
    }
}

function getFinancialEvent(PDO $pdo, int $userId, int $eventId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    return $event ?: null;
}

function getFinancialEventsForRange(PDO $pdo, int $userId, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM financial_events
        WHERE user_id = :user_id
          AND event_date <= :end_date
          AND (recurrence_ends_at IS NULL OR recurrence_ends_at >= :start_date)
        ORDER BY event_date ASC, event_time ASC, id ASC
    ");
    $stmt->execute([
        'user_id' => $userId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $storedOccurrences = getStoredFinancialEventOccurrences($pdo, $userId, $startDate, $endDate);
    $events = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $event) {
        foreach (expandFinancialEventOccurrences($event, $startDate, $endDate) as $occurrence) {
            $key = $occurrence['id'] . ':' . $occurrence['occurrence_date'];
            $events[] = applyStoredFinancialEventOccurrence($occurrence, $storedOccurrences[$key] ?? null);
        }
    }

    usort($events, static function (array $a, array $b): int {
        return [$a['occurrence_date'], $a['event_time'] ?? '', $a['id']] <=> [$b['occurrence_date'], $b['event_time'] ?? '', $b['id']];
    });

    return $events;
}

function getStoredFinancialEventOccurrences(PDO $pdo, int $userId, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare("
        SELECT id, event_id, occurrence_date, status, income_id, expense_id, completed_at
        FROM financial_event_occurrences
        WHERE user_id = :user_id
          AND occurrence_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute([
        'user_id' => $userId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $occurrences = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $occurrence) {
        $occurrences[$occurrence['event_id'] . ':' . $occurrence['occurrence_date']] = $occurrence;
    }

    return $occurrences;
}

function applyStoredFinancialEventOccurrence(array $occurrence, ?array $storedOccurrence): array
{
    $occurrence['occurrence_id'] = null;
    $occurrence['income_id'] = null;
    $occurrence['expense_id'] = null;
    $occurrence['completed_at'] = null;

    if (!$storedOccurrence) {
        return $occurrence;
    }

    $status = $storedOccurrence['status'];
    if ($status === 'pendiente' && $occurrence['occurrence_date'] < date('Y-m-d')) {
        $status = 'vencido';
    }

    $occurrence['occurrence_id'] = (int) $storedOccurrence['id'];
    $occurrence['status'] = $status;
    $occurrence['income_id'] = $storedOccurrence['income_id'] !== null ? (int) $storedOccurrence['income_id'] : null;
    $occurrence['expense_id'] = $storedOccurrence['expense_id'] !== null ? (int) $storedOccurrence['expense_id'] : null;
    $occurrence['completed_at'] = $storedOccurrence['completed_at'];

    return $occurrence;
}

function expandFinancialEventOccurrences(array $event, string $startDate, string $endDate): array
{
    if ($event['status'] === 'cancelado') {
        return [];
    }

    $occurrences = [];
    $current = new DateTimeImmutable($event['event_date']);
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $recurrenceEnd = !empty($event['recurrence_ends_at']) ? new DateTimeImmutable($event['recurrence_ends_at']) : $end;
    $limit = $recurrenceEnd < $end ? $recurrenceEnd : $end;
    $recurrenceType = $event['recurrence_type'] ?? 'none';

    while ($current <= $limit) {
        if ($current >= $start) {
            $occurrences[] = financialEventOccurrenceFromEvent($event, $current);
        }

        if ($recurrenceType === 'none') {
            break;
        }

        $next = nextFinancialEventOccurrenceDate($event, $current);
        if ($next <= $current) {
            break;
        }

        $current = $next;
    }

    return $occurrences;
}

function nextFinancialEventOccurrenceDate(array $event, DateTimeImmutable $current): DateTimeImmutable
{
    $interval = max(1, (int) ($event['recurrence_interval'] ?? 1));

    if ($event['recurrence_type'] === 'daily') {
        return $current->modify("+{$interval} day");
    }

    if ($event['recurrence_type'] === 'weekly') {
        return $current->modify("+{$interval} week");
    }

    if ($event['recurrence_type'] === 'yearly') {
        return $current->modify("+{$interval} year");
    }

    if ($event['recurrence_type'] === 'monthly') {
        $nextMonth = $current->modify("first day of +{$interval} month");

        if (!empty($event['recurrence_is_last_day'])) {
            return $nextMonth->modify('last day of this month');
        }

        $day = (int) ($event['recurrence_day_of_month'] ?: $current->format('j'));
        $day = min($day, (int) $nextMonth->format('t'));

        return $nextMonth->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('m'), $day);
    }

    return $current;
}

function financialEventOccurrenceFromEvent(array $event, DateTimeImmutable $date): array
{
    $occurrenceDate = $date->format('Y-m-d');
    $status = $event['status'];

    if ($status === 'pendiente' && $occurrenceDate < date('Y-m-d')) {
        $status = 'vencido';
    }

    return [
        'id' => (int) $event['id'],
        'title' => $event['title'],
        'event_type' => $event['event_type'],
        'amount' => $event['amount'] !== null ? (float) $event['amount'] : null,
        'occurrence_date' => $occurrenceDate,
        'event_time' => $event['event_time'],
        'description' => $event['description'],
        'status' => $status,
        'recurrence_type' => $event['recurrence_type'],
        'recurrence_interval' => (int) ($event['recurrence_interval'] ?? 1),
        'reminder_days_before' => $event['reminder_days_before'] !== null ? (int) $event['reminder_days_before'] : null,
        'is_recurring' => $event['recurrence_type'] !== 'none',
    ];
}

function getFinancialEventIncomeOptions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT
            i.id,
            i.type,
            i.income_date,
            i.amount,
            i.amount - COALESCE(SUM(e.amount), 0) AS available_amount
        FROM incomes i
        LEFT JOIN expenses e ON e.income_id = i.id
        WHERE i.user_id = :user_id
        GROUP BY i.id, i.type, i.income_date, i.amount
        ORDER BY i.income_date DESC, i.id DESC
        LIMIT 100
    ");
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateFinancialEventOccurrenceStatus(
    PDO $pdo,
    int $userId,
    int $eventId,
    string $occurrenceDate,
    string $status
): void {
    if (!in_array($status, ['pendiente', 'completado', 'cancelado'], true)) {
        throw new InvalidArgumentException('Estado de ocurrencia inválido.');
    }

    runFinancialEventTransaction($pdo, static function () use ($pdo, $userId, $eventId, $occurrenceDate, $status): void {
        $event = getFinancialEventForUpdate($pdo, $userId, $eventId);
        validateFinancialEventOccurrenceDate($event, $occurrenceDate);
        $occurrence = ensureFinancialEventOccurrenceForUpdate($pdo, $userId, $eventId, $occurrenceDate);

        if ($occurrence['income_id'] !== null || $occurrence['expense_id'] !== null) {
            throw new RuntimeException('La ocurrencia ya está vinculada a un movimiento y no puede cambiarse manualmente.');
        }

        $stmt = $pdo->prepare("
            UPDATE financial_event_occurrences
            SET status = :status,
                completed_at = :completed_at
            WHERE id = :id
              AND user_id = :user_id
        ");
        $stmt->execute([
            'status' => $status,
            'completed_at' => $status === 'completado' ? date('Y-m-d H:i:s') : null,
            'id' => $occurrence['id'],
            'user_id' => $userId,
        ]);
    });
}

function registerFinancialEventOccurrenceAsIncome(
    PDO $pdo,
    int $userId,
    int $eventId,
    string $occurrenceDate,
    array $data
): int {
    return runFinancialEventTransaction($pdo, static function () use ($pdo, $userId, $eventId, $occurrenceDate, $data): int {
        $event = getFinancialEventForUpdate($pdo, $userId, $eventId);
        validateFinancialEventOccurrenceDate($event, $occurrenceDate);

        if ($event['event_type'] !== 'ingreso_esperado') {
            throw new InvalidArgumentException('Solo los ingresos esperados pueden registrarse como ingreso.');
        }

        $occurrence = ensureFinancialEventOccurrenceForUpdate($pdo, $userId, $eventId, $occurrenceDate);
        validateFinancialEventOccurrenceIsAvailable($occurrence);

        $amount = normalizeFinancialMovementAmount($data['amount'] ?? null);
        $incomeType = (string) ($data['income_type'] ?? 'otro');
        $movementDate = (string) ($data['movement_date'] ?? $occurrenceDate);
        $note = trim((string) ($data['note'] ?? ''));

        if (!in_array($incomeType, ['quincenal', 'mensual', 'otro'], true)) {
            throw new InvalidArgumentException('Tipo de ingreso inválido.');
        }

        if (!isValidFinancialEventDate($movementDate)) {
            throw new InvalidArgumentException('Fecha de ingreso inválida.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO incomes (user_id, amount, type, income_date, note)
            VALUES (:user_id, :amount, :type, :income_date, :note)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $incomeType,
            'income_date' => $movementDate,
            'note' => $note !== '' ? $note : $event['title'],
        ]);

        $incomeId = (int) $pdo->lastInsertId();
        completeFinancialEventOccurrenceWithMovement($pdo, (int) $occurrence['id'], $userId, $incomeId, null);

        return $incomeId;
    });
}

function registerFinancialEventOccurrenceAsExpense(
    PDO $pdo,
    int $userId,
    int $eventId,
    string $occurrenceDate,
    array $data
): int {
    return runFinancialEventTransaction($pdo, static function () use ($pdo, $userId, $eventId, $occurrenceDate, $data): int {
        $event = getFinancialEventForUpdate($pdo, $userId, $eventId);
        validateFinancialEventOccurrenceDate($event, $occurrenceDate);

        if (!in_array($event['event_type'], FINANCIAL_EVENT_EXPENSE_TYPES, true)) {
            throw new InvalidArgumentException('Este tipo de evento no puede registrarse como gasto.');
        }

        $occurrence = ensureFinancialEventOccurrenceForUpdate($pdo, $userId, $eventId, $occurrenceDate);
        validateFinancialEventOccurrenceIsAvailable($occurrence);

        $incomeId = (int) ($data['income_id'] ?? 0);
        $amount = normalizeFinancialMovementAmount($data['amount'] ?? null);
        $movementDate = (string) ($data['movement_date'] ?? $occurrenceDate);
        $reflectionType = (string) ($data['reflection_type'] ?? '');
        $note = trim((string) ($data['note'] ?? ''));

        if (!isValidFinancialEventDate($movementDate)) {
            throw new InvalidArgumentException('Fecha de gasto inválida.');
        }

        if (!in_array($reflectionType, ['necesario', 'gusto'], true)) {
            throw new InvalidArgumentException('Clasificación de gasto inválida.');
        }

        $incomeStmt = $pdo->prepare("
            SELECT id
            FROM incomes
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
            FOR UPDATE
        ");
        $incomeStmt->execute([
            'id' => $incomeId,
            'user_id' => $userId,
        ]);

        if (!$incomeStmt->fetchColumn()) {
            throw new RuntimeException('Selecciona un ingreso válido para registrar el gasto.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO expenses (income_id, amount, expense_date, note, reflection_type)
            VALUES (:income_id, :amount, :expense_date, :note, :reflection_type)
        ");
        $stmt->execute([
            'income_id' => $incomeId,
            'amount' => $amount,
            'expense_date' => $movementDate,
            'note' => $note !== '' ? $note : $event['title'],
            'reflection_type' => $reflectionType,
        ]);

        $expenseId = (int) $pdo->lastInsertId();
        completeFinancialEventOccurrenceWithMovement($pdo, (int) $occurrence['id'], $userId, null, $expenseId);

        return $expenseId;
    });
}

function getFinancialEventForUpdate(PDO $pdo, int $userId, int $eventId): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM financial_events
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId,
    ]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new RuntimeException('Evento no encontrado o no autorizado.');
    }

    return $event;
}

function validateFinancialEventOccurrenceDate(array $event, string $occurrenceDate): void
{
    if (!isValidFinancialEventDate($occurrenceDate)) {
        throw new InvalidArgumentException('Fecha de ocurrencia inválida.');
    }

    $occurrences = expandFinancialEventOccurrences($event, $occurrenceDate, $occurrenceDate);
    if (count($occurrences) !== 1 || $occurrences[0]['occurrence_date'] !== $occurrenceDate) {
        throw new RuntimeException('La fecha seleccionada no pertenece a este evento.');
    }
}

function ensureFinancialEventOccurrenceForUpdate(
    PDO $pdo,
    int $userId,
    int $eventId,
    string $occurrenceDate
): array {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO financial_event_occurrences (event_id, user_id, occurrence_date, status)
        VALUES (:event_id, :user_id, :occurrence_date, 'pendiente')
    ");
    $stmt->execute([
        'event_id' => $eventId,
        'user_id' => $userId,
        'occurrence_date' => $occurrenceDate,
    ]);

    $stmt = $pdo->prepare("
        SELECT id, event_id, user_id, occurrence_date, status, income_id, expense_id, completed_at
        FROM financial_event_occurrences
        WHERE event_id = :event_id
          AND user_id = :user_id
          AND occurrence_date = :occurrence_date
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([
        'event_id' => $eventId,
        'user_id' => $userId,
        'occurrence_date' => $occurrenceDate,
    ]);
    $occurrence = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$occurrence) {
        throw new RuntimeException('No se pudo preparar la ocurrencia financiera.');
    }

    return $occurrence;
}

function validateFinancialEventOccurrenceIsAvailable(array $occurrence): void
{
    if ($occurrence['income_id'] !== null || $occurrence['expense_id'] !== null) {
        throw new RuntimeException('Esta ocurrencia ya fue registrada como movimiento.');
    }

    if ($occurrence['status'] === 'cancelado') {
        throw new RuntimeException('Reactiva la ocurrencia antes de registrarla como movimiento.');
    }
}

function normalizeFinancialMovementAmount($amount): float
{
    if (!is_numeric($amount) || (float) $amount <= 0) {
        throw new InvalidArgumentException('El monto del movimiento debe ser mayor que cero.');
    }

    return round((float) $amount, 2);
}

function completeFinancialEventOccurrenceWithMovement(
    PDO $pdo,
    int $occurrenceId,
    int $userId,
    ?int $incomeId,
    ?int $expenseId
): void {
    $stmt = $pdo->prepare("
        UPDATE financial_event_occurrences
        SET status = 'completado',
            income_id = :income_id,
            expense_id = :expense_id,
            completed_at = :completed_at
        WHERE id = :id
          AND user_id = :user_id
    ");
    $stmt->execute([
        'income_id' => $incomeId,
        'expense_id' => $expenseId,
        'completed_at' => date('Y-m-d H:i:s'),
        'id' => $occurrenceId,
        'user_id' => $userId,
    ]);
}

function runFinancialEventTransaction(PDO $pdo, callable $callback)
{
    $ownsTransaction = !$pdo->inTransaction();

    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $result = $callback();

        if ($ownsTransaction) {
            $pdo->commit();
        }

        return $result;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function getUpcomingFinancialEventNotifications(PDO $pdo, int $userId, int $daysAhead = 7): array
{
    $today = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
    $events = getFinancialEventsForRange($pdo, $userId, $today, $endDate);
    $notifications = [];

    foreach ($events as $event) {
        if (!in_array($event['status'], ['pendiente', 'vencido'], true)) {
            continue;
        }

        $daysUntil = (int) (new DateTimeImmutable($today))->diff(new DateTimeImmutable($event['occurrence_date']))->format('%r%a');
        $reminderDaysBefore = $event['reminder_days_before'];

        if ($daysUntil > 0 && $reminderDaysBefore === null && $daysUntil > 1) {
            continue;
        }

        if ($daysUntil > 0 && $reminderDaysBefore !== null && $daysUntil > $reminderDaysBefore) {
            continue;
        }

        if ($daysUntil < 0) {
            $message = 'Tienes un evento financiero vencido pendiente por revisar.';
        } elseif ($daysUntil === 0) {
            $message = 'Tienes un evento financiero programado para hoy.';
        } elseif ($daysUntil === 1) {
            $message = 'Tienes un evento financiero programado para mañana.';
        } else {
            $message = "Tienes un evento financiero programado en {$daysUntil} días.";
        }

        $notifications[] = [
            'id' => 'financial-event-' . $event['id'] . '-' . $event['occurrence_date'],
            'title' => $event['title'],
            'message' => $message,
            'type' => $event['status'] === 'vencido' ? 'warning' : 'info',
            'source' => 'financial_event',
            'date' => $event['occurrence_date'],
            'days_until' => $daysUntil,
        ];
    }

    return $notifications;
}
