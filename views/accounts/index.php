<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
require_once '../../app/helpers/financial_accounts.php';
requireAuth();
require_once '../../app/config/database.php';

$userId = (int) $_SESSION['user_id'];
$accounts = [];
$accountsError = null;

try {
    $accounts = getFinancialAccounts($pdo, $userId);
} catch (PDOException $exception) {
    $accountsError = 'El módulo de cuentas financieras aún no tiene la migración aplicada.';
}

$accountTypeLabels = financialAccountTypeLabels();
$accountTypeIcons = [
    'banco' => 'bi-bank',
    'billetera_digital' => 'bi-phone',
    'efectivo' => 'bi-cash-stack',
    'ahorro' => 'bi-piggy-bank',
    'credito' => 'bi-credit-card',
    'otra' => 'bi-wallet2',
];
$activeTotals = [];

foreach ($accounts as $account) {
    if ($account['status'] !== 'activa') {
        continue;
    }

    $currency = $account['currency'];
    $activeTotals[$currency] = ($activeTotals[$currency] ?? 0) + (float) $account['initial_balance'];
}

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1 fs-4 fs-md-3">Mis cuentas</h2>
            <p class="text-muted mb-0">Organiza manualmente dónde administras tu dinero.</p>
        </div>

        <div class="financial-account-page-actions">
            <a href="<?= DASHBOARD_PATH ?>" class="btn btn-action-secondary btn-dashboard-return">
                <i class="bi bi-house-door"></i>
                <span>Volver al dashboard</span>
            </a>

            <button type="button"
                    class="btn btn-action-primary d-inline-flex align-items-center justify-content-center gap-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createFinancialAccountModal"
                    <?= $accountsError ? 'disabled' : '' ?>>
                <i class="bi bi-plus-lg"></i>
                <span>Nueva cuenta</span>
            </button>
        </div>
    </div>

    <?php if ($accountsError): ?>
        <div class="alert alert-warning rounded-4">
            <?= e($accountsError) ?>
            Ejecuta la migración <code>database/migrations/2026_08_17_create_financial_accounts.sql</code>.
        </div>
    <?php endif; ?>

    <?php if ($activeTotals): ?>
        <div class="row g-3 mb-4">
            <?php foreach ($activeTotals as $currency => $total): ?>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 financial-account-summary">
                        <div class="card-body">
                            <div class="small text-muted mb-1">Saldo inicial activo · <?= e($currency) ?></div>
                            <div class="fs-4 fw-bold"><?= e($currency) ?> <?= number_format($total, 2) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$accountsError && !$accounts): ?>
        <div class="card border-0 shadow-sm rounded-4 empty-state">
            <div class="card-body py-5 text-center">
                <i class="bi bi-wallet2 fs-1 text-primary"></i>
                <h3 class="h5 fw-bold mt-3">Aún no tienes cuentas registradas</h3>
                <p class="text-muted mb-3">Agrega una cuenta bancaria, billetera digital, efectivo u otra cuenta manual.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFinancialAccountModal">
                    Agregar primera cuenta
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($accounts): ?>
        <div class="row g-3">
            <?php foreach ($accounts as $account): ?>
                <?php $accountId = (int) $account['id']; ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm rounded-4 financial-account-card <?= $account['status'] === 'inactiva' ? 'financial-account-inactive' : '' ?>">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div class="financial-account-icon">
                                    <i class="bi <?= e($accountTypeIcons[$account['type']] ?? 'bi-wallet2') ?>"></i>
                                </div>
                                <span class="badge rounded-pill <?= $account['status'] === 'activa' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $account['status'] === 'activa' ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </div>

                            <h3 class="h5 fw-bold mb-1"><?= e($account['name']) ?></h3>
                            <div class="text-muted small mb-3">
                                <?= e($accountTypeLabels[$account['type']] ?? 'Otra') ?>
                                <?= $account['institution'] ? ' · ' . e($account['institution']) : '' ?>
                            </div>

                            <div class="financial-account-balance mb-4">
                                <div class="small text-muted">Saldo inicial registrado</div>
                                <div class="fs-4 fw-bold"><?= e($account['currency']) ?> <?= number_format((float) $account['initial_balance'], 2) ?></div>
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2 mt-auto">
                                <button type="button"
                                        class="btn btn-outline-primary flex-fill"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editFinancialAccountModal<?= $accountId ?>">
                                    Editar
                                </button>
                                <button type="button"
                                        class="btn btn-outline-danger flex-fill"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteFinancialAccountModal<?= $accountId ?>">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="createFinancialAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="<?= WEB_ROUTE ?>" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nueva cuenta financiera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create_financial_account">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <?php include __DIR__ . '/partials/form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cuenta</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($accounts as $account): ?>
    <?php $accountId = (int) $account['id']; ?>
    <div class="modal fade" id="editFinancialAccountModal<?= $accountId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="<?= WEB_ROUTE ?>" class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar cuenta financiera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_financial_account">
                    <input type="hidden" name="id" value="<?= $accountId ?>">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                    <?php $financialAccount = $account; ?>
                    <?php include __DIR__ . '/partials/form_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteFinancialAccountModal<?= $accountId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Seguro que deseas eliminar <strong><?= e($account['name']) ?></strong>?</p>
                    <small class="text-muted">Esta acción no afecta tus ingresos ni gastos actuales y no se puede deshacer.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= WEB_ROUTE ?>">
                        <input type="hidden" name="action" value="delete_financial_account">
                        <input type="hidden" name="id" value="<?= $accountId ?>">
                        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn btn-danger">Eliminar definitivamente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
