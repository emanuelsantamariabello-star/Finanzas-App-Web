<?php
/* ======================================================
   CONFIG & HELPERS
   ====================================================== */

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/helpers/redirect.php';
require_once __DIR__ . '/app/helpers/request.php';
require_once __DIR__ . '/app/helpers/validator.php';
require_once __DIR__ . '/app/helpers/csrf.php';
require_once __DIR__ . '/app/helpers/admin.php';
require_once __DIR__ . '/app/config/database.php';

/* ======================================================
   ACCIÓN
   ====================================================== */

$action = action();

if (!$action) {
    redirectError('Acción inválida');
}

/* ======================================================
   ACCIONES POST (SEGURIDAD)
   ====================================================== */

$acciones_post = [
    'register',
    'login',
    'logout',
    'update_profile',
    'change_password',
    'create_income',
    'update_income',
    'delete_income',
    'create_expense',
    'update_expense',
    'delete_expense',
    'create_system_notification',
    'toggle_system_notification',
    'delete_system_notification',
];

/* ======================================================
   PROTECCIÓN HTTP + CSRF (ANTES DE TODO)
   ====================================================== */

requirePost($action, $acciones_post);

if (in_array($action, $acciones_post, true)) {
    verifyCsrf();
}

/* ======================================================
   DISPATCHERS
   ====================================================== */

// INGRESOS
$incomeActions = [
    'create_income' => 'createIncome',
    'update_income' => 'updateIncome',
    'delete_income' => 'deleteIncome',
];

if (isset($incomeActions[$action])) {
    call_user_func($incomeActions[$action], $pdo);
    exit;
}

// GASTOS
$expenseActions = [
    'create_expense' => 'createExpense',
    'update_expense' => 'updateExpense',
    'delete_expense' => 'deleteExpense',
];

if (isset($expenseActions[$action])) {
    call_user_func($expenseActions[$action], $pdo);
    exit;
}

// ADMIN
$adminActions = [
    'create_system_notification' => 'createSystemNotification',
    'toggle_system_notification' => 'toggleSystemNotification',
    'delete_system_notification' => 'deleteSystemNotification',
];

if (isset($adminActions[$action])) {
    call_user_func($adminActions[$action], $pdo);
    exit;
}

/* ======================================================
   AUTH
   ====================================================== */

if ($action === 'register') {

    $username = trim(post('username', ''));
    $email    = trim(post('email', ''));
    $password = post('password', '', false);
    $confirm  = post('confirm_password', '');

    if (!$username || !$email || !$password || !$confirm) {
        redirectError('Todos los campos son obligatorios');
    }

    if ($password !== $confirm) {
        redirectError('Las contraseñas no coinciden');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectError('Email inválido');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
    redirectError('El correo ya está registrado', REGISTER_PATH);
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password)
        VALUES (:username, :email, :password)
    ");

    try {

    $stmt->execute([
        'username' => $username,
        'email'    => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ]);

    } catch (PDOException $e) {

    if ($e->getCode() == 23000) {
        redirectError('El correo ya está registrado', REGISTER_PATH);
    }

    redirectError('Ocurrió un error inesperado. Intenta nuevamente.');
    }
    
    require_once __DIR__ . '/app/helpers/mailer.php';
    sendWelcomeEmail($email, $username);

    redirect(LOGIN_PATH, ['success' => 'Cuenta creada correctamente']);
}

if ($action === 'login') {

    $email    = trim(post('email', ''));
    $password = post('password', '', false);

    if (!$email || !$password) {
        redirectError('Todos los campos son obligatorios');
    }

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
    redirect(LOGIN_PATH, [
        'error' => 'Credenciales incorrectas. Verifica tu correo y contraseña.'
    ]);
}
    // Evitar fijar la sesión antigua: regenerar ID al iniciar sesión
    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    redirect(DASHBOARD_PATH, [
    'success' => 'Bienvenido nuevamente, ' . $user['username'] . '.'
]);

}

