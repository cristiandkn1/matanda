<?php
require 'db.php'; // Asegúrate de que aquí está tu conexión a la BD

try {
    $db = new Database(); 
    $conn = $db->conn;

    // Resetea la tabla metodo_pago_monto
    $query = "UPDATE metodo_pago_monto SET monto = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al resetear montos: ' . $e->getMessage()]);
}
?>
