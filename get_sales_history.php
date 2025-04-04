<?php
require_once 'db.php';

header('Content-Type: application/json');

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->conn;

// Consultar todas las ventas
$query = "SELECT idventa, monto, fecha, metodo_pago, estado, notas, 
            '<button class=\"edit-sale-btn\">Editar</button>' AS acciones 
          FROM venta";
$result = $conn->query($query);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['data' => $data]);

$conn->close();
?>
