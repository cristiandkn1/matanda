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

// Consulta para obtener los descuentos
$sql = "SELECT iddescuento, nombre, porcentaje, fecha FROM descuento";
$result = $conexion->query($sql);

$descuentos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $descuentos[] = [
            "id" => $row["iddescuento"],
            "nombre" => $row["nombre"],
            "porcentaje" => $row["porcentaje"],
            "fecha" => $row["fecha"]
        ];
    }
}

echo json_encode($descuentos);
$conexion->close();
?>
