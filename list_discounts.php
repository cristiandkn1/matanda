<?php
include 'db.php';
$db = new Database();

$query = "SELECT iddescuento AS id, nombre AS name FROM descuento";
$result = $db->conn->query($query);

if ($result && $result->num_rows > 0) {
    $discounts = [];
    while ($row = $result->fetch_assoc()) {
        $discounts[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $discounts]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontraron descuentos.']);
}
?>
