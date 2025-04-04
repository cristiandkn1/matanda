<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $database = new Database();
    $conn = $database->conn;

    // Consulta del producto
    $query = "SELECT idproducto, nombre, precio, codigo, fecha, fecha_vencimiento, descripcion, cantidad, 
              marca_idmarca AS idmarca, 
              categoria_idcategoria AS idcategoria, 
              descuento_iddescuento AS iddescuento
              FROM producto WHERE idproducto = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // Generar las opciones de marca
        $brandOptions = '';
        $brandQuery = "SELECT idmarca, nombre FROM marca";
        $brandResult = $conn->query($brandQuery);
        while ($row = $brandResult->fetch_assoc()) {
            $selected = ($row['idmarca'] == $product['idmarca']) ? 'selected' : '';
            $brandOptions .= "<option value='{$row['idmarca']}' {$selected}>{$row['nombre']}</option>";
        }

        // Generar las opciones de categoría
        $categoryOptions = '';
        $categoryQuery = "SELECT idcategoria, nombre FROM categoria";
        $categoryResult = $conn->query($categoryQuery);
        while ($row = $categoryResult->fetch_assoc()) {
            $selected = ($row['idcategoria'] == $product['idcategoria']) ? 'selected' : '';
            $categoryOptions .= "<option value='{$row['idcategoria']}' {$selected}>{$row['nombre']}</option>";
        }

        // Generar las opciones de descuento
        $discountOptions = '';
        $discountQuery = "SELECT iddescuento, nombre FROM descuento";
        $discountResult = $conn->query($discountQuery);
        while ($row = $discountResult->fetch_assoc()) {
            $selected = ($row['iddescuento'] == $product['iddescuento']) ? 'selected' : '';
            $discountOptions .= "<option value='{$row['iddescuento']}' {$selected}>{$row['nombre']}</option>";
        }

        // Añadir opciones al producto
        $product['brandOptions'] = $brandOptions;
        $product['categoryOptions'] = $categoryOptions;
        $product['discountOptions'] = $discountOptions;

        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado']);
    }

    $stmt->close();
    $database->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto no proporcionado']);
}
?>
