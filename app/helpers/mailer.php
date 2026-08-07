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
        mailerLog('configuracion SMTP incompleta', [
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
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = $smtpEncryption === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $smtpPort;

        // Remitente
        $mail->setFrom($fromEmail, $fromName);

        // Destinatario
        $mail->addAddress($toEmail, $toName);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a Finanzas App';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <title>Bienvenido a Finanzas App</title>
        </head>
        <body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">

        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:30px 0;">
        <tr>
        <td align="center">

        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">

        <!-- Header -->
        <tr>
        <td style="background:#111827;padding:25px;text-align:center;">
        <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:600;">
        Finanzas App
        </h1>
        <p style="color:#d1d5db;margin:5px 0 0;font-size:13px;">
        Controla tu dinero. Construye tu futuro.
        </p>
        </td>
        </tr>

        <!-- Body -->
        <tr>
        <td style="padding:30px 40px;color:#374151;font-size:15px;line-height:1.6;">

        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($toName) . '</strong>,</p>

        <p>
        Tu cuenta ha sido creada correctamente y ya puedes comenzar a gestionar tus finanzas con claridad.
        </p>

        <p>
        Desde ahora podrás:
        </p>

        <ul style="padding-left:20px;margin:10px 0 20px;">
        <li>Registrar ingresos y gastos fácilmente.</li>
        <li>Visualizar tu saldo en tiempo real.</li>
        <li>Analizar tu evolución financiera.</li>
        <li>Descargar reportes profesionales.</li>
        </ul>

        <div style="text-align:center;margin:30px 0;">
        <a href="' . htmlspecialchars($appUrl . '/views/auth/login.php') . '"
        style="background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600;font-size:14px;display:inline-block;">
        Ir al Panel
        </a>
        </div>

        <p style="margin-bottom:0;">
        Gracias por confiar en <strong>Finanzas App</strong>.
        </p>

        </td>
        </tr>

        <!-- Footer -->
        <tr>
        <td style="background:#f9fafb;padding:20px;text-align:center;font-size:12px;color:#6b7280;">
        © ' . date('Y') . ' Finanzas App. Todos los derechos reservados.
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

        Tu cuenta ha sido creada correctamente en Finanzas App.

        Ahora puedes registrar ingresos y gastos, ver tu saldo en tiempo real y descargar reportes.

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
