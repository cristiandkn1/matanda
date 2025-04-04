<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$database = "mydb";

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conexion->connect_error]);
    exit;
}

// Capturar datos del formulario
$iddescuento = $_POST['iddescuento'];
$nuevo_nombre = $_POST['nuevo_nombre'];
$nuevo_porcentaje = $_POST['nuevo_porcentaje'];
$nueva_fecha = $_POST['nueva_fecha'];

// Actualizar descuento
$sql = "UPDATE descuento SET nombre = ?, porcentaje = ?, fecha = ? WHERE iddescuento = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sisi", $nuevo_nombre, $nuevo_porcentaje, $nueva_fecha, $iddescuento);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Descuento actualizado correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar el descuento: " . $conexion->error]);
}

$stmt->close();
$conexion->close();
?>
