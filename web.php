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
require_once __DIR__ . '/app/helpers/google_auth.php';
require_once __DIR__ . '/app/helpers/financial_events.php';
require_once __DIR__ . '/app/helpers/financial_accounts.php';
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
    'google_register',
    'login',
    'google_login',
    'logout',
    'update_profile',
    'change_password',
    'create_income',
    'update_income',
    'delete_income',
    'create_expense',
    'update_expense',
    'delete_expense',
    'create_financial_event',
    'update_financial_event',
    'delete_financial_event',
    'update_financial_occurrence_status',
    'register_financial_occurrence_income',
    'register_financial_occurrence_expense',
    'create_financial_account',
    'update_financial_account',
    'delete_financial_account',
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

// CALENDARIO FINANCIERO
$financialEventActions = [
    'create_financial_event' => 'handleCreateFinancialEvent',
    'update_financial_event' => 'handleUpdateFinancialEvent',
    'delete_financial_event' => 'handleDeleteFinancialEvent',
    'update_financial_occurrence_status' => 'handleUpdateFinancialOccurrenceStatus',
    'register_financial_occurrence_income' => 'handleRegisterFinancialOccurrenceIncome',
    'register_financial_occurrence_expense' => 'handleRegisterFinancialOccurrenceExpense',
];

if (isset($financialEventActions[$action])) {
    call_user_func($financialEventActions[$action], $pdo);
    exit;
}

// CUENTAS FINANCIERAS
$financialAccountActions = [
    'create_financial_account' => 'handleCreateFinancialAccount',
    'update_financial_account' => 'handleUpdateFinancialAccount',
    'delete_financial_account' => 'handleDeleteFinancialAccount',
];

if (isset($financialAccountActions[$action])) {
    call_user_func($financialAccountActions[$action], $pdo);
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

if ($action === 'google_register') {

    $credential = post('credential', '', false);
    $password = post('password', '', false);
    $confirm = post('confirm_password', '', false);

    if (!$credential || !$password || !$confirm) {
        redirectError('Completa la verificación de Google y crea tu contraseña de seguridad.', REGISTER_PATH);
    }

    if ($password !== $confirm) {
        redirectError('Las contraseñas no coinciden.', REGISTER_PATH);
    }

    if (strlen($password) < 8) {
        redirectError('La contraseña de seguridad debe tener mínimo 8 caracteres.', REGISTER_PATH);
    }

    try {
        $payload = verifyGoogleIdToken($credential);
        $googleSub = (string) $payload['sub'];
        $email = strtolower(trim((string) $payload['email']));

        if (googleIdentityBySub($pdo, $googleSub)) {
            redirectError('Esta cuenta de Google ya está registrada. Inicia sesión con Google.', LOGIN_PATH);
        }

        if (emailExists($pdo, $email)) {
            redirectError('Ya existe una cuenta de FinanzasApp registrada con este correo. Inicia sesión utilizando el método asociado a tu cuenta.', REGISTER_PATH);
        }

        $username = generateGoogleUsername($pdo, $payload);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password)
            VALUES (:username, :email, :password)
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $userId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO auth_identities (user_id, provider, provider_user_id, provider_email)
            VALUES (:user_id, 'google', :provider_user_id, :provider_email)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'provider_user_id' => $googleSub,
            'provider_email' => $email,
        ]);

        $pdo->commit();

        require_once __DIR__ . '/app/helpers/mailer.php';
        sendWelcomeEmail($email, $username);

        startUserSession([
            'id' => $userId,
            'username' => $username,
        ]);

        redirect(DASHBOARD_PATH, [
            'success' => 'Cuenta creada con Google. Usamos tu nombre de Google como usuario inicial; puedes cambiarlo desde Perfil → Editar perfil.'
        ]);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        redirectError($e->getMessage(), REGISTER_PATH);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        redirectError('No se pudo crear la cuenta con Google. Intenta nuevamente.', REGISTER_PATH);
    }
}

