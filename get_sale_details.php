<?php
require_once 'db.php';

$database = new Database();
$conn = $database->conn;

$saleId = isset($_GET['idventa']) ? intval($_GET['idventa']) : 0;

if ($saleId > 0) {
    $query = "SELECT 
                p.nombre, 
                vd.cantidad, 
                vd.precio, 
                vd.descuento, 
                vd.subtotal, 
                v.fecha, 
                v.voucher, 
                v.reparto,  -- ✅ Agregamos reparto aquí
                COALESCE(fp.nombre, 'No especificado') AS forma_pago
              FROM venta_detalle vd
              INNER JOIN producto p ON vd.producto_idproducto = p.idproducto
              INNER JOIN venta v ON vd.venta_idventa = v.idventa
              LEFT JOIN pago fp ON v.pago_idpago = fp.idpago
              WHERE v.idventa = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['voucher'] = $row['voucher'] ?? 'No registrado';

        // Calcular valores sin IVA
        $row['precio_sin_iva'] = round($row['precio'] / 1.19, 2);
        $row['descuento_sin_iva'] = round($row['descuento'] / 1.19, 2);
        $row['subtotal_sin_iva'] = round($row['subtotal'] / 1.19, 2);

        // Precio final con descuento
        $row['precio_final'] = round($row['precio'] - $row['descuento'], 2);

        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    $stmt->close();
} else {
    echo json_encode([]);
}

$conn->close();
