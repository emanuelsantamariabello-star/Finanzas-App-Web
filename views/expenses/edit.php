<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
require_once '../../app/config/database.php';

requireAuth();

// ===== VALIDAR IDS =====
$id        = (int) ($_GET['id'] ?? 0);
$income_id = (int) ($_GET['income_id'] ?? 0);

if ($id <= 0 || $income_id <= 0) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no permitido']);
}

// ===== VALIDAR QUE EL GASTO SEA DEL USUARIO =====
$stmt = $pdo->prepare("
    SELECT e.id, e.amount, e.expense_date, e.note, e.reflection_type
    FROM expenses e
    INNER JOIN incomes i ON e.income_id = i.id
    WHERE e.id = :id
      AND e.income_id = :income_id
      AND i.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'id'        => $id,
    'income_id'=> $income_id,
    'user_id'  => $_SESSION['user_id']
]);

$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no autorizado']);
}

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
    <div class="mb-4 text-center">
        <h4 class="fw-bold mb-1">Editar gasto</h4>
        <p class="text-muted mb-0">
            Actualiza la información de este gasto
        </p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">

    <form method="POST" action="<?= WEB_ROUTE ?>">
        <input type="hidden" name="action" value="update_expense">
        <input type="hidden" name="id" value="<?= $expense['id'] ?>">
        <input type="hidden" name="income_id" value="<?= $income_id ?>">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
             <label class="form-label">Monto</label>
             <input type="number"
                    name="amount"
                    step="0.01"
                    class="form-control"
                    value="<?= e($expense['amount']) ?>"
                    required>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="expense_date"
                   class="form-control"
                   value="<?= e($expense['expense_date']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nota</label>
            <input type="text" name="note"
                   class="form-control"
                   value="<?= e($expense['note']) ?>">
        </div>
        
        <div class="mb-3">
            <label class="form-label">¿Este gasto fue?</label>

        <div class="form-check">
            <input class="form-check-input"
                   type="radio"
                   name="reflection_type"
                   value="necesario"
                   <?= $expense['reflection_type'] === 'necesario' ? 'checked' : '' ?>
                   required>
            <label class="form-check-label">
               Necesario
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input"
                   type="radio"
                   name="reflection_type"
                   value="gusto"
                   <?= $expense['reflection_type'] === 'gusto' ? 'checked' : '' ?>
                   required>
            <label class="form-check-label">
               Gusto
            </label>
        </div>
    </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= EXPENSES_PATH ?>?income_id=<?= $income_id ?>"
               class="btn btn-outline-secondary">
                ⬅️ Cancelar
            </a>

            <button type="submit" class="btn btn-primary px-4">
                💾 Guardar cambios
            </button>
        </div>
    </form>
</div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
