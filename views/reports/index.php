<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">

    <!-- ===== ENCABEZADO ===== -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">📄 Reportes financieros</h3>
        <p class="text-muted mb-0">
            Selecciona el período que deseas exportar en PDF
        </p>
    </div>

    <!-- ===== FORMULARIO ===== -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            <form action="<?= REPORTS_URL ?>/resumen_pdf.php" method="GET">

                <!-- Tipo de período -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Período del reporte
                    </label>

                    <select name="periodo" id="periodo"
                            class="form-select"
                            required
                            onchange="toggleFechas()">

                        <option value="">— Seleccionar —</option>
                        <option value="mes_actual">Mes actual</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="personalizado">Rango personalizado</option>
                    </select>
                </div>

                <!-- Fechas personalizadas -->
                <div id="fechasPersonalizadas" class="row g-3 d-none">

                    <div class="col-md-6">
                        <label class="form-label">Desde</label>
                        <input type="date"
                               name="desde"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Hasta</label>
                        <input type="date"
                               name="hasta"
                               class="form-control">
                    </div>

                </div>

                <!-- Botón -->
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">
                        📥 Generar PDF
                    </button>
                </div>

            </form>

        </div>
    </div>

    <div class="mt-3">
        <a href="<?= DASHBOARD_PATH ?>"
           class="btn btn-outline-secondary btn-sm">
           ⬅️ Volver al panel
        </a>
    </div>

</div>

<script>
function toggleFechas() {
    const periodo = document.getElementById('periodo').value;
    const fechas  = document.getElementById('fechasPersonalizadas');

    if (periodo === 'personalizado') {
        fechas.classList.remove('d-none');
    } else {
        fechas.classList.add('d-none');
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
