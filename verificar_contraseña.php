<?php
session_start();
require 'db.php'; // Asegúrate de que este archivo está en la misma carpeta o usa la ruta correcta

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $contraseñaIngresada = $data['password'] ?? '';

    // Crear instancia de la base de datos
    $db = new Database();
    $conn = $db->conn; // Obtener la conexión

    // Verificar si se pudo conectar
    if (!$conn) {
        echo json_encode(["error" => "No se pudo conectar a la base de datos"]);
        exit();
    }

    // Buscar la contraseña almacenada en la base de datos
    $stmt = $conn->prepare("SELECT contraseña FROM rol WHERE correo = 'vega@gmail.com'");
    if ($stmt) {
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $contraseñaGuardada = $fila['contraseña'];

            // Si la contraseña está encriptada, usa password_verify()
            if (password_verify($contraseñaIngresada, $contraseñaGuardada)) {
                echo 'success';
            } else {
                echo 'error';
            }
        } else {
            echo 'error';
        }

        $stmt->close();
    } else {
        echo 'error';
    }

    $db->close(); // Cerrar conexión
}
?>
