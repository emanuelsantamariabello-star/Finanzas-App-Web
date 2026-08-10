<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/csrf.php';
$googleClientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? ''));
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

        <?php if ($googleClientId !== ''): ?>
          <div class="auth-divider">
            <span>o</span>
          </div>

          <div class="d-flex justify-content-center">
            <div id="googleRegisterButton"></div>
          </div>
        <?php endif; ?>

        <div class="text-center mt-4">
          <a href="<?= LOGIN_PATH ?>" class="text-decoration-none">
            Ya tengo cuenta
          </a>
        </div>
      </form>

    </div>
  </div>
</div>

<?php if ($googleClientId !== ''): ?>
<div class="modal fade" id="googlePasswordModal" tabindex="-1" aria-labelledby="googlePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="<?= WEB_ROUTE ?>" class="modal-content rounded-4">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-bold" id="googlePasswordModalLabel">
            Crea tu contraseña de seguridad
          </h5>
          <p class="text-muted small mb-0">
            Google confirma tu identidad. Esta contraseña protege cambios críticos dentro de Finanzas App.
          </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="action" value="google_register">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="credential" id="google_register_credential">

        <div class="mb-3">
          <label class="form-label">Contraseña de seguridad</label>
          <div class="input-group">
            <input type="password"
                   id="google_register_password"
                   name="password"
                   class="form-control"
                   minlength="8"
                   required>
            <button type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword('google_register_password', this)">
              <i class="bi bi-lock-fill"></i>
            </button>
          </div>
          <div class="form-text">
            Mínimo 8 caracteres. Podrás usarla también para iniciar sesión manualmente.
          </div>
        </div>

        <div class="mb-0">
          <label class="form-label">Confirmar contraseña</label>
          <div class="input-group">
            <input type="password"
                   id="google_register_confirm_password"
                   name="confirm_password"
                   class="form-control"
                   minlength="8"
                   required>
            <button type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword('google_register_confirm_password', this)">
              <i class="bi bi-lock-fill"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          Crear cuenta con Google
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
function renderGoogleRegisterButton(attempts = 0) {
    const button = document.getElementById('googleRegisterButton');
    const credentialInput = document.getElementById('google_register_credential');
    const modalElement = document.getElementById('googlePasswordModal');

    if (!button || !credentialInput || !modalElement) {
        return;
    }

    if (!window.google) {
        if (attempts < 20) {
            setTimeout(function () {
                renderGoogleRegisterButton(attempts + 1);
            }, 150);
        }
        return;
    }

    google.accounts.id.initialize({
        client_id: "<?= e($googleClientId) ?>",
        callback: function (response) {
            if (!response || !response.credential) {
                return;
            }

            credentialInput.value = response.credential;
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        }
    });

    google.accounts.id.renderButton(button, {
        theme: 'outline',
        size: 'large',
        text: 'continue_with',
        shape: 'pill',
        width: 320
    });
}

window.addEventListener('load', function () {
    renderGoogleRegisterButton();
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
