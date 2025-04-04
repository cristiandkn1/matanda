<?php
include 'db.php'; // Archivo de conexión
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $porcentaje = $_POST['porcentaje'];
    $fecha = $_POST['fecha'];

    $query = "INSERT INTO descuento (nombre, porcentaje, fecha) VALUES (?, ?, ?)";

    if ($stmt = $db->conn->prepare($query)) {
        $stmt->bind_param("sds", $nombre, $porcentaje, $fecha);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el descuento.']);
        }

        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la preparación de la consulta.']);
    }

    $db->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Método no permitido.']);
}
