<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();

require_once '../../app/config/database.php';

// ===== PASO A2: VALIDAR ACCESO A CREAR GASTO =====

$income_id = (int) ($_GET['income_id'] ?? 0);

if ($income_id <= 0) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no permitido']);
}

// Validar que el ingreso exista y sea del usuario
$stmt = $pdo->prepare("
    SELECT id
    FROM incomes
    WHERE id = :income_id
      AND user_id = :user_id
    LIMIT 1
");
$stmt->execute([
    'income_id' => $income_id,
    'user_id'   => $_SESSION['user_id']
]);

$income = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$income) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no autorizado']);
}

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
    <div class="mb-4 text-center">
    <h4 class="fw-bold mb-1">Agregar gasto</h4>
    <p class="text-muted mb-0">
        Registra un gasto asociado a este ingreso
    </p>
        </div>

    <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">

    <form method="POST" action="<?= WEB_ROUTE ?>">
        <input type="hidden" name="action" value="create_expense">
        <input type="hidden" name="income_id" value="<?= e($income_id) ?>">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
            <label class="form-label">Monto del gasto</label>
            <input type="number" name="amount" step="0.01" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="expense_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nota (opcional)</label>
            <input type="text" name="note" class="form-control">
        </div>
        
        <div class="mb-3">
            <label class="form-label">¿Este gasto fue?</label>

        <div class="form-check">
            <input class="form-check-input" 
                   type="radio" 
                   name="reflection_type" 
                   value="necesario" 
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
                💾 Guardar gasto
            </button>
</div>
    </form>
</div>
      </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
