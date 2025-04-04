<?php
header('Content-Type: application/json');
require_once 'db.php'; // Incluir la conexión a la base de datos

$database = new Database();
$conn = $database->conn;

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_GET['accion'] === 'obtener') {
    // 🔹 Consultamos los montos desde `metodo_pago_monto` en lugar de `venta`
    $query = "SELECT id_metodo_pago, monto FROM metodo_pago_monto";
    
    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Error en la consulta de montos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🔹 Inicializar montos en 0
    $montos = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

    while ($row = $result->fetch_assoc()) {
        $montos[$row['id_metodo_pago']] = $row['monto'];
    }

    echo json_encode(['success' => true, 'montos' => $montos], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida.'], JSON_UNESCAPED_UNICODE);
exit;
?>
