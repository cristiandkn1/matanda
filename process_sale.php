<?php
header('Content-Type: application/json');
require_once 'db.php'; // Incluir la conexión a la base de datos
date_default_timezone_set('America/Santiago');

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log("🟢 Iniciando proceso de venta...");

// 🔹 Validar si se enviaron los datos requeridos
if (empty($_POST['cart']) || empty($_POST['paymentMethod'])) {
    error_log("🔴 Error: No se enviaron datos del carrito o método de pago.");
    echo json_encode([
        'success' => false,
        'error' => 'Debe seleccionar una forma de pago y agregar productos al carrito.'
    ]);
    exit;
}

// 🔹 Revisar datos enviados
error_log("📌 POST recibido: " . json_encode($_POST));

// Verificar y decodificar el carrito
$cart = json_decode($_POST['cart'], true);
if (!is_array($cart) || count($cart) === 0) {
    error_log("🔴 Error: El carrito está vacío o tiene un formato incorrecto.");
    echo json_encode([
        'success' => false,
        'error' => 'El carrito está vacío o tiene un formato incorrecto.'
    ]);
    exit;
}

$paymentMethod = $_POST['paymentMethod'];
$voucherCode = isset($_POST['voucherCode']) ? trim($_POST['voucherCode']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$reparto = isset($_POST['reparto']) && $_POST['reparto'] === 'Sí' ? 'Sí' : 'No';

// ✅ Cambio importante: Asegurar que se recibe correctamente el descuento
$globalDiscount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
error_log("📌 Descuento global recibido: $globalDiscount");

$database = new Database();
$conn = $database->conn;

// Verificar conexión
if (!$conn) {
    error_log("🔴 Error: No se pudo conectar a la base de datos.");
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos.'
    ]);
    exit;
}

try {
    $conn->begin_transaction();
    error_log("🔵 Transacción iniciada.");

    // 🔹 Calcular monto total de la venta (SIN SUMAR IVA)
    $totalAmount = array_reduce($cart, function ($sum, $item) {
        return $sum + $item['subtotal'];
    }, 0);

    error_log("📊 Total de venta sin agregar IVA: $totalAmount");

    // Aplicar descuento proporcional
    if ($globalDiscount > 0 && $totalAmount > 0) {
        foreach ($cart as $index => $item) {
            $price = floatval($item['productPrice']);
            $quantity = intval($item['quantity']);
            $subtotal = $price * $quantity;
    
            // Aplica el descuento directamente sobre el precio unitario
            $unitDiscount = round(($price * $globalDiscount) / 100, 2);
            $totalDiscount = round($unitDiscount * $quantity, 2);
    
            $cart[$index]['discount'] = $totalDiscount;
            $cart[$index]['subtotal'] = $subtotal - $totalDiscount;
    
            error_log("🔹 Producto {$item['productId']}: Precio $price | Cantidad $quantity | Descuento $totalDiscount");
        }
        $totalAmount = array_sum(array_column($cart, 'subtotal'));
        error_log("📌 Nuevo Total después del descuento: $totalAmount");
    }
    

    // 🔹 Validar si el método de pago requiere voucher
    if (in_array($paymentMethod, ["1", "4"]) && empty($voucherCode)) {
        error_log("🔴 Error: Se requiere un voucher para este método de pago.");
        echo json_encode([
            'success' => false,
            'error' => 'Debe ingresar un voucher de operación para Débito o Crédito.'
        ]);
        exit;
    }

    // 🔹 Insertar la venta en la base de datos con el campo reparto
    $query = "INSERT INTO venta (monto, fecha, pago_idpago, estado, notas, cierre_id, voucher, reparto) 
              VALUES (?, NOW(), ?, 'Completado', ?, NULL, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('dssss', $totalAmount, $paymentMethod, $notes, $voucherCode, $reparto);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar en la tabla venta: " . $stmt->error);
    }
    $ventaId = $stmt->insert_id;
    error_log("✅ Venta registrada con ID: $ventaId");

    // 🔹 Insertar el monto en metodo_pago_monto correctamente
    $query = "INSERT INTO metodo_pago_monto (metodo, monto) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE monto = monto + VALUES(monto)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sd', $paymentMethod, $totalAmount);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar en metodo_pago_monto: " . $stmt->error);
    }
    error_log("✅ Monto registrado en metodo_pago_monto para método $paymentMethod");

    // 🔹 Insertar detalles de la venta y actualizar stock
    // 🔹 Insertar detalles de la venta y actualizar stock
