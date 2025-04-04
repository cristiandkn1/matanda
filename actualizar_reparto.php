<?php
require_once 'db.php'; // Conexión a la BD

// Obtener los datos del JSON enviado desde JavaScript
$data = json_decode(file_get_contents("php://input"), true);

$idventa = $data["idventa"] ?? null;
$direccion = $data["direccion"] ?? "Indefinido";
$telefono = $data["telefono"] ?? "Sin número";
$estado_pago = $data["estado_pago"] ?? "Por pagar";
$reparto = $data["reparto"] ?? "No";
$valor_reparto_id = $data["valor_reparto_id"] ?? null; // Nuevo campo

if (!$idventa) {
    echo json_encode(["success" => false, "error" => "ID de venta no proporcionado"]);
    exit;
}

$database = new Database();
$conn = $database->conn;

// Verificar si la venta ya está en la tabla de reparto
$query_check = "SELECT idreparto FROM reparto WHERE idventa = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("i", $idventa);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // Si la venta ya está en la tabla reparto, actualizarla
    $query_update = "UPDATE reparto SET direccion = ?, telefono = ?, estado_pago = ?, valor_reparto_id = ? WHERE idventa = ?";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("sssii", $direccion, $telefono, $estado_pago, $valor_reparto_id, $idventa);
    
    if ($stmt_update->execute()) {
        $update_venta = "UPDATE venta SET reparto = ? WHERE idventa = ?";
        $stmt_venta = $conn->prepare($update_venta);
        $stmt_venta->bind_param("si", $reparto, $idventa);
        $stmt_venta->execute();

        echo json_encode(["success" => true, "message" => "Reparto actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} else {
    // Si no está en la tabla, insertarlo
    $query_insert = "INSERT INTO reparto (idventa, direccion, telefono, estado_pago, valor_reparto_id) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($query_insert);
    $stmt_insert->bind_param("isssi", $idventa, $direccion, $telefono, $estado_pago, $valor_reparto_id);
    
    if ($stmt_insert->execute()) {
        $update_venta = "UPDATE venta SET reparto = ? WHERE idventa = ?";
        $stmt_venta = $conn->prepare($update_venta);
        $stmt_venta->bind_param("si", $reparto, $idventa);
        $stmt_venta->execute();

        echo json_encode(["success" => true, "message" => "Reparto agregado correctamente"]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
}

// Cerrar conexiones
$stmt_check->close();
$conn->close();
?>
