<?php
require_once 'db.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de admin
if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== "vega@gmail.com") {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit();
}

$database = new Database();
$conn = $database->conn;

$saleId = isset($_POST['idventa']) ? intval($_POST['idventa']) : 0;

if ($saleId > 0) {
    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Primero eliminamos los registros relacionados de venta_detalle
        $queryDetalle = "DELETE FROM venta_detalle WHERE venta_idventa = ?";
        $stmtDetalle = $conn->prepare($queryDetalle);
        $stmtDetalle->bind_param('i', $saleId);
        $stmtDetalle->execute();
        $stmtDetalle->close();

        // Ahora eliminamos la venta
        $queryVenta = "DELETE FROM venta WHERE idventa = ?";
        $stmtVenta = $conn->prepare($queryVenta);
        $stmtVenta->bind_param('i', $saleId);
        $stmtVenta->execute();
        $stmtVenta->close();

        // Confirmamos la transacción
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Venta eliminada correctamente']);
    } catch (Exception $e) {
        // Si hay error, hacemos rollback
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID de venta inválido']);
}

$conn->close();