foreach ($cart as $item) {
    $productId = !empty($item['productId']) ? intval($item['productId']) : 0;
    $quantity = $item['quantity'];
    $price = $item['productPrice'];
    $subtotal = $item['subtotal'];
    
    if (isset($item['discount'])) {
        $discount = floatval($item['discount']);
    } else {
        // Obtener el descuento de la base de datos
        $stmt_desc = $conn->prepare("SELECT porcentaje FROM descuento WHERE iddescuento = (SELECT descuento_iddescuento FROM producto WHERE idproducto = ?)");
        $stmt_desc->bind_param('i', $productId);
        $stmt_desc->execute();
        $result_desc = $stmt_desc->get_result();
        
        if ($row_desc = $result_desc->fetch_assoc()) {
            $discount = ($row_desc['porcentaje'] / 100) * $price; // Calculamos el descuento unitario
            $discount = $discount * $quantity; // Multiplicamos por la cantidad
                    } else {
            $discount = 0;
        }
        $stmt_desc->close();
    }

    $priceWithDiscount = $price - $discount; // Aplicamos el descuento al precio
    $voucherCode = !empty($_POST['voucherCode']) ? trim($_POST['voucherCode']) : ''; // Asegurar que sea string

    error_log("💾 Insertando en venta_detalle -> Producto: $productId, Precio: $price, Descuento: $discount, Precio con descuento: $priceWithDiscount, Subtotal: $subtotal");

    // 🔹 Insertar en venta_detalle
    $query = "INSERT INTO venta_detalle (venta_idventa, producto_idproducto, cantidad, precio, descuento, subtotal, voucher) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iidddds', $ventaId, $productId, $quantity, $price, $discount, $subtotal, $voucherCode);
    error_log("💾 Insertando Producto: $productId con descuento calculado: $discount");
    
    if (!$stmt->execute()) {
        throw new Exception("Error al insertar en venta_detalle: " . $stmt->error);
    }
    error_log("✅ Producto $productId agregado a venta $ventaId con descuento $discount");

    // 🔹 Actualizar stock
    if ($productId !== 0) {
        $query = "UPDATE producto SET cantidad = cantidad - ? WHERE idproducto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $quantity, $productId);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar stock para producto $productId: " . $stmt->error);
        }
        error_log("🔄 Stock actualizado para producto $productId");
    }
}

    $conn->commit();
    error_log("✅ Venta procesada exitosamente con reparto: $reparto");

























// 🔸 Emitir DTE temporal de prueba a LibreDTE
try {
    $api_key = 'WDpUaWxpY0VFVTd0Uk04NXVsWVpqYjA4bDdMeW5IV1J0SA=='; // Tu API Key personal
    $url = 'https://libredte.cl/api/dte/documentos/emitir?normalizar=1&formato=json&links=0&email=0';

    $items = [];
    foreach ($cart as $item) {
        $items[] = [
            'NmbItem' => htmlspecialchars($item['productName'], ENT_QUOTES, 'UTF-8'),
            'QtyItem' => intval($item['quantity']),
            'PrcItem' => round(floatval($item['productPrice']), 2)
        ];
    }

    $data = [
        'Encabezado' => [
            'IdDoc' => ['TipoDTE' => 39],
            'Emisor' => ['RUTEmisor' => '76088228-5'],
            'Receptor' => [
                'RUTRecep' => '66666666-6',
                'RznSocRecep' => 'Consumidor Final',
                'DirRecep' => 'Sin dirección',
                'CmnaRecep' => 'Sin comuna'
            ]
        ],
        'Detalle' => $items,
        'LibreDTE' => [
            'extra' => [
                'dte' => [
                    'Encabezado' => [
                        'IdDoc' => [
                            'TermPagoGlosa' => 'DTE de prueba - No tiene validez legal'
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para entorno de pruebas

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("🔴 Error cURL LibreDTE: " . curl_error($ch));
    }
    curl_close($ch);

    error_log("📨 Respuesta cruda de LibreDTE (emitir): " . $response);
    $res = json_decode($response, true);

    if (!empty($res['pdf'])) {
        error_log("✅ DTE de prueba generado correctamente: " . $res['pdf']);
        $stmt = $conn->prepare("UPDATE venta SET boleta_url = ? WHERE idventa = ?");
        $stmt->bind_param("si", $res['pdf'], $ventaId);
        $stmt->execute();
    } else {
        error_log("⚠️ LibreDTE no devolvió PDF: " . $response);
    }

} catch (Exception $e) {
    error_log("🔴 Excepción al emitir DTE de prueba: " . $e->getMessage());
}

































    echo json_encode([
        'success' => true,
        'message' => 'Venta procesada correctamente.',
        'totalAmount' => $totalAmount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("🔴 Error en la venta: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la venta: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $database->close();
}
?>
