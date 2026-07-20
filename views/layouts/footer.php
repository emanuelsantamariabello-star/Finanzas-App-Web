<?php if (isset($_SESSION['user_id'])): ?>
<!-- ===== MODAL REPORTE PDF ===== -->
<div class="modal fade" id="modalReportePDF" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="GET"
          action="<?= REPORTS_URL ?>/resumen_pdf.php"
          class="modal-content rounded-4">

      <div class="modal-header">
        <h5 class="modal-title">Seleccionar periodo para reporte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label">Periodo</label>
        <select name="periodo" class="form-select" required>
          <option value="">Seleccionar...</option>
          <option value="mes_actual">Mes actual</option>
          <option value="mes_anterior">Mes anterior</option>
          <option value="todo">Todo el historial</option>
        </select>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          Generar PDF
        </button>
      </div>

    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php $jsPath = __DIR__ . '/../../public/js/main.js'; ?>
<script src="<?= JS_URL ?>/main.js?v=<?= file_exists($jsPath) ? filemtime($jsPath) : time() ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function autoDismiss(id) {
        const box = document.getElementById(id);
        if (!box) return;

        setTimeout(() => {
            box.classList.remove("animate__fadeInDown");
            box.classList.add("animate__fadeOutUp");

            setTimeout(() => {
                box.remove();
            }, 500);
        }, 5000);
    }

    autoDismiss("dashboardSuccess");
    autoDismiss("loginAlert");
    autoDismiss("loginSuccess");
});
</script>

</body>
</html>
