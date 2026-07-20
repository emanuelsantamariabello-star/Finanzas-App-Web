<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/csrf.php';
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container">
  <div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-6 col-lg-5">

      <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4 p-md-5">

      <div class="text-center mb-4">
        <h3 class="fw-bold mb-1">Crear cuenta</h3>
        <p class="text-muted mb-0">
          Regístrate para empezar a gestionar tus finanzas
        </p>
      </div>

      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
          <?= e($_GET['error']) ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          <?= e($_GET['success']) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= WEB_ROUTE ?>">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text"
                 name="username"
                 class="form-control"
                 required>
          </div>

        <div class="mb-3">
          <label class="form-label">Correo electrónico</label>
          <input type="email"
                 name="email"
                 class="form-control"
                 required>
          </div>

        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <div class="input-group">
              <input type="password"
                     id="register_password"
                     name="password"
                     class="form-control"
                     required>

              <button type="button"
                      class="btn btn-outline-secondary"
                      onclick="togglePassword('register_password', this)">
                  <i class="bi bi-lock-fill"></i>
              </button>
          </div>
      </div>


        <div class="mb-4">
          <label class="form-label">Confirmar contraseña</label>
          <div class="input-group">
              <input type="password"
                     id="register_confirm_password"
                     name="confirm_password"
                     class="form-control"
                     required>

              <button type="button"
                      class="btn btn-outline-secondary"
                      onclick="togglePassword('register_confirm_password', this)">
                  <i class="bi bi-lock-fill"></i>
              </button>
          </div>
      </div>

        <button class="btn btn-primary w-100 py-2">
          Crear cuenta
        </button>

        <div class="text-center mt-4">
          <a href="<?= LOGIN_PATH ?>" class="text-decoration-none">
            Ya tengo cuenta
          </a>
        </div>
      </form>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
