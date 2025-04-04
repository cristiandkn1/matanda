<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['nombre'], $_POST['correo'], $_POST['rol'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $rol = trim($_POST['rol']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $database = new Database();
    $conn = $database->conn;

    if ($password !== '') {
        // Si se ingresó una nueva contraseña, actualiza todo
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE rol SET nombre = ?, correo = ?, contraseña = ?, rol = ? WHERE idrol = ?");
        $stmt->bind_param("ssssi", $nombre, $correo, $passwordHash, $rol, $id);
    } else {
        // Si no se ingresó nueva contraseña
        $stmt = $conn->prepare("UPDATE rol SET nombre = ?, correo = ?, rol = ? WHERE idrol = ?");
        $stmt->bind_param("sssi", $nombre, $correo, $rol, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
}
