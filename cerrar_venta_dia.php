<?php
ob_end_clean(); // Limpiar todo lo anterior
ob_start();

require_once 'db.php';
require_once __DIR__ . '/fpdf/fpdf.php';

date_default_timezone_set('America/Santiago');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Limpiar caracteres incompatibles (como emojis)
function limpiarTextoPDF($texto) {
    return preg_replace('/[^\x20-\x7EñÑáéíóúÁÉÍÓÚüÜ]/u', '', $texto);
}

$database = new Database();
$conn = $database->conn;

$fechaHoy = date('Y-m-d');
$fechaInicio = "$fechaHoy 00:00:00";
$fechaFin = "$fechaHoy 23:59:59";

// ✅ Consulta SIN agrupación: mostrar cada línea individual
$query = "SELECT 
            vd.cantidad, 
            vd.precio, 
            vd.descuento, 
            vd.subtotal,
            p.nombre AS producto,
            v.pago_idpago
          FROM venta_detalle vd
          JOIN producto p ON vd.producto_idproducto = p.idproducto
          JOIN venta v ON vd.venta_idventa = v.idventa
          WHERE v.fecha BETWEEN ? AND ? AND v.cierre_id IS NULL
          ORDER BY v.idventa DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $fechaInicio, $fechaFin);
$stmt->execute();
$result = $stmt->get_result();

$ventas = [];
$totales = [
    'ventas' => 0, 'total' => 0, 'descuento' => 0,
    'debito' => 0, 'efectivo' => 0, 'transferencia' => 0, 'credito' => 0
];

$tiposPago = ['1' => 'Débito', '2' => 'Efectivo', '3' => 'Transferencia', '4' => 'Crédito'];

while ($row = $result->fetch_assoc()) {
    $row['metodo_pago'] = $tiposPago[$row['pago_idpago']] ?? 'Desconocido';
    $row['producto'] .= ' - ' . $row['metodo_pago'];

    $row['precio_dcto'] = ($row['descuento'] > 0 && $row['cantidad'] > 0)
        ? "$" . number_format(floor($row['precio'] - ($row['descuento'] / $row['cantidad'])), 0)
        : "-";

    $ventas[] = $row;
    $totales['total'] += floor($row['subtotal']);
    $totales['descuento'] += floor($row['descuento']);
    $totales['ventas']++;

    switch ($row['pago_idpago']) {
        case '1': $totales['debito']++; break;
        case '2': $totales['efectivo']++; break;
        case '3': $totales['transferencia']++; break;
        case '4': $totales['credito']++; break;
    }
}

// 📄 GENERAR PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 12, "Reporte de Ventas del Día ($fechaHoy)", 1, 1, 'C');
$pdf->Ln(8);

// 🧾 Encabezados
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(85, 12, "Producto", 1);
$pdf->Cell(20, 12, "Cant.", 1, 0, 'C');
$pdf->Cell(25, 12, "Precio", 1, 0, 'C');
$pdf->Cell(30, 12, "Precio Dcto", 1, 0, 'C');
$pdf->Cell(30, 12, "Subtotal", 1, 0, 'C');
$pdf->Ln();

// 🧾 Contenido
$pdf->SetFont('Arial', '', 12);
foreach ($ventas as $v) {
    $producto = limpiarTextoPDF($v['producto']);
    $pdf->Cell(85, 10, utf8_decode($producto), 1);
    $pdf->Cell(20, 10, $v['cantidad'], 1, 0, 'C');
    $pdf->Cell(25, 10, "$" . number_format($v['precio'], 0), 1, 0, 'C');
    $pdf->Cell(30, 10, $v['precio_dcto'], 1, 0, 'C');
    $pdf->Cell(30, 10, "$" . number_format($v['subtotal'], 0), 1, 0, 'C');
    $pdf->Ln();
}

// 🔽 Resumen
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(190, 12, "Resumen del Día", 1, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(95, 10, "Total de Ventas: {$totales['ventas']}", 1);
$pdf->Cell(95, 10, "Total Vendido: $" . number_format($totales['total'], 0), 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Total Descuentos: $" . number_format($totales['descuento'], 0), 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Pagos con Débito: {$totales['debito']}", 1);
$pdf->Cell(95, 10, "Pagos con Efectivo: {$totales['efectivo']}", 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Pagos con Transferencia: {$totales['transferencia']}", 1);
$pdf->Cell(95, 10, "Pagos con Crédito: {$totales['credito']}", 1);
$pdf->Ln();

// 🔽 Salida
ob_end_clean(); // Limpiar salida previa
$pdf->Output("I", "reporte_ventas_dia.pdf");
$conn->close();
?>
