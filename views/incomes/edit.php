<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

requireAuth();

// ===== VALIDAR ID =====
$id = (int) ($_GET['id'] ?? 0);

if (!$id || !is_numeric($id)) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no permitido']);
    exit;
}

// ===== VALIDAR PROPIEDAD DEL INGRESO =====
$stmt = $pdo->prepare("
    SELECT id, amount, type, income_date, note
    FROM incomes
    WHERE id = :id AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'id'       => $id,
    'user_id' => $_SESSION['user_id']
]);

$income = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$income) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no autorizado']);
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
    <div class="mb-4 text-center">
      <h4 class="fw-bold mb-1">Editar ingreso</h4>
      <p class="text-muted mb-0">
        Actualiza la información de este ingreso
    </p>
</div>

    <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">

    <form method="POST" action="<?= WEB_ROUTE ?>">
    <input type="hidden" name="action" value="update_income">
    <input type="hidden" name="id" value="<?= $income['id'] ?>">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

    <div class="mb-3">
      <label class="form-label">Monto</label>
      <input type="number"
             name="amount"
             step="0.01"
             class="form-control"
             value="<?= e($income['amount']) ?>"
             required>
    </div>

    <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select" required>
            <option value="quincenal" <?= $income['type']=='quincenal'?'selected':'' ?>>Quincenal</option>
            <option value="mensual" <?= $income['type']=='mensual'?'selected':'' ?>>Mensual</option>
            <option value="otro" <?= $income['type']=='otro'?'selected':'' ?>>Otro</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Fecha</label>
        <input type="date" name="income_date"
               class="form-control"
               value="<?= e($income['income_date']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Nota</label>
        <input type="text" name="note"
               class="form-control"
               value="<?= e($income['note']) ?>">
    </div>

    <div class="d-flex justify-content-between mt-4">
      <a href="<?= DASHBOARD_PATH ?>"
         class="btn btn-outline-secondary">
          ⬅️ Cancelar
     </a>

      <button type="submit" class="btn btn-warning px-4">
        💾 Guardar cambios
      </button>
</div>
</form>
</div>
   </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
