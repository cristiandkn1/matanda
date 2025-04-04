<?php
header('Content-Type: application/json');
require_once 'db.php';

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->conn;

try {
    // Reiniciar `vendidos_mes` sin eliminar datos de `venta_detalle`
    $query = "UPDATE producto SET vendidos_mes = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Las ventas del mes han sido reiniciadas correctamente.'
    ]);
} catch (Exception $e) {
    error_log("Error en reset_all_sales.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al reiniciar las ventas del mes.'
    ]);
} finally {
    $database->close();
}
