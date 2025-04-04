<?php
include 'db.php';
$db = new Database();

$query = "SELECT idmarca AS id, nombre AS name FROM marca";
$result = $db->conn->query($query);

if ($result && $result->num_rows > 0) {
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $brands]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontraron marcas.']);
}
?>