if ($action === 'google_login') {

    $credential = post('credential', '', false);

    if (!$credential) {
        redirectError('Completa la verificación de Google para iniciar sesión.', LOGIN_PATH);
    }

    try {
        $payload = verifyGoogleIdToken($credential);
        $user = googleIdentityBySub($pdo, (string) $payload['sub']);

        if (!$user) {
            redirectError('No existe una cuenta asociada a ese Google. Crea tu cuenta primero.', REGISTER_PATH);
        }

        startUserSession($user);

        redirect(DASHBOARD_PATH, [
            'success' => 'Bienvenido nuevamente, ' . $user['username'] . '.'
        ]);
    } catch (RuntimeException $e) {
        redirectError($e->getMessage(), LOGIN_PATH);
    } catch (PDOException $e) {
        redirectError('No se pudo iniciar sesión con Google. Verifica que la base de datos esté actualizada.', LOGIN_PATH);
    }
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
   FUNCIONES — CUENTAS FINANCIERAS
   ====================================================== */

function handleCreateFinancialAccount(PDO $pdo): void
{
    requireLogin();

    try {
        createFinancialAccount($pdo, (int) $_SESSION['user_id'], $_POST);
        redirect(ACCOUNTS_PATH, ['success' => 'Cuenta financiera creada']);
    } catch (InvalidArgumentException $exception) {
        redirectError($exception->getMessage(), ACCOUNTS_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo crear la cuenta. Verifica que la migración esté aplicada.', ACCOUNTS_PATH);
    }
}

function handleUpdateFinancialAccount(PDO $pdo): void
{
    requireLogin();

    try {
        updateFinancialAccount($pdo, (int) $_SESSION['user_id'], (int) post('id'), $_POST);
        redirect(ACCOUNTS_PATH, ['success' => 'Cuenta financiera actualizada']);
    } catch (InvalidArgumentException | RuntimeException $exception) {
        redirectError($exception->getMessage(), ACCOUNTS_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo actualizar la cuenta.', ACCOUNTS_PATH);
    }
}

function handleDeleteFinancialAccount(PDO $pdo): void
{
    requireLogin();

    try {
        deleteFinancialAccount($pdo, (int) $_SESSION['user_id'], (int) post('id'));
        redirect(ACCOUNTS_PATH, ['success' => 'Cuenta financiera eliminada']);
    } catch (RuntimeException $exception) {
        redirectError($exception->getMessage(), ACCOUNTS_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo eliminar la cuenta.', ACCOUNTS_PATH);
    }
}

/* ======================================================
   FUNCIONES — CALENDARIO FINANCIERO
   ====================================================== */

function handleCreateFinancialEvent(PDO $pdo): void
{
    requireLogin();

    try {
        createFinancialEvent($pdo, (int) $_SESSION['user_id'], $_POST);
        redirect(CALENDAR_PATH, ['success' => 'Evento financiero creado']);
    } catch (InvalidArgumentException $exception) {
        redirectError($exception->getMessage(), CALENDAR_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo crear el evento. Verifica que la migración del calendario esté aplicada.', CALENDAR_PATH);
    }
}

function handleUpdateFinancialEvent(PDO $pdo): void
{
    requireLogin();

    $eventId = (int) post('id');

    try {
        updateFinancialEvent($pdo, (int) $_SESSION['user_id'], $eventId, $_POST);
        redirect(CALENDAR_PATH, ['success' => 'Evento financiero actualizado']);
    } catch (InvalidArgumentException | RuntimeException $exception) {
        redirectError($exception->getMessage(), CALENDAR_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo actualizar el evento.', CALENDAR_PATH);
    }
}

function handleDeleteFinancialEvent(PDO $pdo): void
{
    requireLogin();

    $eventId = (int) post('id');

    try {
        deleteFinancialEvent($pdo, (int) $_SESSION['user_id'], $eventId);
        redirect(CALENDAR_PATH, ['success' => 'Evento financiero eliminado']);
    } catch (RuntimeException $exception) {
        redirectError($exception->getMessage(), CALENDAR_PATH);
    } catch (PDOException $exception) {
        redirectError('No se pudo eliminar el evento.', CALENDAR_PATH);
    }
}

function handleUpdateFinancialOccurrenceStatus(PDO $pdo): void
{
    requireLogin();

    try {
        updateFinancialEventOccurrenceStatus(
            $pdo,
            (int) $_SESSION['user_id'],
            (int) post('event_id'),
            (string) post('occurrence_date', ''),
            (string) post('status', '')
        );

        redirectFinancialCalendar('Estado de la ocurrencia actualizado');
    } catch (InvalidArgumentException | RuntimeException $exception) {
        redirectFinancialCalendar($exception->getMessage(), true);
    } catch (PDOException $exception) {
        redirectFinancialCalendar('No se pudo actualizar la ocurrencia.', true);
    }
}

function handleRegisterFinancialOccurrenceIncome(PDO $pdo): void
{
    requireLogin();

    try {
        registerFinancialEventOccurrenceAsIncome(
            $pdo,
            (int) $_SESSION['user_id'],
            (int) post('event_id'),
            (string) post('occurrence_date', ''),
            [
                'amount' => post('amount'),
                'income_type' => post('income_type', 'otro'),
                'movement_date' => post('movement_date', ''),
                'note' => post('note', ''),
            ]
        );

        redirectFinancialCalendar('Ingreso registrado desde el calendario');
    } catch (InvalidArgumentException | RuntimeException $exception) {
        redirectFinancialCalendar($exception->getMessage(), true);
    } catch (PDOException $exception) {
        redirectFinancialCalendar('No se pudo registrar el ingreso.', true);
    }
}

function handleRegisterFinancialOccurrenceExpense(PDO $pdo): void
{
    requireLogin();

    try {
        registerFinancialEventOccurrenceAsExpense(
            $pdo,
            (int) $_SESSION['user_id'],
            (int) post('event_id'),
            (string) post('occurrence_date', ''),
            [
                'income_id' => post('income_id'),
                'amount' => post('amount'),
                'movement_date' => post('movement_date', ''),
                'reflection_type' => post('reflection_type', ''),
                'note' => post('note', ''),
            ]
        );

        redirectFinancialCalendar('Gasto registrado desde el calendario');
    } catch (InvalidArgumentException | RuntimeException $exception) {
        redirectFinancialCalendar($exception->getMessage(), true);
    } catch (PDOException $exception) {
        redirectFinancialCalendar('No se pudo registrar el gasto.', true);
    }
}

function redirectFinancialCalendar(string $message, bool $isError = false): void
{
    $month = (string) post('return_month', date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    redirect(CALENDAR_PATH, [
        'mes' => $month,
        $isError ? 'error' : 'success' => $message,
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


