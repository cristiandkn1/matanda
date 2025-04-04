<?php
require_once 'db.php';

// Crear una instancia de la clase Database
$database = new Database();
$conn = $database->conn;

// Establecer encabezado de contenido como JSON
header('Content-Type: application/json');

// Obtener el producto_id desde la solicitud GET
$productoId = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;

if ($productoId > 0) {
    $query = "SELECT DISTINCT p.idpago, p.nombre
              FROM pago p
              INNER JOIN venta v ON v.pago_idpago = p.idpago
              WHERE v.producto_idproducto = ?";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        $result = $stmt->get_result();

        $paymentMethods = [];
        while ($row = $result->fetch_assoc()) {
            $paymentMethods[] = $row;
        }

        echo json_encode($paymentMethods);
        $stmt->close();
    } else {
        echo json_encode(["error" => "Error al preparar la consulta"]);
    }
} else {
    echo json_encode(["error" => "ID de producto inválido"]);
}

$conn->close();
