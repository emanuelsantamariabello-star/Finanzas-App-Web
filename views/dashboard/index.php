<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
requireAuth();
require_once '../../app/helpers/csrf.php';
require_once '../../app/config/database.php';
require_once '../../app/helpers/finance.php';
require_once '../../app/helpers/notifications.php';

// ===== PAGINACIÓN =====
$limit = 5; // ingresos por página
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$userId = (int) $_SESSION['user_id'];
$rawFilterStart = $_GET['desde'] ?? null;
$rawFilterEnd = $_GET['hasta'] ?? null;
$dateFilter = resolveFinanceDateFilter($rawFilterStart, $rawFilterEnd);
$filterStart = $dateFilter['inicio'];
$filterEnd = $dateFilter['fin'];
$isFiltered = $dateFilter['activo'];
$baseQueryParams = [];

if ($isFiltered) {
    $baseQueryParams = [
        'desde' => $filterStart,
        'hasta' => $filterEnd,
    ];
}

// ===== TOTAL DE INGRESOS (PARA PAGINACIÓN) =====
$total_registros = getIncomeCount($pdo, $userId, $filterStart, $filterEnd);

$total_paginas = ceil($total_registros / $limit);

// ===== CONSULTA INGRESOS =====
$incomes = getIncomeSummaries($pdo, $userId, $limit, $offset, $filterStart, $filterEnd);

// ===== TOTALES GENERALES =====
$totals = getFinancialTotals($pdo, $userId, $filterStart, $filterEnd);
$total_ingresos = $totals['ingresos'];
$total_gastos = $totals['gastos'];
$saldo_total = $totals['saldo'];

// ===== COACH FINANCIERO (MES ACTUAL) =====
$coach = getCoachPercentages(getCoachBreakdown($pdo, $userId, null, null, true));
$totalCoach = $coach['total'];
$porcNecesarios = $coach['porc_necesarios'];
$porcGustos = $coach['porc_gustos'];
$dashboardNotifications = getDashboardNotifications($pdo, $userId);
$notificationCount = count($dashboardNotifications);

include dirname(__DIR__) . '/layouts/header.php';

?>

<div class="container py-4 px-3 px-md-4">

<?php if (!empty($_GET['success'])): ?>
<div id="dashboardSuccess"
     class="alert alert-success text-center shadow-sm rounded-3 animate__animated animate__fadeInDown">
    <?= e($_GET['success']) ?>
</div>
<?php endif; ?>

    <!-- HEADER -->
    <div class="mb-4">
        <h2 class="fw-bold mb-2 fs-4 fs-md-3">
            Panel financiero
        </h2>
        <p class="text-muted mb-0">
            Bienvenido, <?= e($_SESSION['username']) ?> · Resumen general de tus finanzas
        </p>
    </div>

    <!-- FILTROS -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Desde</label>
                    <input type="date"
                           name="desde"
                           class="form-control"
                           value="<?= e($rawFilterStart ?? '') ?>">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Hasta</label>
                    <input type="date"
                           name="hasta"
                           class="form-control"
                           value="<?= e($rawFilterEnd ?? '') ?>">
                </div>

                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        Filtrar
                    </button>
                    <a href="<?= DASHBOARD_PATH ?>" class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

            <?php if ($isFiltered): ?>
                <div class="text-muted small mt-3">
                    Mostrando datos del <?= date('d/m/Y', strtotime($filterStart)) ?> al <?= date('d/m/Y', strtotime($filterEnd)) ?>.
                </div>
            <?php elseif (!empty($_GET['desde']) || !empty($_GET['hasta'])): ?>
                <div class="text-danger small mt-3">
                    Rango inválido. Selecciona fecha inicial y final válidas.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RESUMEN -->
    <div class="row g-4 mb-4">

        <div class="col-12 col-md-4">
            <div class="summary-card income h-100">
                <span class="label">Ingresos</span>
                <div class="amount">$<?= number_format($total_ingresos, 2) ?></div>
                <small class="hint"><?= $isFiltered ? 'Registrado en el rango' : 'Total registrado' ?></small>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="summary-card expense h-100">
                <span class="label">Gastos</span>
                <div class="amount">$<?= number_format($total_gastos, 2) ?></div>
                <small class="hint"><?= $isFiltered ? 'Ejecutado en el rango' : 'Total ejecutado' ?></small>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="summary-card balance h-100">
                <span class="label">Saldo</span>
                <div class="amount">$<?= number_format($saldo_total, 2) ?></div>
                <small class="hint"><?= $isFiltered ? 'Disponible en el rango' : 'Disponible' ?></small>
            </div>
        </div>

    </div>

    <hr class="my-4">
    
    <?php
    $tipoCoach = null;
    $mensajeCoach = null;

    if ($totalCoach > 0) {

        if ($porcNecesarios >= 70) {
            $tipoCoach = 'success';
            $mensajeCoach = "Excelente disciplina financiera. Estás priorizando lo importante.";
        }

        elseif ($porcGustos >= 50) {
            $tipoCoach = 'warning';
            $mensajeCoach = "Más de la mitad de tus gastos fueron gustos. Revisa si esto fue intencional.";
        }
    }
    ?>

    <?php if ($mensajeCoach): ?>
    <div class="alert alert-<?= $tipoCoach ?> rounded-4 mb-4 d-flex align-items-center gap-2">
        <span><?= $tipoCoach === 'success' ? '✅' : '⚠️' ?></span>
        <div>
            <?= $mensajeCoach ?>
    </div>
