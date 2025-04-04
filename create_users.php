<?php
require_once 'db.php';

$db = new Database();

// Función para insertar usuario
function insertarUsuario($db, $nombre, $correo, $contraseña_plana) {
    $contraseña_hash = password_hash($contraseña_plana, PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO rol (nombre, contraseña, correo) VALUES (?, ?, ?)";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $contraseña_hash, $correo);

    if ($stmt->execute()) {
        echo "Cuenta creada: $correo<br>";
    } else {
        echo "Error al crear cuenta ($correo): " . $stmt->error . "<br>";
    }

    $stmt->close();
}

// Crear cuentas
insertarUsuario($db, 'Usuario', 'damary.sierralta@gmail.com', 'kimchi.1');
insertarUsuario($db, 'Empleado', 'empleado@ferreteriahinojal.cl', 'Hinojal2024');

$db->conn->close();
?>
