<?php
require_once 'db.php';

$type = $_GET['type'] ?? '';
$validTypes = ['marca', 'categoria', 'descuento'];

if (in_array($type, $validTypes)) {
    $db = new Database();
    $query = "SELECT id, nombre FROM $type";
    $result = $db->query($query);

    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }

    echo json_encode($options);
    $db->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de opción inválido']);
}
?>
