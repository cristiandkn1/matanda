<?php
require_once 'db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se envió el ID del producto
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $productId = intval($_POST['id']);

        // Instancia de la base de datos
        $database = new Database();
        $conn = $database->conn;

        // Actualizar el campo visible a 0
        $query = "UPDATE producto SET visible = 0 WHERE idproducto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $productId);

        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Error al actualizar el producto: ' . $conn->error;
        }

        $stmt->close();
        $database->close();
    } else {
        $response['message'] = 'ID del producto no válido.';
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
?>
