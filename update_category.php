<?php 
header('Content-Type: application/json'); // Asegurar que la respuesta sea JSON

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
$idcategoria = $_POST['idcategoria'];
$nuevo_nombre = $_POST['nuevo_nombre'];

// Actualizar categoría
$sql = "UPDATE categoria SET nombre = ? WHERE idcategoria = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $nuevo_nombre, $idcategoria);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Categoría actualizada correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar la categoría: " . $conexion->error]);
}

$stmt->close();
$conexion->close();
?>
