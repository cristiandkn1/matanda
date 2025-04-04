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

// Consulta para obtener las marcas
$sql = "SELECT idmarca, nombre FROM marca";
$result = $conexion->query($sql);

$marcas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $marcas[] = [
            "id" => $row["idmarca"],
            "nombre" => $row["nombre"]
        ];
    }
}

echo json_encode($marcas);
$conexion->close();
?>
