<?php
include 'db.php'; // Asegúrate de que este archivo contiene la conexión a la BD correctamente

// Habilitar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id_producto = intval($_POST['id']); // Asegurar que el ID es un número entero

    if (!$conn) {
        die(json_encode(["error" => "Error de conexión a la base de datos: " . mysqli_connect_error()]));
    }

    // Ejecutar la consulta para actualizar vendidos_mes a 0
    $query = "UPDATE producto SET vendidos_mes = 0 WHERE idproducto = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_producto);
        if ($stmt->execute()) {
            echo json_encode(["success" => "Ventas del mes reiniciadas para el producto ID $id_producto"]);
        } else {
            echo json_encode(["error" => "Error al ejecutar la consulta: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "Error en la preparación de la consulta: " . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(["error" => "ID de producto no recibido o método incorrecto"]);
}
?>
