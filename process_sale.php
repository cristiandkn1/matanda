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





























// 🔸 Datos para enviar a LibreDTE
$api_key = 'TU_API_KEY'; // Reemplaza con la API Key de la empresa cliente
$rut_emisor = '12345678-9'; // RUT empresa sin puntos y con guion
$url = 'https://libredte.cl/api/dte/documento';

// 🔸 Armar lista de ítems
$items = [];
foreach ($cart as $item) {
    $items[] = [
        'NmbItem' => $item['productName'], // Ya viene con nombre + precio en tu JS
        'QtyItem' => $item['quantity'],
        'PrcItem' => round($item['productPrice'], 2),
        'DescuentoMonto' => round(($item['productPrice'] - $item['discountPrice']) * $item['quantity'], 2)
    ];
}

// 🔸 Estructura del DTE
$data = [
    'dte' => [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39 // Boleta electrónica afecta con IVA
            ],
            'Emisor' => [
                'RUTEmisor' => $rut_emisor
            ],
            'Receptor' => [ // Consumidor Final (sin RUT)
                'RUTRecep' => '66666666-6',
                'RznSocRecep' => 'Consumidor Final',
                'DirRecep' => 'Sin dirección',
                'CmnaRecep' => 'Sin comuna'
            ]
        ],
        'Detalle' => $items
    ]
];

// 🔸 Enviar a LibreDTE
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$res = json_decode($response, true);
curl_close($ch);

// 🔸 Procesar respuesta
if (!empty($res['pdf'])) {
    error_log("✅ Boleta generada: " . $res['pdf']);

    // OPCIONAL: guardar en tu base de datos
    $stmt = $conn->prepare("UPDATE venta SET boleta_url = ? WHERE idventa = ?");
    $stmt->bind_param("si", $res['pdf'], $ventaId);
    $stmt->execute();
} else {
    error_log("⚠️ Error al generar boleta: " . $response);
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
