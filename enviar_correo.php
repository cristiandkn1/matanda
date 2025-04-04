<?php
require_once 'db.php';
require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurar zona horaria
date_default_timezone_set('America/Santiago');

$fechaHoy = date('Y-m-d');
$nombreArchivo = "ventas_$fechaHoy.pdf";
$directorioEscritorio = "C:/Users/kakar/Desktop/"; // Cambia TU_USUARIO por tu nombre de usuario

// Generar PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 10, "Reporte de Ventas del Dia ($fechaHoy)", 1, 1, 'C');
$pdf->Ln(10);
$pdf->Output("F", $directorioEscritorio . $nombreArchivo); // Guardar en escritorio

// Enviar correo con PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tu_correo@gmail.com'; // Tu correo
    $mail->Password = 'tu_contraseña'; // Tu contraseña de aplicación
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('tu_correo@gmail.com', 'Sistema Matanda');
    $mail->addAddress('destinatario@gmail.com', 'Administrador');
    $mail->addAttachment($directorioEscritorio . $nombreArchivo); // Adjuntar el PDF guardado
    $mail->isHTML(true);
    $mail->Subject = "Reporte Diario de Ventas $fechaHoy";
    $mail->Body = "Adjunto se encuentra el reporte diario de ventas del día $fechaHoy.";

    if ($mail->send()) {
        echo "✅ Correo enviado correctamente y guardado en el escritorio.";
    } else {
        echo "❌ Error al enviar correo: " . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo "❌ Error al enviar correo: " . $mail->ErrorInfo;
}
?>