if ($action === 'logout') {

    session_unset();

    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookieParams['path'] ?? '/',
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => $cookieParams['secure'] ?? false,
            'httponly' => $cookieParams['httponly'] ?? true,
            'samesite' => $cookieParams['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();

    redirect(LOGIN_PATH);
}

/* ======================================================
   PERFIL
   ====================================================== */

if ($action === 'update_profile') {

    requireLogin();

    $username   = trim(post('username', ''));
    $email      = trim(post('email', ''));
    $occupation = trim(post('occupation', ''));

    if (!$username || !$email) {
        redirectError('Campos obligatorios');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectError('Email inválido');
    }

    $stmt = $pdo->prepare("
        SELECT id FROM users WHERE email = :email AND id != :id
    ");
    $stmt->execute([
        'email' => $email,
        'id'    => $_SESSION['user_id']
    ]);

    if ($stmt->fetch()) {
        redirectError('Email ya en uso');
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email,
            occupation = :occupation
        WHERE id = :id
    ");

    $stmt->execute([
        'username'   => $username,
        'email'      => $email,
        'occupation' => $occupation,
        'id'         => $_SESSION['user_id']
    ]);

    $_SESSION['username'] = $username;

    redirect(PROFILE_PATH, ['success' => 'Perfil actualizado']);
}

if ($action === 'change_password') {

    requireLogin();

    $current = post('current_password', '', false);
    $new     = post('new_password', '', false);
    $confirm = post('confirm_password', '', false);
    $back    = BASE_PATH . '/views/profile/password.php';

    if (!$current || !$new || !$confirm) {
        redirectError('Todos los campos son obligatorios', $back);
    }

    if ($new !== $confirm) {
        redirectError('Las contraseñas no coinciden', $back);
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        redirectError('Contraseña actual incorrecta', $back);
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = :password
        WHERE id = :id
    ");

    $stmt->execute([
        'password' => password_hash($new, PASSWORD_DEFAULT),
        'id'       => $_SESSION['user_id']
    ]);

    redirect(PROFILE_PATH, ['success' => 'Contraseña actualizada']);
}

/* ======================================================
   FUNCIONES — INGRESOS
   ====================================================== */

function createIncome(PDO $pdo) {
    requireLogin();

    $stmt = $pdo->prepare("
        INSERT INTO incomes (user_id, amount, type, income_date, note)
        VALUES (:user_id, :amount, :type, :income_date, :note)
    ");

    $stmt->execute([
        'user_id'     => $_SESSION['user_id'],
        'amount'      => post('amount'),
        'type'        => post('type'),
        'income_date' => post('income_date'),
        'note'        => trim(post('note', ''))
    ]);

    redirect(DASHBOARD_PATH, ['success' => 'Ingreso agregado']);
}

function updateIncome(PDO $pdo) {
    requireLogin();

    $id = (int) post('id');
    validateIncomeOwnership($pdo, $id, $_SESSION['user_id']);

    $stmt = $pdo->prepare("
        UPDATE incomes
        SET amount = :amount,
            type = :type,
            income_date = :income_date,
            note = :note
        WHERE id = :id
    ");

    $stmt->execute([
        'amount'      => post('amount'),
        'type'        => post('type'),
        'income_date' => post('income_date'),
        'note'        => trim(post('note', '')),
        'id'          => $id
    ]);

    redirect(DASHBOARD_PATH, ['success' => 'Ingreso actualizado']);
}

function deleteIncome(PDO $pdo) {
    requireLogin();

    $id = (int) post('id');
    validateIncomeOwnership($pdo, $id, $_SESSION['user_id']);

    $pdo->prepare("DELETE FROM incomes WHERE id = :id")
        ->execute(['id' => $id]);

    redirect(DASHBOARD_PATH, ['success' => 'Ingreso eliminado']);
}

/* ======================================================
   FUNCIONES — GASTOS
   ====================================================== */

function createExpense(PDO $pdo) {
    requireLogin();

    $income_id = (int) post('income_id');
    validateIncomeOwnership($pdo, $income_id, $_SESSION['user_id']);

    $reflection_type = post('reflection_type');

    if (!in_array($reflection_type, ['necesario', 'gusto'], true)) {
        redirectError('Tipo de gasto inválido');
    }

    $pdo->prepare("
        INSERT INTO expenses (income_id, amount, expense_date, note, reflection_type)
        VALUES (:income_id, :amount, :expense_date, :note, :reflection_type)
    ")->execute([
        'income_id'      => $income_id,
        'amount'         => post('amount'),
        'expense_date'   => post('expense_date'),
        'note'           => trim(post('note', '')),
        'reflection_type'=> $reflection_type
    ]);

    redirect(EXPENSES_PATH, [
        'income_id' => $income_id,
        'success'   => 'Gasto agregado'
    ]);
}

function updateExpense(PDO $pdo) {
    requireLogin();

    $id        = (int) post('id');
    $income_id = (int) post('income_id');

    validateExpenseOwnership($pdo, $id, $_SESSION['user_id']);

    $reflection_type = post('reflection_type');

    if (!in_array($reflection_type, ['necesario', 'gusto'], true)) {
        redirectError('Tipo de gasto inválido');
    }

    $pdo->prepare("
        UPDATE expenses
        SET amount = :amount,
            expense_date = :expense_date,
            note = :note,
            reflection_type = :reflection_type
        WHERE id = :id
    ")->execute([
        'amount'          => post('amount'),
        'expense_date'    => post('expense_date'),
        'note'            => trim(post('note', '')),
        'reflection_type' => $reflection_type,
        'id'              => $id
    ]);

    redirect(EXPENSES_PATH, [
        'income_id' => $income_id,
        'success'   => 'Gasto actualizado'
    ]);
}

function deleteExpense(PDO $pdo) {
    requireLogin();

    $id        = (int) post('id');
    $income_id = (int) post('income_id');

    validateExpenseOwnership($pdo, $id, $_SESSION['user_id']);

    $pdo->prepare("DELETE FROM expenses WHERE id = :id")
        ->execute(['id' => $id]);

    redirect(EXPENSES_PATH, [
        'income_id' => $income_id,
        'success'   => 'Gasto eliminado'
    ]);
}

/* ======================================================
   FUNCIONES — ADMIN NOVEDADES
   ====================================================== */

function normalizeNotificationDate(?string $date): ?string
{
    if (!$date) {
        return null;
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d\TH:i', $date);

    if (!$parsedDate || $parsedDate->format('Y-m-d\TH:i') !== $date) {
        redirectError('Fecha de novedad inválida', ADMIN_NOTIFICATIONS_PATH);
    }

    return $parsedDate->format('Y-m-d H:i:s');
}

function createSystemNotification(PDO $pdo): void
{
    requireAdmin();

    $title = trim(post('title', ''));
    $message = trim(post('message', ''));
    $type = post('type', 'info');
    $startsAt = normalizeNotificationDate(post('starts_at', null));
    $endsAt = normalizeNotificationDate(post('ends_at', null));
    $isActive = post('is_active', '0') === '1' ? 1 : 0;

    if (!$title || !$message) {
        redirectError('Título y mensaje son obligatorios', ADMIN_NOTIFICATIONS_PATH);
    }

    if (!in_array($type, ['info', 'success', 'warning', 'danger'], true)) {
        redirectError('Tipo de novedad inválido', ADMIN_NOTIFICATIONS_PATH);
    }

    if ($startsAt && $endsAt && $startsAt > $endsAt) {
        redirectError('La fecha final no puede ser menor que la fecha inicial', ADMIN_NOTIFICATIONS_PATH);
    }

    $stmt = $pdo->prepare("
        INSERT INTO system_notifications (title, message, type, starts_at, ends_at, is_active)
        VALUES (:title, :message, :type, :starts_at, :ends_at, :is_active)
    ");

    $stmt->execute([
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'is_active' => $isActive,
    ]);

    redirect(ADMIN_NOTIFICATIONS_PATH, ['success' => 'Novedad creada']);
}

function toggleSystemNotification(PDO $pdo): void
{
    requireAdmin();

    $id = (int) post('id');
    $isActive = post('is_active', '0') === '1' ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE system_notifications
        SET is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        'is_active' => $isActive,
        'id' => $id,
    ]);

    redirect(ADMIN_NOTIFICATIONS_PATH, [
        'success' => $isActive ? 'Novedad activada' : 'Novedad desactivada'
    ]);
}

function deleteSystemNotification(PDO $pdo): void
{
    requireAdmin();

    $id = (int) post('id');

    $stmt = $pdo->prepare("DELETE FROM system_notifications WHERE id = :id");
    $stmt->execute(['id' => $id]);

    redirect(ADMIN_NOTIFICATIONS_PATH, ['success' => 'Novedad eliminada']);
}


