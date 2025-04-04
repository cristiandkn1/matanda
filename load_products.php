<?php
require_once 'db.php';

$database = new Database();
$conn = $database->conn;

// Verifica que la conexión sea exitosa
if (!$conn) {
    http_response_code(500); // Código de error para el cliente
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

// Construir la consulta SQL con filtro de visibilidad
$query = "
    SELECT 
        p.idproducto,
        p.nombre,
        p.precio,
        p.codigo,
        p.fecha,
        p.fecha_vencimiento, -- ✅ Nueva columna
        p.descripcion,
        p.cantidad,
        m.nombre AS marca,
        c.nombre AS categoria,
        d.nombre AS nombre_descuento,
        d.porcentaje AS descuento,
        IF(d.fecha < NOW() AND d.porcentaje > 0, 
            p.precio - (p.precio * (d.porcentaje / 100)), 
            p.precio
        ) AS precio_ordenado
    FROM producto p
    LEFT JOIN marca m ON p.marca_idmarca = m.idmarca
    LEFT JOIN categoria c ON p.categoria_idcategoria = c.idcategoria
    LEFT JOIN descuento d ON p.descuento_iddescuento = d.iddescuento
    WHERE p.visible = 1
";

// Ejecutar la consulta
$result = $conn->query($query);

// Verificar si la consulta fue exitosa
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta SQL: ' . $conn->error]);
    exit;
}

// Configura el encabezado para JSON
header('Content-Type: application/json');

// Procesar los resultados
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Devuelve los datos en formato JSON
echo json_encode(['data' => $products]);

// Cierra la conexión
$database->close();
?>
