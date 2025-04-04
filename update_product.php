<?php
require_once 'db.php';

header('Content-Type: application/json');

// Verifica si los datos obligatorios están presentes
if (isset($_POST['productId'], $_POST['productName'], $_POST['productPrice'], $_POST['productCode'], $_POST['productQuantity'])) {
    
    // Obtener datos del formulario
    $id = intval($_POST['productId']);
    $name = $_POST['productName'];
    $price = floatval($_POST['productPrice']);
    $code = $_POST['productCode'];
    $quantity = intval($_POST['productQuantity']);

    // Manejo de valores opcionales (si no existen, asignar NULL)
    $description = isset($_POST['productDescription']) && $_POST['productDescription'] !== "" ? $_POST['productDescription'] : NULL;
    $brand = isset($_POST['productBrand']) && $_POST['productBrand'] !== "" ? intval($_POST['productBrand']) : NULL;
    $category = isset($_POST['productCategory']) && $_POST['productCategory'] !== "" ? intval($_POST['productCategory']) : NULL;
    $discount = isset($_POST['productDiscount']) && $_POST['productDiscount'] !== "" ? intval($_POST['productDiscount']) : NULL;
    $date = isset($_POST['productDate']) && $_POST['productDate'] !== "" ? $_POST['productDate'] : NULL;

    // ✅ Nuevo: fecha de vencimiento
    $fecha_vencimiento = isset($_POST['fecha_vencimiento']) && $_POST['fecha_vencimiento'] !== "" ? $_POST['fecha_vencimiento'] : NULL;

    // Conexión a la base de datos
    $database = new Database();
    $conn = $database->conn;

    // Construcción de la consulta SQL con valores opcionales
    $query = "UPDATE producto SET 
                nombre = ?, 
                precio = ?, 
                codigo = ?, 
                cantidad = ?, 
                descripcion = ?, 
                marca_idmarca = ?, 
                categoria_idcategoria = ?, 
                descuento_iddescuento = ?, 
                fecha = ?, 
                fecha_vencimiento = ?
              WHERE idproducto = ?";

    // Preparar la consulta
    $stmt = $conn->prepare($query);

    // Enlazar parámetros con posibilidad de valores NULL
    $stmt->bind_param("sdsiisisssi", $name, $price, $code, $quantity, $description, $brand, $category, $discount, $date, $fecha_vencimiento, $id);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Producto actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el producto']);
    }

    // Cerrar conexión
    $stmt->close();
    $database->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para actualizar el producto']);
}
?>
