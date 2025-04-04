<?php
require_once 'db.php';

header("Content-Type: application/json");

$database = new Database();
$conn = $database->conn;

if (!$conn) {
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

$query = "SELECT 
            v.idventa, 
            v.monto, 
            v.fecha, 
            v.pago_idpago,  -- ✅ Nuevo campo

            COALESCE(r.direccion, 'Indefinido') AS direccion, 
            COALESCE(r.telefono, 'Sin número') AS telefono, 
            COALESCE(r.estado_pago, 'Por pagar') AS estado_pago, 
            COALESCE(r.estado, 'Pendiente') AS estado,
            COALESCE(r.cantidad_pagada, 0) AS cantidad_pagada,

            v.reparto, 
            COALESCE(vr.nombre, 'No asignado') AS nombre_reparto, 
            COALESCE(vr.precio, 0) AS precio_reparto,
            COALESCE(r.reparto_tomado_por, '') AS reparto_tomado_por
          FROM venta v 
          LEFT JOIN reparto r ON v.idventa = r.idventa
          LEFT JOIN valores_reparto vr ON r.valor_reparto_id = vr.id
          WHERE v.reparto = 'Sí'";


$result = $conn->query($query);

if (!$result) {
    echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
    exit;
}

$ventas = [];
while ($row = $result->fetch_assoc()) {
    $ventas[] = $row;
}

echo json_encode($ventas);
?>
