<?php
require_once 'db.php'; // Conexión a la BD

$database = new Database(); // Instanciar la clase
$conn = $database->conn; // Obtener la conexión

// Obtener solo las ventas que no han sido repartidas, ordenadas por idventa descendente
$query = "SELECT idventa, monto, fecha, reparto FROM venta WHERE reparto = 'No' ORDER BY idventa DESC";
$result = $conn->query($query);

if (!$result) {
    echo json_encode(["error" => "Error en la consulta", "detalle" => $conn->error]);
    exit();
}

$ventas = [];
while ($row = $result->fetch_assoc()) {
    $ventas[] = $row;
}

// Devolver las ventas en formato JSON
echo json_encode($ventas);
?>
