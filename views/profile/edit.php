<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();

require_once '../../app/config/database.php';

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("
    SELECT username, email, occupation
    FROM users
    WHERE id = :id
");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container mt-4">

    <!-- ENCABEZADO -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Editar perfil</h3>
        <p class="text-muted mb-0">
            Actualiza tu información personal
        </p>
    </div>

    <!-- FORM CARD -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <form method="POST" action="<?= WEB_ROUTE ?>">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Usuario</label>
                        <input type="text"
                               name="username"
                               class="form-control"
                               value="<?= e($user['username']) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Correo electrónico</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="<?= e($user['email']) ?>"
                               required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Ocupación</label>
                        <input type="text"
                               name="occupation"
                               class="form-control"
                               placeholder="Ej: Estudiante, Independiente…"
                               value="<?= e($user['occupation'] ?? '') ?>">
                    </div>

                </div>

                <!-- ACCIONES -->
                <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar cambios
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
