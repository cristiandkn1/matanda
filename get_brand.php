<?php
include 'db.php';
$db = new Database();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM marca WHERE idmarca = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'brand' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Marca no encontrada.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado.']);
}
?>
