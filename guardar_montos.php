<?php
header('Content-Type: application/json');
require_once 'db.php'; // Incluir la conexión a la base de datos

$database = new Database();
$conn = $database->conn;

// Verificar conexión
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']);
    exit;
}

// Obtener montos acumulados por método de pago del día actual
$query = "SELECT pago_idpago, SUM(monto) as total FROM venta WHERE DATE(fecha) = CURDATE() GROUP BY pago_idpago";
$result = $conn->query($query);

// Definir los montos iniciales en 0
$paymentAmounts = [
    '1' => 0, // Débito
    '2' => 0, // Efectivo
    '3' => 0, // Transferencia
    '4' => 0  // Crédito
];

while ($row = $result->fetch_assoc()) {
    $paymentAmounts[$row['pago_idpago']] = $row['total'];
}

// Responder con los montos actualizados
echo json_encode(['success' => true, 'paymentAmounts' => $paymentAmounts]);

$database->close();
?>
