<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$database = "mydb";

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Consulta para obtener las categorías
$sql = "SELECT idcategoria, nombre FROM categoria";
$result = $conexion->query($sql);

$categorias = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = [
            "id" => $row["idcategoria"],
            "nombre" => $row["nombre"]
        ];
    }
}

echo json_encode($categorias);
$conexion->close();
?>
