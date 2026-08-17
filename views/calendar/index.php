<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
require_once '../../app/helpers/financial_events.php';
requireAuth();
require_once '../../app/config/database.php';

$userId = (int) $_SESSION['user_id'];
$monthParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', (string) $monthParam)) {
    $monthParam = date('Y-m');
}

$monthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $monthParam . '-01') ?: new DateTimeImmutable('first day of this month');
$monthStart = $monthDate->modify('first day of this month');
$monthEnd = $monthDate->modify('last day of this month');
$calendarStart = $monthStart->modify('-' . ((int) $monthStart->format('N') - 1) . ' days');
$calendarEnd = $monthEnd->modify('+' . (7 - (int) $monthEnd->format('N')) . ' days');
$previousMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');
$today = date('Y-m-d');
$eventsByDate = [];
$calendarError = null;

try {
    $events = getFinancialEventsForRange($pdo, $userId, $calendarStart->format('Y-m-d'), $calendarEnd->format('Y-m-d'));
    $incomeOptions = getFinancialEventIncomeOptions($pdo, $userId);
} catch (PDOException $exception) {
    $events = [];
    $incomeOptions = [];
    $calendarError = 'El calendario financiero aún no tiene la migración aplicada.';
}

foreach ($events as $event) {
    $eventsByDate[$event['occurrence_date']][] = $event;
}

