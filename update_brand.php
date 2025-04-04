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
$idmarca = $_POST['idmarca'];
$nuevo_nombre = $_POST['nuevo_nombre'];

// Actualizar marca
$sql = "UPDATE marca SET nombre = ? WHERE idmarca = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $nuevo_nombre, $idmarca);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Marca actualizada correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar la marca: " . $conexion->error]);
}

$stmt->close();
$conexion->close();
?>
