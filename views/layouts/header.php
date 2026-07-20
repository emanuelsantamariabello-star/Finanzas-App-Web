<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Finanzas App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_PATH ?>/public/img/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_PATH ?>/public/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/public/img/favicon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS propio -->
    <?php $cssPath = __DIR__ . '/../../public/css/styles.css'; ?>
    <link rel="stylesheet"
          href="<?= CSS_URL ?>/styles.css?v=<?= file_exists($cssPath) ? filemtime($cssPath) : time() ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body>

<?php if (isset($_SESSION['user_id'])): ?>
<?php
require_once __DIR__ . '/../../app/helpers/admin.php';

$dashboardNotifications = $dashboardNotifications ?? [];
$notificationCount = $notificationCount ?? count($dashboardNotifications);
?>

<nav class="navbar navbar-expand navbar-light bg-body-tertiary mb-4">
    <div class="container d-flex justify-content-between align-items-center">

        <!-- IDENTIDAD -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold"
           href="<?= DASHBOARD_PATH ?>">

            <img src="<?= BASE_PATH ?>/public/img/favicon.png"
                 alt="Finanzas App logo">

             <span class="fs-5 fw-semibold d-none d-sm-inline">
                   Finanzas App
             </span>

        </a>

        <div class="d-flex align-items-center gap-2">

            <!-- ===== REPORTES ===== -->
            <div class="dropdown">

                <!-- Desktop -->
                <button class="btn btn-outline-secondary d-none d-md-inline-flex align-items-center gap-2"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-graph-up-arrow"></i>
                    Reportes
                </button>

                <!-- Mobile -->
                <button class="btn btn-outline-secondary d-md-none"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-bar-chart"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item"
                           href="<?= BASE_PATH ?>/views/reports/graficas.php">
                            📈 Ver gráficas
                        </a>
                    </li>
                    <li>
                        <button class="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#modalReportePDF">
                            📑 Obtener reporte PDF
                        </button>
                    </li>
                </ul>
            </div>

            <!-- ===== NOTIFICACIONES ===== -->
            <div class="dropdown notification-dropdown">
                <button class="btn btn-outline-primary position-relative"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Ver notificaciones">
                    <i class="bi bi-bell-fill"></i>
                    <span id="financeNotificationBadge"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $notificationCount > 0 ? '' : 'd-none' ?>">
                        <?= $notificationCount ?>
                    </span>
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow notification-panel p-0">
                    <div class="px-3 py-2 border-bottom fw-semibold">
                        Notificaciones
                    </div>

                    <div id="financeNotificationList">
                        <?php if (empty($dashboardNotifications)): ?>
                            <div class="px-3 py-4 text-center text-muted small">
                                No tienes notificaciones activas por ahora.
                            </div>
                        <?php else: ?>
                            <?php foreach ($dashboardNotifications as $notification): ?>
                                <div class="notification-item px-3 py-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="notification-dot notification-dot-<?= e($notification['type'] ?? 'info') ?>"></span>
                                        <div>
                                            <div class="fw-semibold mb-1">
                                                <?= e($notification['title']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= e($notification['message']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="px-3 py-2 border-top <?= empty($dashboardNotifications) ? 'd-none' : '' ?>"
                         id="financeNotificationActions">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary w-100 mb-2"
                                id="enableBrowserNotifications">
                            Activar notificaciones del navegador
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary w-100"
                                id="markNotificationsRead">
                            Marcar como leído
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== PERFIL ===== -->
            <div class="dropdown">

                <!-- Desktop -->
                <button class="btn btn-outline-primary d-none d-md-inline-flex align-items-center gap-2"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill"></i>
                    <?= e($_SESSION['username']) ?>
                </button>

                <!-- Mobile -->
                <button class="btn btn-outline-primary d-md-none"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                    <li>
                        <a class="dropdown-item" href="<?= PROFILE_PATH ?>">
                            👤 Ver perfil
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item"
                           href="<?= BASE_PATH ?>/views/profile/edit.php">
                            ✏️ Editar perfil
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <?php if (currentUserIsAdmin()): ?>
                        <li>
                            <a class="dropdown-item" href="<?= ADMIN_NOTIFICATIONS_PATH ?>">
                                🔔 Gestionar novedades
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    <?php endif; ?>

                    <li class="px-3 pt-2 pb-1 text-muted small">
                        Tema
                    </li>

                    <li>
                        <button class="dropdown-item theme-option" data-theme="light">
                            <span>☀ Claro</span>
                            <i class="bi bi-check2 theme-check d-none"></i>
                        </button>
                    </li>

                    <li>
                        <button class="dropdown-item theme-option" data-theme="dark">
                            <span>🌙 Oscuro</span>
                            <i class="bi bi-check2 theme-check d-none"></i>
                        </button>
                    </li>

                    <li>
                        <button class="dropdown-item theme-option" data-theme="system">
                            <span>💻 Sistema</span>
                            <i class="bi bi-check2 theme-check d-none"></i>
                        </button>
                    </li>

                    <li>
                     <form method="POST" action="<?= BASE_PATH ?>/web.php" class="m-0">
                     <input type="hidden" name="action" value="logout">
                     <input type="hidden" name="_csrf" value="<?= $_SESSION['_csrf'] ?>">
                     <button type="submit" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>
                       Cerrar sesión
                    </button>
                </form>
            </li>
                  
                </ul>

            </div>

        </div>
    </div>
</nav>

<script>
    window.financeDashboardNotifications = <?= json_encode($dashboardNotifications, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    window.financeDashboardUserId = <?= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0 ?>;
    window.financeNotificationIcon = "<?= ASSETS_URL ?>/img/favicon.png";
    window.financeNotificationsEndpoint = "<?= BASE_PATH ?>/public/api/notifications.php";
</script>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= e($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php endif; ?>
