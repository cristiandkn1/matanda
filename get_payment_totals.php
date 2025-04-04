<?php
header('Content-Type: application/json');
require_once 'db.php';

$database = new Database();
$conn = $database->conn;

if (!$conn) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// 🔹 Consultar montos acumulados por método de pago
$query = "SELECT metodo, SUM(monto) as total FROM metodo_pago_monto GROUP BY metodo";
$result = $conn->query($query);

$montos = [
    'efectivo' => 0.00,
    'transferencia' => 0.00,
    'debito' => 0.00,
    'credito' => 0.00
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        
        switch ($row['metodo']) {
            case '2': // ID de Efectivo
                $montos['efectivo'] = (float) $row['total'];
                break;
            case '3': // ID de Transferencia
                $montos['transferencia'] = (float) $row['total'];
                break;
            case '1': // ID de Débito
                $montos['debito'] = (float) $row['total'];
                break;
            case '4': // ID de Crédito
                $montos['credito'] = (float) $row['total'];
                break;
        }
    }
}

echo json_encode($montos);
?>
