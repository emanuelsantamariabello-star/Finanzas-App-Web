<?php

$financialAccount = $financialAccount ?? [
    'name' => '',
    'type' => 'banco',
    'institution' => '',
    'initial_balance' => '0.00',
    'currency' => 'COP',
    'status' => 'activa',
];

$accountTypeLabels = $accountTypeLabels ?? financialAccountTypeLabels();
?>

<div class="row g-3">
    <div class="col-12 col-md-7">
        <label class="form-label fw-semibold">Nombre de la cuenta</label>
        <input type="text"
               name="name"
               class="form-control"
               maxlength="100"
               placeholder="Ej: Cuenta principal"
               value="<?= e($financialAccount['name'] ?? '') ?>"
               required>
    </div>

    <div class="col-12 col-md-5">
        <label class="form-label fw-semibold">Tipo</label>
        <select name="type" class="form-select" required>
            <?php foreach ($accountTypeLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($financialAccount['type'] ?? 'otra') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Institución opcional</label>
        <input type="text"
               name="institution"
               class="form-control"
               maxlength="100"
               placeholder="Ej: Bancolombia, Nequi"
               value="<?= e($financialAccount['institution'] ?? '') ?>">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Saldo inicial</label>
        <input type="number"
               name="initial_balance"
               class="form-control"
               step="0.01"
               value="<?= e($financialAccount['initial_balance'] ?? '0.00') ?>"
               required>
        <div class="form-text">Puede ser negativo para cuentas de crédito o deuda.</div>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Moneda</label>
        <select name="currency" class="form-select" required>
            <?php foreach (['COP', 'USD', 'EUR'] as $currency): ?>
                <option value="<?= $currency ?>" <?= ($financialAccount['currency'] ?? 'COP') === $currency ? 'selected' : '' ?>>
                    <?= $currency ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Estado</label>
        <select name="status" class="form-select" required>
            <option value="activa" <?= ($financialAccount['status'] ?? 'activa') === 'activa' ? 'selected' : '' ?>>Activa</option>
            <option value="inactiva" <?= ($financialAccount['status'] ?? 'activa') === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
        </select>
    </div>
</div>
