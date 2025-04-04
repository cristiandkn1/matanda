<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function enviarReportePorCorreo($destinatario) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.tudominio.com'; // Cambia esto por tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'tuemail@tudominio.com'; // Tu email
        $mail->Password = 'tu_contraseña'; // Tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del correo
        $mail->setFrom('tuemail@tudominio.com', 'Nombre Remitente');
        $mail->addAddress($destinatario);
        $mail->Subject = '📊 Reporte de Ventas Mensual';
        $mail->Body = 'Adjunto encontrarás el reporte de ventas del mes actual.';
        $mail->addAttachment(__DIR__ . '/reporte_mes_actual.pdf');

        // Enviar correo
        $mail->send();
        echo "✅ Correo enviado exitosamente a $destinatario.";
    } catch (Exception $e) {
        echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
    }
}
?>
