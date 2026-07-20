<?php

require_once __DIR__ . '/../../vend0r/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vend0r/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vend0r/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendWelcomeEmail($toEmail, $toName)
{
    $envFile = __DIR__ . '/../../.env.php';
    if (file_exists($envFile)) {
        require_once $envFile;
    }

    $smtpHost = $_ENV['SMTP_HOST'] ?? '';
    $smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
    $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
    $smtpPort = (int) ($_ENV['SMTP_PORT'] ?? 465);
    $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? $smtpUsername;
    $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Finanzas App';
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'https://finanzasappsan.com', '/');

    if (!$smtpHost || !$smtpUsername || !$smtpPassword || !$fromEmail) {
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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
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
        return false;
    }
}
