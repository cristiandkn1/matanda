<?php
require_once 'db.php';

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $codigo = $_POST['codigo'];
    $fecha = $_POST['fecha'];
    $descripcion = !empty($_POST['descripcion']) ? $_POST['descripcion'] : NULL;
    $cantidad = $_POST['cantidad'];

    // ✅ Nuevo: fecha de vencimiento (opcional)
    $fecha_vencimiento = isset($_POST['fecha_vencimiento']) && !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : NULL;

    // Verificar si se envió una marca o categoría seleccionada
    $marca_id = isset($_POST['marca_idmarca']) && !empty($_POST['marca_idmarca']) ? $_POST['marca_idmarca'] : 5; // ID de "Ninguna" para marca
    $categoria_id = isset($_POST['categoria_idcategoria']) && !empty($_POST['categoria_idcategoria']) ? $_POST['categoria_idcategoria'] : 3; // ID de "Ninguna" para categoría
    
    // Verificar si se envió un descuento válido
    $descuento_id = isset($_POST['descuento_iddescuento']) && !empty($_POST['descuento_iddescuento']) ? $_POST['descuento_iddescuento'] : 3; // ID de "Sin descuento"

    // ✅ Agregamos la columna fecha_vencimiento
    $query = "INSERT INTO producto 
        (nombre, precio, codigo, fecha, descripcion, cantidad, marca_idmarca, categoria_idcategoria, descuento_iddescuento, fecha_vencimiento) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $db->conn->prepare($query)) {
        $stmt->bind_param("sdsssiisis", $nombre, $precio, $codigo, $fecha, $descripcion, $cantidad, $marca_id, $categoria_id, $descuento_id, $fecha_vencimiento);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el producto.']);
        }

        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la preparación de la consulta.']);
        die("Error en la preparación de la consulta: " . $db->conn->error);
    }

    $db->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Método no permitido.']);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
