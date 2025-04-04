<?php
include 'db.php';
$db = new Database();

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $id = $data['id'];
    $query = "DELETE FROM descuento WHERE iddescuento = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar el descuento.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado.']);
}
?>
