<?php
require_once 'db.php';

// Configura el encabezado para JSON
header('Content-Type: application/json');

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->conn;

// Obtener datos enviados desde AJAX
$saleId = isset($_POST['idventa']) ? intval($_POST['idventa']) : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : null;
$notas = isset($_POST['notas']) ? trim($_POST['notas']) : null;

// Verificar si se recibió un ID de venta válido
if ($saleId > 0) {
    // Si `estado` o `notas` están presentes, se considera una actualización
    if ($estado !== null || $notas !== null) {
        // Consulta para actualizar la venta
        $query = "UPDATE venta SET estado = ?, notas = ? WHERE idventa = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('ssi', $estado, $notas, $saleId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Venta actualizada correctamente']);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
    // Si no se proporcionan `estado` ni `notas`, se considera una eliminación
    else {
        // Consulta para eliminar la venta
        $query = "DELETE FROM venta WHERE idventa = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('i', $saleId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Venta eliminada correctamente']);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID de venta inválido']);
}

$conn->close();
?>
