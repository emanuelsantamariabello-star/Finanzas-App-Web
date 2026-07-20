<?php
require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';


if (isset($_SESSION['user_id'])) {
    header("Location: " . DASHBOARD_PATH);
    exit;
}
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>


<div class="container">
  <div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-5 col-lg-4">
        
        <div class="text-center mb-4">
    <h2 class="fw-bold mb-2">
        Recibes tu ingreso.<br>
        Aquí decides si valió la pena.
    </h2>
    <p class="text-muted">
        Clasifica tus gastos y entiende si fueron necesarios o impulsivos.
    </p>
</div>

      <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4 p-md-5">

            <div class="text-center mb-4">
              <h3 class="fw-bold mb-1">Bienvenido</h3>
              <p class="text-muted mb-0">
                Inicia sesión para continuar
              </p>
            </div>
            
            <?php if (!empty($_GET['error'])): ?>
            <div id="loginAlert"
                 class="alert alert-danger text-center shadow-sm rounded-3 animate__animated animate__fadeInDown">
            <?= e($_GET['error']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($_GET['success'])): ?>
            <div id="loginSuccess"
                 class="alert alert-success text-center shadow-sm rounded-3 animate__animated animate__fadeInDown">
            <?= e($_GET['success']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= WEB_ROUTE ?>">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <div class="mb-3">
                  <label class="form-label">Correo electrónico</label>
                  <input type="email"
                         name="email"
                         class="form-control"
                         required>
                </div>

                <div class="mb-4">
                 <label class="form-label">Contraseña</label>
                 <div class="input-group">
                     <input type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required>

                     <button type="button"
                             class="btn btn-outline-secondary"
                             onclick="togglePassword('password', this)">
                          <i class="bi bi-lock-fill"></i>
                     </button>
                </div>
            </div>


                <button class="btn btn-primary w-100 py-2">
                  Entrar
                </button>

                <div class="text-center mt-4">
                  <a href="<?= REGISTER_PATH ?>" class="text-decoration-none">
                     ¿No tienes cuenta? Crear una
                  </a>
</div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    function autoDismiss(id) {
        const box = document.getElementById(id);
        if (box) {
            setTimeout(() => {
                box.classList.remove("animate__fadeInDown");
                box.classList.add("animate__fadeOutUp");

                setTimeout(() => {
                    box.remove();
                }, 500);
            }, 5000); // visible 5 segundos
        }
    }

    autoDismiss("loginAlert");
    autoDismiss("loginSuccess");

});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


