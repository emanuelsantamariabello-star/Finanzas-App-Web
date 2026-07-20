<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
requireAuth();
require_once '../../app/helpers/csrf.php';
require_once '../../app/config/database.php';

// ===== PASO A: VALIDAR ACCESO A LA VISTA =====

// 1. Validar que venga income_id
$income_id = (int) ($_GET['income_id'] ?? 0);

if ($income_id <= 0) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no permitido']);
}

// 2. Validar que el ingreso exista y sea del usuario
$stmt = $pdo->prepare("
    SELECT id, amount, type, income_date
    FROM incomes
    WHERE id = :income_id AND user_id = :user_id
");

$stmt->execute([
    'income_id' => $income_id,
    'user_id'   => $_SESSION['user_id']
]);

$income = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$income) {
    redirect(DASHBOARD_PATH, ['error' => 'Acceso no autorizado']);
}



// Obtener gastos del ingreso
$stmt = $pdo->prepare("
    SELECT id, amount, expense_date, note
    FROM expenses
    WHERE income_id = :income_id
    ORDER BY expense_date DESC
");

$stmt->execute(['income_id' => $income_id]);

$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">
    <div class="mx-auto" style="max-width: 1100px;">

<div class="mb-4">
    <h3 class="fw-bold mb-1">Gastos del ingreso</h3>
    <p class="text-muted mb-0">
        Detalle de gastos asociados a este ingreso
    </p>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">

        <div class="row g-3">
            <div class="col-md-4">
                <small class="text-muted">Tipo</small>
                <div class="fw-semibold"><?= e(ucfirst($income['type'])) ?></div>
            </div>

            <div class="col-md-4">
                <small class="text-muted">Fecha</small>
                <div class="fw-semibold"><?= e($income['income_date']) ?></div>
            </div>

            <div class="col-md-4">
                <small class="text-muted">Monto</small>
                <div class="fw-semibold">$<?= number_format($income['amount'], 2) ?></div>
            </div>
        </div>

    </div>
</div>

    <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
    <a href="<?= EXPENSE_CREATE_PATH ?>?income_id=<?= $income_id ?>"
       class="btn btn-primary">
        ➕ Agregar gasto
    </a>

    <a href="<?= DASHBOARD_PATH ?>"
       class="btn btn-outline-secondary">
        ⬅️ Volver al panel
    </a>
</div>

    <div class="table-responsive-md">
        <table class="table align-middle">
        <thead class="table-light">
        <tr>
            <th>Fecha</th>
            <th class="text-center">Monto</th>
            <th>Nota</th>
            <th class="text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
            <?php if (empty($expenses)): ?>
                <tr>
                  <td colspan="4" class="text-center py-5">
                      <div class="fs-1 mb-3">🧾</div>
                      <h6 class="mb-1">Aún no hay gastos registrados</h6>
                      <p class="text-muted mb-3">
                         Este ingreso aún no tiene gastos asociados.
                      </p>
                      <a href="<?= EXPENSE_CREATE_PATH ?>?income_id=<?= $income_id ?>"
                         class="btn btn-primary btn-sm">
                          ➕ Agregar primer gasto
                      </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= e($expense['expense_date']) ?></td>

                        <td class="text-center text-danger fw-semibold">
                            $<?= number_format($expense['amount'], 2) ?>
                        </td>

                        <td><?= e($expense['note'] ?: '—') ?></td>

                        <td class="text-end">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-end gap-2">
                                <a href="<?= EXPENSE_EDIT_PATH ?>?id=<?= $expense['id'] ?>&income_id=<?= $income_id ?>"
                                   class="btn btn-sm btn-outline-warning">
                                   Editar
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteExpenseModal<?= $expense['id'] ?>">
                                        Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                
                <!-- Modal eliminar gasto -->
                <div class="modal fade"
                     id="deleteExpenseModal<?= $expense['id'] ?>"
                     tabindex="-1"
                     aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">

                <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">
                    Confirmar eliminación
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                     ¿Seguro que deseas eliminar este gasto?
                </div>

                <div class="modal-footer border-0">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancelar
                </button>

                <form action="<?= WEB_ROUTE ?>" method="POST">
                <input type="hidden" name="action" value="delete_expense">
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <input type="hidden" name="income_id" value="<?= $income_id ?>">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <button type="submit"
                        class="btn btn-danger">
                        Sí, eliminar
                </button>
            </form>

      </div>

    </div>
  </div>
</div>
                
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

</div>
   
   </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
