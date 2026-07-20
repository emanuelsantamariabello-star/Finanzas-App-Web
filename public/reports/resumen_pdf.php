<?php
date_default_timezone_set('America/Bogota');
require_once '../../vendor/dompdf/autoload.inc.php';
require_once '../../app/config/app.php';
require_once '../../app/config/database.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/finance.php';
require_once '../../app/helpers/reports.php';
requireAuth();

use Dompdf\Dompdf; /* ========================= LEER PARÁMETROS ========================= */

$periodo = $_GET['periodo'] ?? null;
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;
$user_id = (int) $_SESSION['user_id'];
$reportPeriod = resolveReportPeriod($pdo, $user_id, $periodo, $desde, $hasta);

if (!$reportPeriod) {
    redirectError('Período inválido', REPORTS_PATH);
}

$fecha_inicio = $reportPeriod['inicio'];
$fecha_fin = $reportPeriod['fin'];
$nombre_periodo = $reportPeriod['nombre'];
/* ========================= TOTALES ========================= */
$totals = getFinancialTotals($pdo, $user_id, $fecha_inicio, $fecha_fin);
$ingresos = $totals['ingresos'];
$gastos = $totals['gastos'];
$saldo = $totals['saldo'];
$porcentaje_gastos = $ingresos > 0 ? ($gastos / $ingresos) * 100 : 0;
if ($porcentaje_gastos < 70) {
    $estado = 'Saludable';
} elseif ($porcentaje_gastos <= 90) {
    $estado = 'Ajustado';
} else {
    $estado = 'En riesgo';
} /* ========================= GASTOS DETALLADOS ========================= */
$gastos_detalle = getExpenseDetails($pdo, $user_id, $fecha_inicio, $fecha_fin);
$total_gastos = count($gastos_detalle);

/* ========================= COACH FINANCIERO PDF ========================= */

$coach = getCoachPercentages(getCoachBreakdown($pdo, $user_id, $fecha_inicio, $fecha_fin));
$totalCoach = $coach['total'];
$porcNecesarios = $coach['porc_necesarios'];
$porcGustos = $coach['porc_gustos'];

$mensajeCoach = null;

if ($totalCoach > 0) {

    if ($porcNecesarios >= 70) {
        $mensajeCoach = "Excelente disciplina financiera. Estás priorizando lo importante.";
    }

    elseif ($porcGustos >= 50) {
        $mensajeCoach = "Más de la mitad de tus gastos fueron gustos. Revisa si esto fue intencional.";
    }
}

$claseSaldo = $saldo >= 0 ? 'saldo' : 'saldo-negativo'; /* ========================= FECHA Y HORA ========================= */
$fecha_generacion = date('d/m/Y');
$hora_generacion = date('H:i'); /* ========================= HTML DEL PDF ========================= */
$inicio_formato = date('d/m/Y', strtotime($fecha_inicio));
$fin_formato = date('d/m/Y', strtotime($fecha_fin));
$nombre_periodo_safe = e($nombre_periodo);
$username_safe = e($_SESSION['username'] ?? '');
$logoPath = realpath(__DIR__ . '/../img/favicon.png');
$logoHtml = '';

if ($logoPath && extension_loaded('gd')) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logo = 'data:image/png;base64,' . $logoData;
    $logoHtml = "<img src='{$logo}' class='logo'>";
}

$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
body { font-family: DejaVu Sans; font-size: 12px; color: #212529; }
.header{
display:flex;
align-items:center;
border-bottom:2px solid #2563EB;
padding-bottom:10px;
margin-bottom:20px;
}

.logo{
width:32px;
margin-right:10px;
}

.title-area h1{
margin:0;
color:#2563EB;
font-size:20px;
}
h1 { text-align:center; margin-bottom:4px; }
.subtitle { text-align:center; color:#6c757d; margin-bottom:20px; }
.meta { margin-bottom:20px; }
.cards { display:flex; gap:12px; margin-bottom:25px; }
.card { padding:14px; border-radius:8px; width:32%; }
.ingresos { background:#e6f4ea; color:#0f5132; }
.gastos { background:#fdecea; color:#842029; }
.saldo { background:#e7f1ff; color:#084298; }
.label { font-size:11px; letter-spacing:1px; margin-bottom:6px; }
.amount { font-size:18px; font-weight:bold; }
.small { font-size:11px; margin-top:4px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:8px; border-bottom:1px solid #dee2e6; }
th { background:#f8f9fa; text-align:left; font-size:11px; }
footer { position:fixed; bottom:20px; width:100%; text-align:center; font-size:10px; color:#6c757d; }
</style>
</head>
<body>

<div class='header'>

{$logoHtml}

<div class='title-area'>
<h1>Finanzas App</h1>
<div class='subtitle'>Reporte financiero · {$nombre_periodo_safe}</div>
</div>

</div>

<div class='subtitle'>
Período: {$nombre_periodo_safe} ({$inicio_formato} – {$fin_formato})
</div>

<div class='meta'>
<strong>Usuario:</strong> {$username_safe}<br>
<strong>Generado:</strong> {$fecha_generacion} · {$hora_generacion}
</div>

<div class='cards'>
<div class='card ingresos'>
<div class='label'>INGRESOS</div>
<div class='amount'>$ " . number_format($ingresos, 2) . "</div>
</div>

<div class='card gastos'>
<div class='label'>GASTOS</div>
<div class='amount'>$ " . number_format($gastos, 2) . "</div>
<div class='small'>{$total_gastos} movimientos</div>
</div>

<div class='card {$claseSaldo}'>
<div class='label'>SALDO</div>
<div class='amount'>$ " . number_format($saldo, 2) . "</div>
</div>
</div>

<p class='small'>
<strong>Estado financiero:</strong> {$estado} · Gastos representan el " . number_format($porcentaje_gastos, 1) . "% de los ingresos
</p>

<h3>Coach financiero</h3>
<p>
<strong>" . number_format($porcNecesarios, 0) . "% Necesarios</strong> ·
<strong>" . number_format($porcGustos, 0) . "% Gustos</strong>
</p>
";

if ($mensajeCoach) {
    $html .= "<p><em>{$mensajeCoach}</em></p>";
}

$html .= "
<h3>Detalle de gastos</h3>
<table>
<thead>
<tr>
<th>Fecha</th>
<th>Ingreso</th>
<th>Descripción</th>
<th>Monto</th>
</tr>
</thead>
<tbody>
";

if ($total_gastos === 0) {
    $html .= "<tr><td colspan='4'>No se registraron gastos en este período.</td></tr>";
} else {
    foreach ($gastos_detalle as $g) {
        $tipo_safe = e($g['type']);
        $html .= "
        <tr>
        <td>" . date('d/m/Y', strtotime($g['expense_date'])) . "</td>
        <td>{$tipo_safe}</td>
        <td>" . e($g['note'] ?: '—') . "</td>
        <td>$ " . number_format($g['amount'], 2) . "</td>
        </tr>
        ";
    }
}

$html .= "
</tbody>
</table>

<footer>
Finanzas App · Reporte confidencial · {$fecha_generacion}
</footer>

</body>
</html>
";
/* ========================= GENERAR PDF ========================= */
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('reporte_financiero.pdf', ['Attachment' => true]);
exit;
