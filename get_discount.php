<?php
include 'db.php';
$db = new Database();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT nombre, porcentaje, fecha FROM descuento WHERE iddescuento = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'discount' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Descuento no encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado.']);
}
?>
