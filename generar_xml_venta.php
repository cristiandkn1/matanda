<?php
// generar_xml_venta.php
function generarXMLVenta($conn, $ventaId) {
    // 1. Obtener datos de la venta
    $venta = $conn->query("SELECT * FROM venta WHERE idventa = $ventaId")->fetch_assoc();

    // 2. Obtener detalle de productos
    $detalles = $conn->query("
        SELECT p.nombre, vd.*
        FROM venta_detalle vd
        JOIN producto p ON p.idproducto = vd.producto_idproducto
        WHERE vd.venta_idventa = $ventaId
    ");

    // 3. Crear estructura XML
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    $root = $xml->createElement('Venta');
    $root->setAttribute('version', '1.0'); // o la que exija el servicio

    $xml->appendChild($root);

    $root->appendChild($xml->createElement('ID', $ventaId));
    $root->appendChild($xml->createElement('Fecha', $venta['fecha']));
    $root->appendChild($xml->createElement('Monto', $venta['monto']));
    $root->appendChild($xml->createElement('MetodoPago', $venta['pago_idpago']));
    $root->appendChild($xml->createElement('Reparto', $venta['reparto']));

    $productos = $xml->createElement('Productos');
    while ($fila = $detalles->fetch_assoc()) {
        $producto = $xml->createElement('Producto');
        $producto->appendChild($xml->createElement('Nombre', $fila['nombre']));
        $producto->appendChild($xml->createElement('Cantidad', $fila['cantidad']));
        $producto->appendChild($xml->createElement('PrecioUnitario', $fila['precio']));
        $producto->appendChild($xml->createElement('Descuento', $fila['descuento']));
        $producto->appendChild($xml->createElement('Subtotal', $fila['subtotal']));
        $productos->appendChild($producto);
    }

    $root->appendChild($productos);

    // 4. Guardar el archivo XML
    $ruta = "xml_ventas/venta_$ventaId.xml";
    $xml->save($ruta);

    return $ruta;
}
?>

