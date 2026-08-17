<?php

$occurrenceStatus = (string) ($event['status'] ?? 'pendiente');
$hasLinkedIncome = !empty($event['income_id']);
$hasLinkedExpense = !empty($event['expense_id']);
$hasLinkedMovement = $hasLinkedIncome || $hasLinkedExpense;
$canRegisterIncome = !$hasLinkedMovement
    && $occurrenceStatus !== 'cancelado'
    && ($event['event_type'] ?? '') === 'ingreso_esperado';
$canRegisterExpense = !$hasLinkedMovement
    && $occurrenceStatus !== 'cancelado'
    && in_array($event['event_type'] ?? '', FINANCIAL_EVENT_EXPENSE_TYPES, true);
$defaultIncomeType = ($event['recurrence_type'] ?? 'none') === 'monthly' ? 'mensual' : 'otro';
?>

<section class="financial-occurrence-actions mx-3 mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h6 class="fw-bold mb-1">Acciones para esta fecha</h6>
            <p class="text-muted small mb-0">
                Estos cambios afectan únicamente la ocurrencia del <?= date('d/m/Y', strtotime($event['occurrence_date'])) ?>.
            </p>
        </div>
        <span class="badge rounded-pill bg-light text-dark border align-self-start">
            <?= e($statusLabels[$occurrenceStatus] ?? $occurrenceStatus) ?>
        </span>
    </div>

    <?php if ($hasLinkedMovement): ?>
        <div class="alert alert-success mb-0">
            <i class="bi bi-check-circle-fill me-1"></i>
            Esta ocurrencia ya generó
            <?= $hasLinkedIncome ? 'el ingreso #' . (int) $event['income_id'] : 'el gasto #' . (int) $event['expense_id'] ?>.
        </div>
    <?php else: ?>
        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mb-3">
            <?php if (in_array($occurrenceStatus, ['pendiente', 'vencido'], true)): ?>
                <form method="POST" action="<?= WEB_ROUTE ?>">
                    <input type="hidden" name="action" value="update_financial_occurrence_status">
                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                    <input type="hidden" name="occurrence_date" value="<?= e($event['occurrence_date']) ?>">
                    <input type="hidden" name="status" value="completado">
                    <input type="hidden" name="return_month" value="<?= e($monthParam) ?>">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-outline-success w-100">
                        <i class="bi bi-check2-circle me-1"></i> Marcar completado
                    </button>
                </form>

                <form method="POST" action="<?= WEB_ROUTE ?>">
                    <input type="hidden" name="action" value="update_financial_occurrence_status">
                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                    <input type="hidden" name="occurrence_date" value="<?= e($event['occurrence_date']) ?>">
                    <input type="hidden" name="status" value="cancelado">
                    <input type="hidden" name="return_month" value="<?= e($monthParam) ?>">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i> Cancelar ocurrencia
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= WEB_ROUTE ?>">
                    <input type="hidden" name="action" value="update_financial_occurrence_status">
                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                    <input type="hidden" name="occurrence_date" value="<?= e($event['occurrence_date']) ?>">
                    <input type="hidden" name="status" value="pendiente">
                    <input type="hidden" name="return_month" value="<?= e($monthParam) ?>">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Marcar pendiente
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($canRegisterIncome): ?>
            <details class="financial-occurrence-action-card">
                <summary>
                    <span><i class="bi bi-cash-coin me-2"></i>Registrar como ingreso</span>
                    <i class="bi bi-chevron-down"></i>
                </summary>
                <form method="POST" action="<?= WEB_ROUTE ?>" class="p-3 border-top">
                    <input type="hidden" name="action" value="register_financial_occurrence_income">
                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                    <input type="hidden" name="occurrence_date" value="<?= e($event['occurrence_date']) ?>">
                    <input type="hidden" name="return_month" value="<?= e($monthParam) ?>">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Monto real</label>
                            <input type="number" name="amount" class="form-control" min="0.01" step="0.01"
                                   value="<?= e($event['amount'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Tipo de ingreso</label>
                            <select name="income_type" class="form-select" required>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual" <?= $defaultIncomeType === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                                <option value="otro" <?= $defaultIncomeType === 'otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Fecha real</label>
                            <input type="date" name="movement_date" class="form-control"
                                   value="<?= e($event['occurrence_date']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nota opcional</label>
                            <input type="text" name="note" class="form-control" maxlength="255"
                                   placeholder="Se usará el título del evento si queda vacío">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">
                        <i class="bi bi-check-lg me-1"></i> Confirmar ingreso
                    </button>
                </form>
            </details>
        <?php endif; ?>

        <?php if ($canRegisterExpense): ?>
            <details class="financial-occurrence-action-card">
                <summary>
                    <span><i class="bi bi-receipt me-2"></i>Registrar como gasto</span>
                    <i class="bi bi-chevron-down"></i>
                </summary>

                <?php if (empty($incomeOptions)): ?>
                    <div class="alert alert-warning m-3">
                        Primero debes registrar un ingreso para poder asociar este gasto.
                        <a href="<?= INCOME_CREATE_PATH ?>" class="alert-link">Agregar ingreso</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= WEB_ROUTE ?>" class="p-3 border-top">
                        <input type="hidden" name="action" value="register_financial_occurrence_expense">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <input type="hidden" name="occurrence_date" value="<?= e($event['occurrence_date']) ?>">
                        <input type="hidden" name="return_month" value="<?= e($monthParam) ?>">
                        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Ingreso del cual se descontará</label>
                                <select name="income_id" class="form-select" required>
                                    <option value="">Seleccionar ingreso</option>
                                    <?php foreach ($incomeOptions as $incomeOption): ?>
                                        <option value="<?= (int) $incomeOption['id'] ?>">
                                            <?= e(ucfirst($incomeOption['type'])) ?> · <?= date('d/m/Y', strtotime($incomeOption['income_date'])) ?> · Disponible $<?= number_format((float) $incomeOption['available_amount'], 2) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Monto real</label>
                                <input type="number" name="amount" class="form-control" min="0.01" step="0.01"
                                       value="<?= e($event['amount'] ?? '') ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Fecha real</label>
                                <input type="date" name="movement_date" class="form-control"
                                       value="<?= e($event['occurrence_date']) ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Clasificación</label>
                                <select name="reflection_type" class="form-select" required>
                                    <option value="necesario">Necesario</option>
                                    <option value="gusto">Gusto</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nota opcional</label>
                                <input type="text" name="note" class="form-control" maxlength="255"
                                       placeholder="Se usará el título del evento si queda vacío">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger mt-3">
                            <i class="bi bi-check-lg me-1"></i> Confirmar gasto
                        </button>
                    </form>
                <?php endif; ?>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</section>
