<?php
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if (isset($_POST['nombre'], $_POST['correo'], $_POST['password'], $_POST['rol'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $rol = trim($_POST['rol']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $db = new Database();
    $conn = $db->conn;

    // Verificar si el correo ya existe
    $check = $conn->prepare("SELECT idrol FROM rol WHERE correo = ?");
    $check->bind_param("s", $correo);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $response['message'] = "El correo ya está registrado.";
    } else {
        $insert = $conn->prepare("INSERT INTO rol (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)");
$insert->bind_param("ssss", $nombre, $correo, $password, $rol);


        if ($insert->execute()) {
            $response['success'] = true;
            $response['message'] = "Usuario agregado correctamente.";
        } else {
            $response['message'] = "Error al insertar el usuario.";
        }
    }
} else {
    $response['message'] = "Faltan datos obligatorios.";
}

header('Content-Type: application/json');
echo json_encode($response);
