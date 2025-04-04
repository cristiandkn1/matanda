<?php
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $db = new Database();
    $conn = $db->conn;

    // Verificar si el usuario existe
    $check = $conn->prepare("SELECT * FROM rol WHERE idrol = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM rol WHERE idrol = ?");
        $delete->bind_param("i", $id);

        if ($delete->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuario eliminado correctamente.';
        } else {
            $response['message'] = 'Error al eliminar usuario.';
        }
    } else {
        $response['message'] = 'Usuario no encontrado.';
    }
} else {
    $response['message'] = 'ID no recibido.';
}

header('Content-Type: application/json');
echo json_encode($response);
