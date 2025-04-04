<?php
include 'db.php';
$db = new Database();
$conn = $db->conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $precio = $_POST['precio'];

    $query = "UPDATE producto SET precio_compra = ? WHERE idproducto = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("di", $precio, $id);

    if ($stmt->execute()) {
        echo "Precio actualizado correctamente.";
    } else {
        echo "Error al actualizar.";
    }
}
?>
