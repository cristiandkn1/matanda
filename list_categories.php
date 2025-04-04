<?php
include 'db.php';
$db = new Database();

$query = "SELECT idcategoria AS id, nombre AS name FROM categoria";
$result = $db->conn->query($query);

if ($result && $result->num_rows > 0) {
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $categories]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontraron categorías.']);
}
?>
