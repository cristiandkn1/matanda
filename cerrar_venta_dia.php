<?php
require_once 'db.php'; // Conexión a la base de datos
require_once __DIR__ . '/fpdf/fpdf.php';

// Configurar la zona horaria correcta
date_default_timezone_set('America/Santiago'); // Ajusta según tu zona horaria

// Crear conexión
$database = new Database();
$conn = $database->conn;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener ventas del día actual que NO estén cerradas
$fechaHoy = date('Y-m-d');
$fechaInicio = date('Y-m-d 00:00:00');
$fechaFin = date('Y-m-d 23:59:59');

// Consulta SQL con la nueva columna "descuento"
$query = "SELECT vd.producto_idproducto, p.nombre AS producto, 
                 SUM(vd.cantidad) AS cantidad, 
                 vd.precio, 
                 SUM(vd.descuento) AS descuento, 
                 SUM(vd.subtotal) AS subtotal, 
                 v.pago_idpago
          FROM venta v
          JOIN venta_detalle vd ON v.idventa = vd.venta_idventa
          JOIN producto p ON vd.producto_idproducto = p.idproducto
          WHERE v.fecha BETWEEN ? AND ? AND v.cierre_id IS NULL
          GROUP BY vd.producto_idproducto, vd.precio, v.pago_idpago";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $fechaInicio, $fechaFin);
$stmt->execute();
$result = $stmt->get_result();

$ventas = [];
$totales = [
    'ventas' => 0, 
    'total' => 0, 
    'descuento' => 0, 
    'debito' => 0, 
    'efectivo' => 0, 
    'transferencia' => 0, 
    'credito' => 0
];

// Mapeo de tipos de pago
$tiposPago = [
    '1' => 'Débito',
    '2' => 'Efectivo',
    '3' => 'Transferencia',
    '4' => 'Crédito'
];

while ($row = $result->fetch_assoc()) {
    $metodoPago = isset($tiposPago[$row['pago_idpago']]) ? $tiposPago[$row['pago_idpago']] : 'Desconocido';
    
    // Concatenamos el producto con el método de pago
    $row['producto'] = $row['producto'] . ' - ' . $metodoPago;
    
    // Si no hay descuento, mostrar "-"
    if ($row['descuento'] > 0) {
        $row['precio_dcto'] = "$" . number_format(floor($row['precio'] - ($row['descuento'] / $row['cantidad'])), 0);
    } else {
        $row['precio_dcto'] = "-";
    }

    $ventas[] = $row;
    $totales['total'] += floor($row['subtotal']);
    $totales['descuento'] += floor($row['descuento']);
    $totales['ventas']++;

    // Contar tipos de pago
    if ($row['pago_idpago'] == '1') {  
        $totales['debito']++;
    } elseif ($row['pago_idpago'] == '2') {  
        $totales['efectivo']++;
    } elseif ($row['pago_idpago'] == '3') {  
        $totales['transferencia']++;
    } elseif ($row['pago_idpago'] == '4') {  
        $totales['credito']++;
    }
}

// Generar PDF con FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 12, "Reporte de Ventas del Dia ($fechaHoy)", 1, 1, 'C');
$pdf->Ln(8);

// Encabezados de la tabla con más espacio
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(85, 12, "Producto", 1); 
$pdf->Cell(20, 12, "Cant.", 1, 0, 'C'); 
$pdf->Cell(25, 12, "Precio", 1, 0, 'C');
$pdf->Cell(30, 12, "Precio Dcto", 1, 0, 'C'); 
$pdf->Cell(30, 12, "Subtotal", 1, 0, 'C');
$pdf->Ln();

// Contenido de la tabla
$pdf->SetFont('Arial', '', 12);
foreach ($ventas as $venta) {
    $pdf->Cell(85, 10, utf8_decode($venta['producto']), 1);
    $pdf->Cell(20, 10, $venta['cantidad'], 1, 0, 'C');
    $pdf->Cell(25, 10, "$" . number_format(floor($venta['precio']), 0), 1, 0, 'C');
    $pdf->Cell(30, 10, $venta['precio_dcto'], 1, 0, 'C');
    $pdf->Cell(30, 10, "$" . number_format(floor($venta['subtotal']), 0), 1, 0, 'C');
    $pdf->Ln();
}

// Espacio antes del resumen
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(190, 12, "Resumen del Dia", 1, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(95, 10, "Total de Ventas: " . $totales['ventas'], 1);
$pdf->Cell(95, 10, "Total Vendido: $" . number_format(floor($totales['total']), 0), 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Total Descuentos: $" . number_format(floor($totales['descuento']), 0), 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Pagos con Debito: " . $totales['debito'], 1);
$pdf->Cell(95, 10, "Pagos con Efectivo: " . $totales['efectivo'], 1);
$pdf->Ln();
$pdf->Cell(95, 10, "Pagos con Transferencia: " . $totales['transferencia'], 1);
$pdf->Cell(95, 10, "Pagos con Credito: " . $totales['credito'], 1);
$pdf->Ln();

$pdf->Output();
$conn->close();

