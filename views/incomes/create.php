<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
    <div class="mb-4 text-center">
      <h4 class="fw-bold mb-1">Agregar ingreso</h4>
      <p class="text-muted mb-0">
        Registra un nuevo ingreso en tu control financiero
      </p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">

    <form method="POST" action="<?= WEB_ROUTE ?>" class="mt-4">
        <input type="hidden" name="action" value="create_income">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label">Monto</label>
          <input type="number"
                 name="amount"
                 step="0.01"
                 class="form-control"
                 required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo de ingreso</label>
            <select name="type" class="form-select" required>
                <option value="">Seleccionar</option>
                <option value="quincenal">Quincenal</option>
                <option value="mensual">Mensual</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="income_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nota (opcional)</label>
            <input type="text" name="note" class="form-control">
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= DASHBOARD_PATH ?>"
             class="btn btn-outline-secondary">
              ⬅️ Cancelar
          </a>

          <button type="submit" class="btn btn-primary px-4">
            💾 Guardar ingreso
          </button>
        </div>
    </form>
</div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
