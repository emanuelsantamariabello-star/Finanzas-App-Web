<?php
require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/csrf.php';
requireAuth();
require_once '../../app/config/database.php';

$stmt = $pdo->prepare("
    SELECT username, email, occupation, created_at
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
        <h3 class="fw-bold mb-1">Mi perfil</h3>
        <p class="text-muted mb-0">
            Información personal de tu cuenta
        </p>
    </div>

    <!-- CARD PERFIL -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <div class="row g-4">

                <div class="col-md-6">
                    <small class="text-muted">Usuario</small>
                    <div class="fw-semibold">
                        <?= e($user['username']) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-muted">Correo electrónico</small>
                    <div class="fw-semibold">
                        <?= e($user['email']) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-muted">Ocupación</small>
                    <div class="fw-semibold">
                        <?= $user['occupation']
                            ? e($user['occupation'])
                            : 'No definida' ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-muted">Cuenta creada</small>
                    <div class="fw-semibold">
                        <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ACCIONES -->
    <div class="mt-4 d-flex flex-column flex-sm-row gap-2">

       <a href="<?= DASHBOARD_PATH ?>"
          class="btn btn-outline-secondary">
           ⬅ Volver
       </a>

       <a href="<?= PROFILE_EDIT_PATH ?>"
          class="btn btn-primary">
           ✏️ Editar perfil
       </a>

       <a href="<?= PROFILE_PASSWORD_PATH ?>"
          class="btn btn-outline-secondary">
          🔒 Cambiar contraseña
       </a>

</div>

</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