</div>
<?php endif; ?>

    <!-- INGRESOS -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h4 class="mb-0">Mis ingresos</h4>
        <a href="<?= INCOME_CREATE_PATH ?>" class="btn btn-action-primary">
            ➕ Agregar ingreso
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">

        <div class="card-body p-0">

            <p class="text-muted small mb-3 px-3">
                Cada ingreso muestra su saldo disponible después de gastos.
            </p>

            <div class="table-responsive-md">
                <table class="table align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th class="d-none d-md-table-cell">Tipo</th>
                            <th class="text-center">Ingreso</th>
                            <th class="text-center d-none d-md-table-cell">Gastos</th>
                            <th class="text-center">Saldo</th>
                            <th class="text-center text-md-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($incomes)): ?>

                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="fs-1 mb-3">📊</div>
                                    <h5 class="fw-semibold mb-1">
                                        <?= $isFiltered ? 'No hay ingresos en este rango' : 'Aún no tienes ingresos registrados' ?>
                                    </h5>
                                    <p class="text-muted mb-4">
                                        <?= $isFiltered ? 'Ajusta las fechas o limpia el filtro para ver más registros.' : 'Agrega tu primer ingreso para comenzar.' ?>
                                    </p>
                                    <?php if ($isFiltered): ?>
                                        <a href="<?= DASHBOARD_PATH ?>" class="btn btn-action-primary px-4">
                                            Limpiar filtro
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= INCOME_CREATE_PATH ?>" class="btn btn-action-primary px-4">
                                            ➕ Registrar primer ingreso
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($incomes as $income): ?>

                                <tr>

                                    <td class="text-muted small">
                                        <?= date('d M Y', strtotime($income['income_date'])) ?>
                                    </td>

                                    <td class="d-none d-md-table-cell">
                                        <span class="badge rounded-pill bg-light text-dark border px-3 py-1">
                                            <?= e(ucfirst($income['type'])) ?>
                                        </span>
                                    </td>

                                    <td class="text-center fw-semibold">
                                        $<?= number_format($income['ingreso_total'], 2) ?>
                                    </td>

                                    <td class="text-center text-danger fw-medium d-none d-md-table-cell">
                                        $<?= number_format($income['total_gastos'], 2) ?>
                                    </td>

                                    <td class="text-center fw-bold <?= $income['saldo'] < 0 ? 'saldo-negativo' : 'saldo-positivo' ?>">
                                        <?= $income['saldo'] < 0 
                                            ? '↓ $' . number_format(abs($income['saldo']), 2)
                                            : '↑ $' . number_format($income['saldo'], 2); ?>
                                    </td>

                                    <td class="text-center text-md-end">
                                        <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-stretch align-items-md-center gap-2">

                                            <a href="<?= EXPENSE_CREATE_PATH ?>?income_id=<?= $income['id'] ?>"
                                               class="btn btn-primary btn-sm">
                                                ➕ Gasto
                                            </a>

                                            <a href="<?= EXPENSES_INDEX_PATH ?>?income_id=<?= $income['id'] ?>"
                                               class="btn btn-primary btn-sm">
                                                📄 Ver gastos
                                            </a>

                                            <div class="dropdown">
                                                <button class="btn btn-action-menu"
                                                        type="button"
                                                        data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?= INCOME_EDIT_PATH ?>?id=<?= $income['id'] ?>">
                                                            ✏️ Editar ingreso
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal<?= $income['id'] ?>">
                                                            🗑 Eliminar ingreso
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </div>

        <?php if ($total_paginas > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">

                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($baseQueryParams, ['page' => $page - 1])) ?>">⬅️</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($baseQueryParams, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($baseQueryParams, ['page' => $page + 1])) ?>">➡️</a>
                        </li>

                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    </div>

</div>

</div>
<?php foreach ($incomes as $income): ?>
<div class="modal fade" id="deleteModal<?= $income['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">

      <div class="modal-header">
        <h5 class="modal-title">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        ¿Eliminar este ingreso?
        <br>
        <small class="text-muted">
        Se eliminarán también los gastos asociados. Esta acción no se puede deshacer.
        </small>
      </div>

      <div class="modal-footer">
        <button class="btn btn-action-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>

        <form action="<?= WEB_ROUTE ?>" method="POST" class="d-inline">
          <input type="hidden" name="action" value="delete_income">
          <input type="hidden" name="id" value="<?= $income['id'] ?>">
          <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

          <button type="submit" class="btn btn-action-danger">
            Sí, eliminar
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
<?php endforeach; ?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
