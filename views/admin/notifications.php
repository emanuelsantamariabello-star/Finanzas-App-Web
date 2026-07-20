<?php

require_once '../../app/config/app.php';
require_once '../../app/helpers/auth.php';
require_once '../../app/helpers/admin.php';
require_once '../../app/helpers/csrf.php';
requireAuth();
requireAdmin();
require_once '../../app/config/database.php';

$stmt = $pdo->query("
    SELECT id, title, message, type, starts_at, ends_at, is_active, created_at
    FROM system_notifications
    ORDER BY created_at DESC, id DESC
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container py-4 px-3 px-md-4">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Gestionar novedades</h3>
        <p class="text-muted mb-0">
            Crea avisos globales para todos los usuarios del sistema.
        </p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="POST" action="<?= WEB_ROUTE ?>">
                <input type="hidden" name="action" value="create_system_notification">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">Título</label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               maxlength="120"
                               required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select name="type" class="form-select" required>
                            <option value="info">Informativa</option>
                            <option value="success">Éxito</option>
                            <option value="warning">Advertencia</option>
                            <option value="danger">Importante</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Mensaje</label>
                        <textarea name="message"
                                  class="form-control"
                                  rows="3"
                                  required></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Visible desde</label>
                        <input type="datetime-local"
                               name="starts_at"
                               class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Visible hasta</label>
                        <input type="datetime-local"
                               name="ends_at"
                               class="form-control">
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input"
                           type="checkbox"
                           name="is_active"
                           value="1"
                           id="notificationActive"
                           checked>
                    <label class="form-check-label" for="notificationActive">
                        Publicar activa
                    </label>
                </div>

                <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary">
                        Guardar novedad
                    </button>

                    <a href="<?= DASHBOARD_PATH ?>" class="btn btn-outline-secondary">
                        Volver al panel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h5 class="fw-bold mb-1">Novedades creadas</h5>
                <p class="text-muted small mb-3">
                    Las novedades activas se muestran en la campana de todos los usuarios.
                </p>
            </div>

            <div class="table-responsive-md">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th class="d-none d-md-table-cell">Tipo</th>
                            <th class="d-none d-md-table-cell">Vigencia</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="fs-1 mb-3">🔔</div>
                                    <h6 class="mb-1">No hay novedades creadas</h6>
                                    <p class="text-muted mb-0">
                                        Crea la primera novedad para mostrarla en la campana.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= e($notification['title']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= e(mb_strimwidth($notification['message'], 0, 90, '...')) ?>
                                        </div>
                                    </td>

                                    <td class="d-none d-md-table-cell">
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            <?= e($notification['type']) ?>
                                        </span>
                                    </td>

                                    <td class="d-none d-md-table-cell small text-muted">
                                        <div>Desde: <?= $notification['starts_at'] ? e($notification['starts_at']) : 'Inmediata' ?></div>
                                        <div>Hasta: <?= $notification['ends_at'] ? e($notification['ends_at']) : 'Sin límite' ?></div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge rounded-pill <?= $notification['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $notification['is_active'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex flex-column flex-sm-row justify-content-sm-end gap-2">
                                            <form method="POST" action="<?= WEB_ROUTE ?>">
                                                <input type="hidden" name="action" value="toggle_system_notification">
                                                <input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
                                                <input type="hidden" name="is_active" value="<?= $notification['is_active'] ? 0 : 1 ?>">
                                                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                    <?= $notification['is_active'] ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>

                                            <form method="POST" action="<?= WEB_ROUTE ?>"
                                                  onsubmit="return confirm('¿Eliminar esta novedad?');">
                                                <input type="hidden" name="action" value="delete_system_notification">
                                                <input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
                                                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
