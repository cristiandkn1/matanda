<?php
session_start();
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if (isset($_POST['id'], $_SESSION['nombre'])) {
    $id = intval($_POST['id']);
    $repartidor = $_SESSION['nombre'];
    $rut = $_SESSION['user_rut'] ?? 'Sin RUT'; // si lo usas más adelante
    $asignado = $repartidor;

    $db = new Database();
    $conn = $db->conn;

    // ✅ Verificar si ya tiene un reparto pendiente
    $verificar = $conn->prepare("SELECT COUNT(*) as total FROM reparto WHERE reparto_tomado_por = ? AND estado = 'Pendiente'");
    $verificar->bind_param("s", $asignado);
    $verificar->execute();
    $yaTiene = $verificar->get_result()->fetch_assoc();

    if ($yaTiene['total'] > 0) {
        $response['message'] = 'Ya tienes un reparto pendiente. Debes completarlo o cancelarlo antes de tomar otro.';
    } else {
        // ✅ Verificar que este reparto aún no fue tomado
        $check = $conn->prepare("SELECT reparto_tomado_por FROM reparto WHERE idreparto = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();

        if (!empty($result['reparto_tomado_por'])) {
            $response['message'] = 'Este reparto ya ha sido tomado por otro repartidor.';
        } else {
            // ✅ Asignar el reparto
            $stmt = $conn->prepare("UPDATE reparto SET reparto_tomado_por = ? WHERE idreparto = ?");
            $stmt->bind_param("si", $asignado, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Reparto asignado correctamente.';
            } else {
                $response['message'] = 'Error al tomar el reparto.';
            }
        }
    }
} else {
    $response['message'] = 'Solicitud inválida.';
}

header('Content-Type: application/json');
echo json_encode($response);
