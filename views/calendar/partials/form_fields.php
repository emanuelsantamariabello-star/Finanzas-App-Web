<?php

$financialEvent = $financialEvent ?? [
    'title' => '',
    'event_type' => 'otro',
    'amount' => '',
    'event_date' => date('Y-m-d'),
    'event_time' => '',
    'description' => '',
    'status' => 'pendiente',
    'recurrence_type' => 'none',
    'recurrence_is_last_day' => 0,
    'recurrence_ends_at' => '',
    'reminder_days_before' => '',
];

$typeLabels = $typeLabels ?? financialEventTypeLabels();
$statusLabels = $statusLabels ?? financialEventStatusLabels();
$recurrenceLabels = $recurrenceLabels ?? financialEventRecurrenceLabels();
$fieldSuffix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($financialEventFieldSuffix ?? ($financialEvent['id'] ?? 'new')));
?>

<div class="row g-3">
    <div class="col-12 col-md-8">
        <label class="form-label fw-semibold">Título</label>
        <input type="text"
               name="title"
               class="form-control"
               maxlength="150"
               value="<?= e($financialEvent['title'] ?? '') ?>"
               required>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Tipo</label>
        <select name="event_type" class="form-select" required>
            <?php foreach ($typeLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($financialEvent['event_type'] ?? 'otro') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Monto opcional</label>
        <input type="number"
               name="amount"
               class="form-control"
               min="0"
               step="0.01"
               value="<?= e($financialEvent['amount'] ?? '') ?>">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Fecha</label>
        <input type="date"
               name="event_date"
               class="form-control"
               value="<?= e($financialEvent['event_date'] ?? date('Y-m-d')) ?>"
               required>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Hora opcional</label>
        <input type="time"
               name="event_time"
               class="form-control"
               value="<?= e($financialEvent['event_time'] ? substr((string) $financialEvent['event_time'], 0, 5) : '') ?>">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Descripción opcional</label>
        <textarea name="description"
                  class="form-control"
                  rows="3"><?= e($financialEvent['description'] ?? '') ?></textarea>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Estado</label>
        <select name="status" class="form-select" required>
            <?php foreach (['pendiente', 'completado', 'cancelado'] as $value): ?>
                <option value="<?= e($value) ?>" <?= ($financialEvent['status'] ?? 'pendiente') === $value ? 'selected' : '' ?>>
                    <?= e($statusLabels[$value]) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Recurrencia</label>
        <select name="recurrence_type" class="form-select">
            <?php foreach ($recurrenceLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($financialEvent['recurrence_type'] ?? 'none') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Recordar días antes</label>
        <input type="number"
               name="reminder_days_before"
               class="form-control"
               min="0"
               value="<?= e($financialEvent['reminder_days_before'] ?? '') ?>">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Fin de recurrencia</label>
        <input type="date"
               name="recurrence_ends_at"
               class="form-control"
               value="<?= e($financialEvent['recurrence_ends_at'] ?? '') ?>">
    </div>

    <div class="col-12 col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   name="recurrence_is_last_day"
                   value="1"
                   id="lastDay<?= e($fieldSuffix) ?>"
                   <?= !empty($financialEvent['recurrence_is_last_day']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="lastDay<?= e($fieldSuffix) ?>">
                Si es mensual, usar último día del mes
            </label>
        </div>
    </div>
</div>
