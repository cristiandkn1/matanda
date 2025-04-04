<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db_connection.php';

    $id = intval($_POST['id']);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'El ID del producto es inválido.']);
        exit;
    }

    // Eliminar registros relacionados en venta_detalle
    $stmt = $conn->prepare("DELETE FROM venta_detalle WHERE producto_idproducto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Eliminar el producto
    $stmt = $conn->prepare("DELETE FROM producto WHERE idproducto = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
