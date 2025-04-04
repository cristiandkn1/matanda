<?php
require_once 'db.php';

header('Content-Type: application/json');
$database = new Database();
$conn = $database->conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idventa'])) {
    $idventa = intval($_POST['idventa']);

    // Inicia transacción
    $conn->begin_transaction();

    try {
        // Eliminar el reparto
        $stmt1 = $conn->prepare("DELETE FROM reparto WHERE idventa = ?");
        $stmt1->bind_param("i", $idventa);
        $stmt1->execute();
        $stmt1->close();

        // Actualizar el campo reparto en la tabla venta
        $stmt2 = $conn->prepare("UPDATE venta SET reparto = 'No' WHERE idventa = ?");
        $stmt2->bind_param("i", $idventa);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Parámetros inválidos"]);
}

$conn->close();