$typeLabels = financialEventTypeLabels();
$statusLabels = financialEventStatusLabels();
$recurrenceLabels = financialEventRecurrenceLabels();
$monthNames = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
$monthTitle = $monthNames[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y');

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container py-4 px-3 px-md-4">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1 fs-4 fs-md-3">
                Calendario financiero
            </h2>
            <p class="text-muted mb-0">
                Planifica pagos, ingresos esperados, cuotas, suscripciones y recordatorios.
            </p>
        </div>

        <div class="calendar-page-actions">
            <a class="btn btn-action-secondary btn-dashboard-return"
               href="<?= DASHBOARD_PATH ?>">
                <i class="bi bi-house-door"></i>
                <span>Volver al dashboard</span>
            </a>

            <button class="btn btn-action-primary d-inline-flex align-items-center justify-content-center gap-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createFinancialEventModal"
                    <?= $calendarError ? 'disabled' : '' ?>>
                <i class="bi bi-plus-lg"></i>
                <span>Nuevo evento</span>
            </button>
        </div>
    </div>

    <?php if ($calendarError): ?>
        <div class="alert alert-warning rounded-4">
            <?= e($calendarError) ?>
            Ejecuta la migración <code>database/migrations/2026_08_16_create_financial_events.sql</code>.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <div class="calendar-month-navigation">
                <a class="calendar-month-button"
                   href="<?= CALENDAR_PATH ?>?mes=<?= e($previousMonth) ?>"
                   aria-label="Ver mes anterior"
                   title="Mes anterior">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    <span>Mes anterior</span>
                </a>

                <div class="calendar-month-title text-center">
                    <h4 class="fw-bold mb-0"><?= e($monthTitle) ?></h4>
                    <small class="text-muted">Vista mensual</small>
                </div>

                <a class="calendar-month-button"
                   href="<?= CALENDAR_PATH ?>?mes=<?= e($nextMonth) ?>"
                   aria-label="Ver mes siguiente"
                   title="Mes siguiente">
                    <span>Mes siguiente</span>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="calendar-grid card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="calendar-weekday">Lun</div>
        <div class="calendar-weekday">Mar</div>
        <div class="calendar-weekday">Mié</div>
        <div class="calendar-weekday">Jue</div>
        <div class="calendar-weekday">Vie</div>
        <div class="calendar-weekday">Sáb</div>
        <div class="calendar-weekday">Dom</div>

        <?php for ($day = $calendarStart; $day <= $calendarEnd; $day = $day->modify('+1 day')): ?>
            <?php
            $dateKey = $day->format('Y-m-d');
            $isCurrentMonth = $day->format('m') === $monthStart->format('m');
            $dayEvents = $eventsByDate[$dateKey] ?? [];
            ?>
            <div class="calendar-day <?= $isCurrentMonth ? '' : 'calendar-day-muted' ?> <?= $dateKey === $today ? 'calendar-day-today' : '' ?>">
                <div class="calendar-day-number">
                    <?= $day->format('j') ?>
                </div>

                <?php if (empty($dayEvents)): ?>
                    <div class="calendar-empty d-none d-md-block">Sin eventos</div>
                <?php else: ?>
                    <div class="calendar-events">
                        <?php foreach ($dayEvents as $event): ?>
                            <button class="calendar-event calendar-event-<?= e($event['event_type']) ?> calendar-event-status-<?= e($event['status']) ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#eventModal<?= (int) $event['id'] ?>_<?= e(str_replace('-', '', $event['occurrence_date'])) ?>">
                                <span class="calendar-event-title"><?= e($event['title']) ?></span>
                                <?php if ($event['amount'] !== null): ?>
                                    <span class="calendar-event-amount">$<?= number_format($event['amount'], 0) ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mt-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Próximos eventos visibles</h5>
            <?php if (empty($events)): ?>
                <div class="text-center text-muted py-4">
                    No tienes eventos financieros en este mes.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($events, 0, 8) as $event): ?>
                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3"
                                data-bs-toggle="modal"
                                data-bs-target="#eventModal<?= (int) $event['id'] ?>_<?= e(str_replace('-', '', $event['occurrence_date'])) ?>">
                            <span>
                                <span class="fw-semibold"><?= e($event['title']) ?></span>
                                <span class="d-block text-muted small">
                                    <?= e($typeLabels[$event['event_type']] ?? 'Evento') ?> · <?= date('d/m/Y', strtotime($event['occurrence_date'])) ?>
                                </span>
                            </span>
                            <span class="badge rounded-pill bg-light text-dark border">
                                <?= e($statusLabels[$event['status']] ?? $event['status']) ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="createFinancialEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="<?= WEB_ROUTE ?>" class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nuevo evento financiero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create_financial_event">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <?php include __DIR__ . '/partials/form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar evento</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($events as $event): ?>
    <?php $modalId = 'eventModal' . (int) $event['id'] . '_' . str_replace('-', '', $event['occurrence_date']); ?>
    <?php $deleteModalId = 'delete' . ucfirst($modalId); ?>
    <div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold"><?= e($event['title']) ?></h5>
                        <div class="text-muted small">
                            <?= e($typeLabels[$event['event_type']] ?? 'Evento') ?> · <?= date('d/m/Y', strtotime($event['occurrence_date'])) ?>
                            <?= $event['event_time'] ? ' · ' . e(substr($event['event_time'], 0, 5)) : '' ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="<?= WEB_ROUTE ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_financial_event">
                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                        <?php
                        $financialEvent = getFinancialEvent($pdo, $userId, (int) $event['id']);
                        $financialEventFieldSuffix = $event['id'] . '_' . str_replace('-', '', $event['occurrence_date']);
                        include __DIR__ . '/partials/form_fields.php';
                        unset($financialEventFieldSuffix);
                        ?>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row justify-content-between gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </div>
                </form>

                <?php include __DIR__ . '/partials/occurrence_actions.php'; ?>

                <div class="px-3 pb-3">
                    <button type="button"
                            class="btn btn-outline-danger w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#<?= e($deleteModalId) ?>">
                        Eliminar evento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="<?= e($deleteModalId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Seguro que deseas eliminar el evento <strong><?= e($event['title']) ?></strong>?</p>
                    <small class="text-muted">Se eliminarán todas sus ocurrencias. Esta acción no se puede deshacer.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= WEB_ROUTE ?>">
                        <input type="hidden" name="action" value="delete_financial_event">
                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn btn-danger">Eliminar definitivamente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
