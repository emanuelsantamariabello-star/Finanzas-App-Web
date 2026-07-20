<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();
require_once '../../app/config/database.php';
require_once '../../app/helpers/finance.php';

include dirname(__DIR__) . '/layouts/header.php';

/* =========================
   EVOLUCIÓN DEL SALDO
   ========================= */
$userId = (int) $_SESSION['user_id'];
$evolucion = getBalanceEvolution($pdo, $userId);

$fechas = [];
$saldos = [];
$acumulado = 0;

foreach ($evolucion as $row) {
    $fechas[] = date('d M', strtotime($row['fecha']));
    $acumulado += (float) $row['saldo_dia'];
    $saldos[] = $acumulado;
}

$totals = getFinancialTotals($pdo, $userId);
$ingresos = $totals['ingresos'];
$gastos = $totals['gastos'];
$saldo = $totals['saldo'];
?>

<div class="container mt-4">
    <h3>📊 Análisis financiero</h3>
    <p class="text-muted">Resumen visual de tus finanzas</p>

    <div class="row g-4">
        <!-- Ingresos vs Gastos -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5>Ingresos vs Gastos</h5>
                    <canvas id="chartIngresosGastos"></canvas>
                </div>
            </div>
        </div>

        <!-- Saldo total -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5>Saldo total</h5>
                    <canvas id="chartSaldo"></canvas>
                </div>
            </div>
        </div>

        <!-- Evolución del saldo acumulado por fechas -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>📈 Evolución de saldo acumulado por fechas</h5>
                    <canvas id="chartEvolucion"></canvas>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <a href="<?= DASHBOARD_PATH ?>"
           class="btn btn-outline-secondary btn-sm">
            ⬅️ Volver al panel
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const ingresos = <?= $ingresos ?>;
    const gastos   = <?= $gastos ?>;
    const saldo    = <?= $saldo ?>;

    const fechas = <?= json_encode($fechas) ?>;
    const saldos = <?= json_encode($saldos) ?>;

    // Ingresos vs Gastos
    new Chart(document.getElementById('chartIngresosGastos'), {
        type: 'bar',
        data: {
            labels: ['Ingresos', 'Gastos'],
            datasets: [{
                data: [ingresos, gastos],
                backgroundColor: ['#198754', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '$' + value.toLocaleString()
                    }
                }
            }
        }
    });

    // Saldo total
    new Chart(document.getElementById('chartSaldo'), {
        type: 'doughnut',
        data: {
            labels: ['Saldo disponible', 'Gastos'],
            datasets: [{
                data: [saldo, gastos],
                backgroundColor: ['#0d6efd', '#adb5bd']
            }]
        },
        options: {
            cutout: '65%',
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Evolución de saldo acumulado por fechas
    new Chart(document.getElementById('chartEvolucion'), {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Saldo acumulado por fecha',
                data: saldos,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.15)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            }
        }
    });

});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
