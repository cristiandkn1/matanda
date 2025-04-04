<?php
require_once 'db.php';

$database = new Database();
$conn = $database->conn;

// Consulta para obtener los productos con los datos necesarios
$query = "
    SELECT 
        p.idproducto AS id,
        p.nombre AS name,
        p.precio AS price,
        p.cantidad AS stock,
        d.porcentaje AS discount
    FROM producto p
    LEFT JOIN descuento d ON p.descuento_iddescuento = d.iddescuento
";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode([
        "data" => $products
    ]);
} else {
    echo json_encode([
        "data" => []
    ]);
}

$database->close();
?>
