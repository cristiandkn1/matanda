<?php
include 'db.php'; // Archivo de conexión
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];

    $query = "INSERT INTO categoria (nombre) VALUES (?)";

    if ($stmt = $db->conn->prepare($query)) {
        $stmt->bind_param("s", $nombre);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar la categoría.']);
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
