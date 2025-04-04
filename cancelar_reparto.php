<?php
session_start();
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $db = new Database();
    $conn = $db->conn;

    // También limpiamos el campo reparto_tomado_por
    $stmt = $conn->prepare("UPDATE reparto SET estado = 'Cancelado', reparto_tomado_por = NULL WHERE idreparto = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Reparto cancelado correctamente.';
    } else {
        $response['message'] = 'Error al cancelar reparto.';
    }
} else {
    $response['message'] = 'ID inválido.';
}

header('Content-Type: application/json');
echo json_encode($response);
