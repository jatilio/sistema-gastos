<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// RUTAS A PHPMailer (sube un nivel y entra a phpmailer/)
require __DIR__ . '/../phpmailer/PHPMailer.php';
require __DIR__ . '/../phpmailer/SMTP.php';
require __DIR__ . '/../phpmailer/Exception.php';

function enviarCorreo($asunto, $mensaje) {
    $mail = new PHPMailer(true);

    try {
        // CONFIGURACIÓN SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'TU_CORREO@gmail.com';
        $mail->Password = 'TU_PASSWORD_APP';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // REMITENTE
        $mail->setFrom('TU_CORREO@gmail.com', 'Alertas Financieras');

        // DESTINATARIO
        $mail->addAddress('CORREO_DESTINO@gmail.com');

        // CONTENIDO
        $mail->isHTML(false);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
