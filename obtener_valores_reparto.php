<?php
require 'db.php'; // Asegúrate de que este archivo esté en el mismo directorio

$db = new Database(); // Crear una instancia de la base de datos

$sql = "SELECT * FROM valores_reparto";
$result = $db->query($sql);

$valores = [];
while ($row = $result->fetch_assoc()) {
    $valores[] = $row;
}

echo json_encode($valores);

$db->close(); // Cerrar la conexión
?>
