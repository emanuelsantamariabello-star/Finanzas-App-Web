<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">

    <!-- ENCABEZADO -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Cambiar contraseña</h3>
        <p class="text-muted mb-0">
            Actualiza tu contraseña para mantener tu cuenta segura
        </p>
    </div>

    <!-- CARD -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <form method="POST" action="<?= WEB_ROUTE ?>">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <!-- CONTRASEÑA ACTUAL -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Contraseña actual</label>
                    <div class="input-group">
                        <input type="password"
                               name="current_password"
                               id="current_password"
                               class="form-control"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary btn-password"
                                onclick="togglePassword('current_password', this)">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- NUEVA CONTRASEÑA -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Nueva contraseña</label>
                    <div class="input-group">
                        <input type="password"
                               name="new_password"
                               id="new_password"
                               class="form-control"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary btn-password"
                                onclick="togglePassword('new_password', this)">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- CONFIRMAR -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
                    <div class="input-group">
                        <input type="password"
                               name="confirm_password"
                               id="confirm_password"
                               class="form-control"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary btn-password"
                                onclick="togglePassword('confirm_password', this)">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- ACCIONES -->
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary">
                        🔐 Actualizar contraseña
                    </button>

                    <a href="<?= DASHBOARD_PATH ?>"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
