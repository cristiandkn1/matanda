<?php
ob_end_clean();
ob_start();

require_once __DIR__ . '/fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, utf8_decode('¡PDF de prueba!'));

ob_end_clean(); // ⚠️ Limpia todo antes de salida binaria
$pdf->Output("I", "test.pdf");
