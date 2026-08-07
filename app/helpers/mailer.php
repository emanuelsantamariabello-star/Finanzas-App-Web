<?php

require_once __DIR__ . '/../../vend0r/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vend0r/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vend0r/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function mailerEnv($primaryKey, $fallbackKeys = [], $default = '')
{
    $keys = array_merge([$primaryKey], $fallbackKeys);

    foreach ($keys as $key) {
        if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
            return trim((string) $_ENV[$key]);
        }
    }

    return $default;
}

function mailerLog($message, array $context = [])
{
    $logDir = __DIR__ . '/../../logs';
    $logFile = $logDir . '/errors.log';

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $safeContext = [];
    foreach ($context as $key => $value) {
        if (stripos($key, 'pass') !== false || stripos($key, 'password') !== false) {
            continue;
        }

        $safeContext[$key] = $value;
    }

    $line = '[' . date('Y-m-d H:i:s') . '] Correo bienvenida: ' . $message;
    if ($safeContext) {
        $line .= ' | ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if (@file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log($line);
    }
}

function sendWelcomeEmail($toEmail, $toName)
{
    $envFile = __DIR__ . '/../../.env.php';
    if (file_exists($envFile)) {
        require_once $envFile;
    }

    $smtpHost = mailerEnv('SMTP_HOST', ['MAIL_HOST']);
    $smtpUsername = mailerEnv('SMTP_USERNAME', ['MAIL_USER', 'MAIL_USERNAME']);
    $smtpPassword = mailerEnv('SMTP_PASSWORD', ['MAIL_PASS', 'MAIL_PASSWORD']);
    $smtpPort = (int) mailerEnv('SMTP_PORT', ['MAIL_PORT'], '465');
    $smtpEncryption = strtolower(mailerEnv('SMTP_ENCRYPTION', ['MAIL_ENCRYPTION'], 'smtps'));
    $fromEmail = mailerEnv('SMTP_FROM_EMAIL', ['MAIL_FROM_EMAIL'], $smtpUsername);
    $fromName = mailerEnv('SMTP_FROM_NAME', ['MAIL_FROM_NAME'], 'Finanzas App');
    $appUrl = rtrim(mailerEnv('APP_URL', [], 'https://finanzasappsan.com'), '/');

    if (!$smtpHost || !$smtpUsername || !$smtpPassword || !$fromEmail) {
        mailerLog('configuración SMTP incompleta', [
            'smtp_host' => $smtpHost ? 'definido' : 'faltante',
            'smtp_username' => $smtpUsername ? 'definido' : 'faltante',
            'smtp_password' => $smtpPassword ? 'definido' : 'faltante',
            'from_email' => $fromEmail ? 'definido' : 'faltante',
            'to_email' => $toEmail,
        ]);

        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = $smtpEncryption === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $smtpPort;

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a Finanzas App - Tu cuenta está lista';

        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $loginUrl = htmlspecialchars($appUrl . '/views/auth/login.php', ENT_QUOTES, 'UTF-8');
        $logoUrl = htmlspecialchars($appUrl . '/public/img/favicon.png', ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bienvenido a Finanzas App</title>
        </head>
        <body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;background:#f3f6fb;padding:32px 12px;">
        <tr>
        <td align="center">

        <table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:640px;background:#ffffff;border-radius:22px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,0.14);">
        <tr>
        <td style="background:#1E3A8A;background:linear-gradient(135deg,#1E3A8A 0%,#2563EB 56%,#60A5FA 100%);padding:34px 34px 38px;text-align:left;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
        <td style="vertical-align:middle;">
        <img src="' . $logoUrl . '" alt="Finanzas App" width="56" height="56" style="display:block;border-radius:14px;background:#ffffff;padding:4px;border:0;">
        </td>
        <td align="right" style="vertical-align:middle;">
        <span style="display:inline-block;color:#dbeafe;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.22);padding:8px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.3px;">
        Cuenta activa
        </span>
        </td>
        </tr>
        </table>

        <h1 style="color:#ffffff;margin:28px 0 10px;font-size:30px;line-height:1.2;font-weight:800;">
        Bienvenido a Finanzas App
        </h1>
        <p style="color:#e0ecff;margin:0;font-size:15px;line-height:1.7;max-width:500px;">
        Tu espacio para registrar ingresos, controlar gastos y tomar mejores decisiones con tu dinero.
        </p>
        </td>
        </tr>

        <tr>
        <td style="padding:34px 34px 14px;">
        <p style="margin:0 0 16px;color:#111827;font-size:18px;line-height:1.6;">
        Hola <strong>' . $safeName . '</strong>,
        </p>

        <p style="margin:0;color:#4b5563;font-size:15px;line-height:1.7;">
        Tu cuenta fue creada correctamente. Desde ahora puedes acceder a tu panel y comenzar a llevar un control claro, ordenado y visual de tus finanzas personales.
        </p>

        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:28px 0 8px;">
        <tr>
        <td style="padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
        <td width="36" style="vertical-align:top;">
        <span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background:#dbeafe;color:#1E3A8A;font-size:12px;font-weight:800;">1</span>
        </td>
        <td style="color:#374151;font-size:14px;line-height:1.6;">
        <strong style="color:#111827;">Registra ingresos y gastos</strong><br>
        Mantén cada movimiento organizado para conocer tu saldo real.
        </td>
        </tr>
        </table>
        </td>
        </tr>
        <tr><td style="height:12px;"></td></tr>
        <tr>
        <td style="padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
        <td width="36" style="vertical-align:top;">
        <span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background:#dbeafe;color:#1E3A8A;font-size:12px;font-weight:800;">2</span>
        </td>
        <td style="color:#374151;font-size:14px;line-height:1.6;">
        <strong style="color:#111827;">Visualiza tu evolución financiera</strong><br>
        Consulta gráficas, saldos acumulados y reportes para entender mejor tu progreso.
        </td>
        </tr>
        </table>
        </td>
        </tr>
        <tr><td style="height:12px;"></td></tr>
        <tr>
        <td style="padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
        <td width="36" style="vertical-align:top;">
        <span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background:#dbeafe;color:#1E3A8A;font-size:12px;font-weight:800;">3</span>
        </td>
        <td style="color:#374151;font-size:14px;line-height:1.6;">
        <strong style="color:#111827;">Recibe recordatorios y novedades</strong><br>
        Usa las notificaciones para mantener tu control financiero al día.
        </td>
        </tr>
        </table>
        </td>
        </tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:30px 0 22px;">
        <tr>
        <td align="center">
        <a href="' . $loginUrl . '"
        style="background:#2563EB;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:12px;font-weight:700;font-size:15px;display:inline-block;box-shadow:0 10px 22px rgba(37,99,235,0.28);">
        Iniciar sesión
        </a>
        </td>
        </tr>
        </table>

        <p style="margin:0;color:#6b7280;font-size:13px;line-height:1.7;text-align:center;">
        Si no creaste esta cuenta, puedes ignorar este correo.
        </p>
        </td>
        </tr>

        <tr>
        <td style="padding:24px 34px 30px;text-align:center;">
        <div style="height:1px;background:#e5e7eb;margin-bottom:20px;"></div>
        <p style="margin:0 0 6px;color:#111827;font-size:13px;font-weight:700;">
        Finanzas App
        </p>
        <p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">
        Controla tu dinero. Construye tu futuro.<br>
        © ' . $currentYear . ' Finanzas App. Todos los derechos reservados.
        </p>
        </td>
        </tr>
        </table>

        </td>
        </tr>
        </table>

        </body>
        </html>
        ';

        $mail->AltBody =
        "Hola {$toName},

        Tu cuenta fue creada correctamente en Finanzas App.

        Ahora puedes registrar ingresos y gastos, visualizar tu evolución financiera, consultar reportes y recibir recordatorios para mantener tu control financiero al día.

        Ingresa aquí:
        {$appUrl}/views/auth/login.php

        Gracias por confiar en Finanzas App.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        mailerLog('fallo al enviar correo', [
            'error' => $mail->ErrorInfo ?: $e->getMessage(),
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_encryption' => $smtpEncryption,
            'to_email' => $toEmail,
        ]);

        return false;
    }
}
