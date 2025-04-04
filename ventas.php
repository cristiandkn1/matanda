<?php
// Iniciar la sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
// Verificar si el usuario tiene rol "usuario"
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    // Si no tiene permiso, redirigir o mostrar error
    header('Location: login.php'); // o usar index.php, login.php, etc.
    exit;
}

// Conectar a la base de datos (solo si el usuario está autenticado)
require_once 'db.php';
date_default_timezone_set('America/Santiago');

$database = new Database();
$conn = $database->conn;
// Obtener lista de productos visibles con descuento asociado
$productos = [];
$queryProductos = "SELECT 
    p.idproducto, 
    p.nombre, 
    p.precio, 
    p.cantidad, 
    p.codigo, 
    p.visible, 
    d.porcentaje, 
    d.nombre AS nombre_descuento 
FROM producto p
LEFT JOIN descuento d ON p.descuento_iddescuento = d.iddescuento
WHERE p.visible = 1";

$result = $conn->query($queryProductos);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
    die("Error en la consulta: " . $conn->error);
}

// ✅ Obtener montos desde metodo_pago_monto (CORRECCIÓN MÍNIMA)
$montos = ["efectivo" => 0, "transferencia" => 0, "debito" => 0, "credito" => 0];

$queryMontos = "SELECT metodo, SUM(monto) AS total FROM metodo_pago_monto GROUP BY metodo";
$resultMontos = $conn->query($queryMontos);

if ($resultMontos) {
    while ($fila = $resultMontos->fetch_assoc()) {
        switch (strtolower($fila["metodo"])) {
            case "efectivo":
                $montos["efectivo"] = (float) $fila["total"];
                break;
            case "transferencia":
                $montos["transferencia"] = (float) $fila["total"];
                break;
            case "debito":
                $montos["debito"] = (float) $fila["total"];
                break;
            case "credito":
                $montos["credito"] = (float) $fila["total"];
                break;
        }
    }
} else {
    die("Error en la consulta de montos: " . $conn->error);
}

// Aplicar descuento manual adicional sin reemplazar descuentos anteriores
if (isset($_POST['manual_discount'])) {
    $manualDiscount = (float)$_POST['manual_discount'];
    $_SESSION['manual_discount'] = ($_SESSION['manual_discount'] ?? 0) + $manualDiscount;
} else {
    $manualDiscount = $_SESSION['manual_discount'] ?? 0;
}

// Cerrar conexión a la base de datos
$database->close();
?>










<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=800, user-scalable=yes">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Ferretería</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style2.css">

    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Scripts de DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>
    <!-- Agrega esto en tu HTML si aún no tienes SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<header style="display: flex; justify-content: center; align-items: center; padding: 10px;  color: white; position: relative;">
<nav>
    <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 20px;">
        <li><a href="index.php" style="color: white; text-decoration: none;">
            <i class="fas fa-box"></i> Inventario
        </a></li>
        <li><a href="ventas.php" style="color: white; text-decoration: none;">
            <i class="fas fa-money-bill-wave"></i> Ventas
        </a></li>
        <li><a href="historial.php" style="color: white; text-decoration: none;">
            <i class="fas fa-file-alt"></i> Historial
        </a></li>
        <li><a href="bodega.php" style="color: white; text-decoration: none;">
            <i class="fas fa-chart-line"></i> Gestión
        </a></li>
        <li><a href="reparto.php" style="color: white; text-decoration: none;">
            <i class="fas fa-truck"></i> Reparto
        </a></li>
        <li><a href="desechos.php" style="color: white; text-decoration: none;">
            <i class="fas fa-trash"></i> Desechos
        </a></li>
        <li><a href="usuario.php" style="color: white; text-decoration: none;">
            <i class="fas fa-user"></i> Usuarios
        </a></li>
    </ul>
</nav>

    <!-- Contenedor para el botón de cerrar sesión -->
    <div class="logout-container">
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Cerrar Sesión</button>
        </form>
    </div>
</header>




    <h1>Registro de Ventas</h1>

    <form id="salesForm">
        <div>
            <label for="searchByCode">Buscar por código:</label>
            <input type="text" id="searchByCode" class="form-control" placeholder="Escanea el código de barras aquí">
        </div>

        <div>
        <label for="productSelect">Seleccionar Producto:</label>
<select id="productSelect" class="form-control" style="width: 100%;" required>
    <option value="" disabled selected>Seleccione un producto</option>
    <?php foreach ($productos as $producto): ?>
        <option 
    value="<?= htmlspecialchars($producto['idproducto']) ?>" 
    data-precio="<?= htmlspecialchars($producto['precio']) ?>" 
    data-stock="<?= htmlspecialchars($producto['cantidad']) ?>" 
    data-codigo="<?= htmlspecialchars($producto['codigo']) ?>" 
    data-visible="<?= htmlspecialchars($producto['visible']) ?>" 
    data-descuento="<?= htmlspecialchars($producto['porcentaje'] ?? 0) ?>" 
    data-descuento-nombre="<?= htmlspecialchars($producto['nombre_descuento'] ?? '') ?>"
    data-nombre="<?= htmlspecialchars($producto['nombre']) ?>">
    <?= htmlspecialchars($producto['nombre']) ?> - $<?= number_format($producto['precio'], 0, ',', '.') ?> (Stock: <?= htmlspecialchars($producto['cantidad']) ?>)
    <?= ($producto['porcentaje'] > 0) ? ' - ' . htmlspecialchars($producto['porcentaje']) . '% Dcto - ' . htmlspecialchars($producto['nombre_descuento']) : '' ?>
</option>
    <?php endforeach; ?>
</select>


        </div>

        <div>
            <label for="quantity">Cantidad:</label>
            <input type="number" id="quantity" class="form-control" min="1" value="1" onfocus="if(this.value=='') this.value='1';" required>
            </div>

        <button type="button" id="addProduct" class="btn btn-primary">Agregar al Carrito</button>
        <button type="button" id="addManualProductButton" class="btn btn-secondary">Agregar Producto Manual</button>



        </form>
        <h2>Carrito de Compras</h2>
<table id="cartTable" class="table table-striped table-bordered">
    <thead>
        <tr class="text-center">
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Precio Dcto</th>
            <th>Subtotal</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <!-- Aquí se agregan los productos dinámicamente -->
    </tbody>
    <tfoot>



   <tr id="totalWithTaxRow">
    <th colspan="5" class="text-end bg-info text-white display-4 py-4">Total:</th>
    <th id="totalWithTax" class="text-center display-4 py-4">$0</th>
    <th></th>
</tr>




        <tr id="discountAutomaticRow">
            <th colspan="5" class="text-end bg-light">Descuento aplicado:</th>
            <th id="discountAutomaticAmount" class="text-center">$0</th>
            <th></th>
        </tr>

        <tr id="totalRow" style="display: none;">
            <th colspan="5" class="text-end bg-primary text-white">Total con Descuentos:</th>
            <th id="totalAmount" class="text-center">$0</th>
            <th></th>
        </tr>

        <tr id="totalWithoutTaxRow">
            <th colspan="5" class="text-end bg-warning">Total sin IVA (19% menos):</th>
            <th id="totalWithoutTax" class="text-center">$0</th>
            <th></th>
        </tr>
    </tfoot>
</table>



    <div class="payment-container">
    <div>
        <label for="paymentMethod">Método de Pago:</label>
        <select id="paymentMethod" class="form-control" name="paymentMethod">
    <option value="">Seleccionar...</option>
    <option value="2">Efectivo</option>
    <option value="3">Transferencia</option>
    <option value="1">Débito</option>
    <option value="4">Crédito</option>
</select>
    </div>




<!-- Input para Voucher de Operación (Oculto por defecto) -->
<div id="voucherContainer" style="display: none;">
        <label for="voucherCode">Voucher de Operación:</label>
        <input type="text" id="voucherCode" class="form-control" name="voucherCode" placeholder="Ingrese el código de voucher">
    </div>


    <div>
        <button id="processSale" class="btn btn-success">Procesar Venta</button>
    </div>
</div>








<style>
.modal-descuento {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content-descuento {
    background: white;
    padding: 40px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
    width: 450px;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.discount-input {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}
.close-descuento {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 20px;
    cursor: pointer;
}
</style>


<!-- Modal para agregar producto manual -->
<div id="manualProductModal" class="modal">
    <div class="modal-content">
        <span id="closeManualProductModal" class="close">&times;</span>
        <h3>Agregar Producto Manual</h3>
        <form id="manualProductForm">
            <div>
                <label for="manualProductName">Nombre del Producto:</label>
                <input type="text" id="manualProductName" class="form-control" placeholder="Nombre del producto" required>
            </div>
            <div>
                <label for="manualProductPrice">Precio Unitario:</label>
                <input type="number" id="manualProductPrice" class="form-control" placeholder="Precio" min="0" step="0.01" required>
            </div>
            <div>
                <label for="manualProductQuantity">Cantidad:</label>
                <input type="number" id="manualProductQuantity" class="form-control" placeholder="Cantidad" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Agregar al Carrito</button>
        </form>
    </div>
</div>


</main>




<p class="caja-monto">Monto en caja: $0</p>

<button class="btn-hacer-caja">Hacer Caja</button>



<div class="contenedor-montos">
    <p class="monto-pago" id="monto-efectivo">Efectivo: $0</p>
    <p class="monto-pago" id="monto-transferencia">Transferencia: $0</p>
    <p class="monto-pago" id="monto-debito">Débito: $0</p>
    <p class="monto-pago" id="monto-credito">Crédito: $0</p>
    
</div>




<button id="cerrarVentaDia">Generar Reporte del Dia</button>
<p></p>
<button id="limpiarVentaDia">Cerrar Ventas del Día</button>



<!-- Modal para ingresar el monto de la caja -->
<div class="modal-caja">
    <div class="modal-caja-contenido">
        <span class="modal-caja-cerrar">&times;</span>
        <h2 class="modal-caja-titulo">Agregar Monto a la Caja</h2>
        <label class="modal-caja-label" for="cajaInput">Ingrese el monto inicial:</label>
        <input type="number" class="modal-caja-input" placeholder="Ingrese el monto" min="0">
        <div class="modal-caja-botones">
            <button class="btn-guardar-caja">Guardar</button>
            <button class="btn-limpiar-caja">Limpiar</button>
        </div>
    </div>
</div>




<script>
$(document).ready(function () {
    // --- VARIABLES GLOBALES ---
    let cart = []; // Array para almacenar los productos del carrito
    let monthlySales = {}; // Objeto para almacenar las ventas del mes
    let barcodeTimer; // Variable para el temporizador del escáner
    

    // --- FUNCIÓN PARA FORMATEAR OPCIONES CON DESCUENTO ---
    function formatProductOption(option) {
        if (!option.id) {
            return option.text;
        }

        const descuento = $(option.element).attr('data-descuento');
        const $option = $('<span>' + option.text + '</span>');

        if (descuento > 0) {
            $option.css({
                'color': '#28a745', // Verde oscuro
                'font-weight': 'bold'
            });
        }

        return $option;
    }

    // --- INICIALIZACIÓN SELECT2 ---
    $('#productSelect').select2({
        language: 'es',
        templateResult: formatProductOption,  // Personaliza las opciones desplegadas
        templateSelection: formatProductOption // Personaliza la opción seleccionada
    });

    // --- OCULTAR PRODUCTOS CON VISIBLE=0 ---
    $('#productSelect option').each(function () {
        if (Number($(this).data('visible')) === 0) {
            $(this).remove();
        }
    });

    // --- BÚSQUEDA AUTOMÁTICA DE PRODUCTOS POR CÓDIGO ---
    $('#searchByCode').on('input', function () {
        clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(() => processBarcode(), 100);
    });

    $('#searchByCode').on('keypress', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            processBarcode();
        }
    });



    //separacion x d
    function processBarcode() {
        const code = $('#searchByCode').val().trim();
        if (!code) return;
        
        let found = false;
        $('#productSelect option').each(function () {
            if ($(this).data('codigo') == code) {
                if ($(this).data('stock') <= 0) {
                    alert('Producto sin stock disponible.');
                    return false;
                }
                $(this).prop('selected', true);
                $('#productSelect').trigger('change');
                $('#searchByCode').val('');
                $('#quantity').val(1);
                
                setTimeout(() => addToCart(), 100);
                found = true;
                return false;
            }
        });
        
        if (!found) alert('Producto no encontrado con ese código.');
    }
// --- ARRAY DEL CARRITO ---








// --- AGREGAR PRODUCTOS AL CARRITO (PRODUCTOS SELECCIONADOS) ---
function addToCart() {
    const selectedOption = $('#productSelect').find(':selected');
    const productId = selectedOption.val();
    const productPrice = parseFloat(selectedOption.attr('data-precio'));
    let productStock = parseInt(selectedOption.attr('data-stock'));
    const discountPercentage = parseFloat(selectedOption.attr('data-descuento')) || 0;
    const discountName = selectedOption.attr('data-descuento-nombre') || '';
    const quantity = parseInt($('#quantity').val());

    if (!productId || isNaN(quantity) || quantity <= 0 || quantity > productStock) {
        return;
    }

    // Restar Stock
    productStock -= quantity;
    selectedOption.attr('data-stock', productStock);

    // Reconstruir el texto con toda la información
    let productText = `${selectedOption.attr('data-nombre')} - $${productPrice.toLocaleString('es-CL')} (Stock: ${productStock})`;
    if (discountPercentage > 0) {
        productText += ` - ${discountPercentage}% Dcto - ${discountName}`;
    }

    selectedOption.text(productText);

    // Volver a inicializar Select2 con formato para mantener el estilo
    $('#productSelect').select2({
        language: 'es',
        templateResult: formatProductOption,
        templateSelection: formatProductOption
    }).trigger('change.select2'); // Dispara el evento para refrescar la opción seleccionada

    // Calcular Precio con Descuento
    let discountPrice = productPrice;
    if (discountPercentage > 0) {
        discountPrice = productPrice - (productPrice * (discountPercentage / 100));
    }

    // Buscar si el producto ya existe en el carrito
    const existingProduct = cart.find(item => item.productId === productId);
    if (existingProduct) {
        existingProduct.quantity += quantity;
        existingProduct.subtotal = existingProduct.discountPrice * existingProduct.quantity;
    } else {
        cart.push({
            productId,
            productName: productText,
            quantity,
            productPrice,
            discountPrice,
            discountPercentage,
            subtotal: discountPrice * quantity
        });
    }

    updateCart();
    $('#stockDisplay').text(`Stock disponible: ${productStock}`);

    // Deshabilitar opción si el stock llega a 0
    if (productStock === 0) {
        selectedOption.prop('disabled', true);
        $('#productSelect').select2({
            language: 'es',
            templateResult: formatProductOption,
            templateSelection: formatProductOption
        });
    }
}









// --- AGREGAR PRODUCTOS MANUALES AL CARRITO ---
function addManualProduct(event) {
    event.preventDefault();

    const productName = $('#manualProductName').val();
    const productPrice = parseFloat($('#manualProductPrice').val());
    const quantity = parseInt($('#manualProductQuantity').val());

    if (!productName || isNaN(productPrice) || quantity <= 0 || isNaN(quantity)) {
        alert('Por favor, complete todos los campos correctamente.');
        return;
    }

    cart.push({
        productId: 'manual_' + Date.now(),
        productName,
        productPrice,
        quantity,
        discountPrice: productPrice, // No tiene descuento manual
        discountPercentage: 0,
        subtotal: productPrice * quantity
    });

    updateCart();
    $('#manualProductModal').hide();
    $('#manualProductForm')[0].reset();
}

// --- ACTUALIZAR CARRITO ---
function updateCart() {
    const tbody = $('#cartTable tbody').empty();
    let subtotal = 0;
    let totalDiscount = 0;
    let totalWithoutDiscount = 0;

    cart.forEach((item, index) => {
        totalWithoutDiscount += item.productPrice * item.quantity;
        subtotal += item.subtotal;

        if (item.discountPercentage > 0) {
            totalDiscount += (item.productPrice - item.discountPrice) * item.quantity;
        }

        tbody.append(`
            <tr>
                <td>${item.productName}</td>
                <td>${item.quantity}</td>
                <td>${item.productPrice.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' })}</td>
                <td class="text-success fw-bold">${item.discountPrice.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' })}</td>
                <td>${item.subtotal.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' })} 
                    ${item.discountPercentage > 0 ? `<span class="badge bg-success ms-1">Dcto ${item.discountPercentage}%</span>` : ''}
                </td>
                <td>
                    <button class="btn btn-danger btn-sm remove-item" 
                            data-index="${index}" 
                            data-id="${item.productId}" 
                            data-quantity="${item.quantity}">
                        Eliminar
                    </button>
                </td>
            </tr>
        `);
    });

    const totalConIVA = totalWithoutDiscount;
    const totalSinIVA = subtotal / 1.19;

    $('#discountAutomaticAmount').text(totalDiscount.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' }));
    $('#totalAmount').text(subtotal.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' }));
    $('#totalWithoutTax').text(totalSinIVA.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' }));
    $('#totalWithTax').text(totalConIVA.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' }));

    $('.remove-item').on('click', function () {
        const index = $(this).data('index');
        cart.splice(index, 1);
        updateCart();
    });


    if (totalDiscount > 0) {
        $('#totalRow').show();
        $('#discountAutomaticRow').show();
    } else {
        $('#totalRow').hide();
        $('#discountAutomaticRow').hide();
    }

    $('#totalWithTax').text(totalConIVA.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' }));
    $('#totalWithTaxRow th').css({
        'font-size': '1.1rem',
        'font-weight': 'bold'
    });

    $('.remove-item').on('click', function () {
        const index = $(this).data('index');
        const productId = $(this).data('id');
        const quantityToRestore = parseInt($(this).data('quantity'));

        restoreStock(productId, quantityToRestore);
        cart.splice(index, 1);
        updateCart();
    });
}





$(document).ready(function () {
    // --- MOSTRAR U OCULTAR EL INPUT DE VOUCHER SEGÚN MÉTODO DE PAGO ---
    $('#paymentMethod').change(function () {
        const paymentMethod = $(this).val();
        if (paymentMethod === "1" || paymentMethod === "4") { // Solo Débito y Crédito requieren voucher
            $('#voucherContainer').show();
        } else {
            $('#voucherContainer').hide().find('input').val(''); // Ocultar y limpiar input
        }
    });

    // --- PROCESAR VENTA ---
$('#processSale').click(function () {
    if (typeof cart === 'undefined' || cart.length === 0) {
        return;
    }

    const paymentMethod = $('#paymentMethod').val();
    if (!paymentMethod) {
        alert('Debe seleccionar una forma de pago.');
        return;
    }

    let voucherCode = $('#voucherCode').val().trim();

    // Validar si se requiere voucher (Débito y Crédito, pero NO Transferencia)
    if ((paymentMethod === "1" || paymentMethod === "4") && voucherCode === '') {
        alert('Debe ingresar el voucher de operación para Débito o Crédito.');
        return;
    }

    // Si el método de pago no requiere voucher, enviamos NULL
    if (!(paymentMethod === "1" || paymentMethod === "4")) {
        voucherCode = null;
    }

    // Obtener el descuento aplicado
    let discount = parseFloat($('#discountInput').val()) || 0;

    // Calcular el total con el descuento aplicado
    let total = cart.reduce((acc, item) => acc + item.subtotal, 0) - discount;

    if (total < 0) {
        alert('El descuento no puede ser mayor al total de la venta.');
        return;
    }

    // **Si el método de pago es efectivo, preguntar por el vuelto**
    if (paymentMethod === "2") { // "2" es la opción de efectivo
        if (confirm('¿Desea calcular el vuelto?')) {
            const amountReceived = parseFloat(prompt('Ingrese el monto recibido en efectivo:', ''));
            
            if (isNaN(amountReceived) || amountReceived < total) {
                alert('El monto recibido es insuficiente o inválido.');
                return;
            }
            
            const change = amountReceived - total;
            alert(`El vuelto a entregar es: ${change.toLocaleString('es-CL', { style: 'currency', currency: 'CLP' })}`);
        }
    }

    // **Procesar la venta**
    $.post('process_sale.php', { cart: JSON.stringify(cart), paymentMethod, voucherCode, total, discount }, function (response) {
    if (response.success) {
        Swal.fire({
            title: '¡Venta procesada exitosamente!',
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });

        cart = [];
        updateCart();
        if (typeof updateMonthlySales === 'function') {
            updateMonthlySales();
        }
        $('#voucherCode').val('');
        
        // 🔹 Resetear el select del producto a la opción por defecto
        $('#productSelect').val('').trigger('change');
        actualizarMontos(); 

        // 🔹 Reiniciar el descuento después de la venta
        $('#discountInput').val('0');
        $('#discountAmount').text('-$0');
    } else {
        Swal.fire({
            title: 'Error al procesar la venta',
            text: response.error,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    }
}, 'json').fail(() => {
    Swal.fire({
        title: 'Hubo un error al procesar la venta',
        text: 'Inténtalo nuevamente.',
        icon: 'error',
        confirmButtonText: 'Aceptar'
    });
});
})


function restoreStock(productId, quantity) {
    const option = $(`#productSelect option[value="${productId}"]`);
    if (option.length) {
        let currentStock = parseInt(option.attr('data-stock')) || 0;
        let newStock = currentStock + quantity;
        
        // **Actualizar el stock en el DOM**
        option.attr('data-stock', newStock);
        option.text(`${option.text().split(' (Stock:')[0]} (Stock: ${newStock})`);

        // **Habilitar la opción si estaba deshabilitada**
        option.prop('disabled', false);
        $('#productSelect').select2(); // Refrescar select2 si lo usas
    }
}

// --- EVENTOS ---
$('#addProduct').on('click', addToCart);
$('#manualProductForm').submit(addManualProduct);
$('#addManualProductButton').on('click', () => $('#manualProductModal').show());

// Cerrar modal al hacer clic fuera del contenido
$(window).on('click', function (event) {
    const modal = $('#manualProductModal');
    if (event.target === modal[0]) {
        modal.hide();
    }
});




    function updateMonthlySales() {
        if (typeof monthlySales === 'undefined') return;

        $('#monthlySalesList').empty();
        Object.entries(monthlySales).forEach(([productId, quantity]) => {
            const productName = $('#productSelect').find(`option[value="${productId}"]`).text() || 'Producto Manual';
            $('#monthlySalesList').append(`<li>${productName}: ${quantity} vendidos</li>`);
        });
    }

});

$(document).ready(function () {
    $('.modal-caja').hide();

    let montoGuardado = localStorage.getItem('montoCaja');
    if (montoGuardado) {
        $('.caja-monto').text("Monto en caja: $" + parseFloat(montoGuardado).toLocaleString('es-CL'));
    }

    $('.btn-hacer-caja').click(function () {
        Swal.fire({
            title: 'Ingrese la contraseña',
            input: 'password', // Oculta la contraseña
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            showDenyButton: true, // Botón automático
            confirmButtonText: 'Aceptar',
            denyButtonText: 'Automático', // Texto del botón automático
            cancelButtonText: 'Cancelar',
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage('Debe ingresar una contraseña');
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let password = result.value;

                fetch('verificar_contraseña.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: password })
                })
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        $('.modal-caja').fadeIn();
                    } else {
                        Swal.fire('Error', 'Contraseña incorrecta. No tienes permiso para hacer caja.', 'error');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else if (result.isDenied) {
                localStorage.setItem('montoCaja', 45000);
                $('.caja-monto').text("Monto en caja: $" + (45000).toLocaleString('es-CL'));
                Swal.fire('Automático', 'Se ha agregado automáticamente $45,000 a la caja', 'success');
            }
        });
    });

    $('.modal-caja-cerrar').click(function () {
        $('.modal-caja').fadeOut();
    });

    $(document).mouseup(function (e) {
        let modalContent = $('.modal-caja-contenido');
        if (!modalContent.is(e.target) && modalContent.has(e.target).length === 0) {
            $('.modal-caja').fadeOut();
        }
    });

    $('.btn-guardar-caja').click(function () {
        let montoCaja = parseFloat($('.modal-caja-input').val());

        if (isNaN(montoCaja) || montoCaja < 0) {
            Swal.fire('Error', 'Ingrese un monto válido.', 'error');
            return;
        }

        localStorage.setItem('montoCaja', montoCaja);
        $('.caja-monto').text("Monto en caja: $" + montoCaja.toLocaleString('es-CL'));
        $('.modal-caja').fadeOut();
        $('.modal-caja-input').val('');
    });

    $('.btn-limpiar-caja').click(function () {
        localStorage.setItem('montoCaja', 0);
        $('.caja-monto').text('Monto en caja: $0');
        $('.modal-caja-input').val('');
        $('.modal-caja').fadeOut();
    });
});})


</script>







<script>
    //aviso AL AGREGAR UN PRODUCTO AL CARRITO
document.getElementById("addProduct").addEventListener("click", function () {
    const productSelect = document.getElementById("productSelect");
    const quantityInput = document.getElementById("quantity");
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const stock = parseInt(selectedOption.dataset.stock);
    const cantidad = parseInt(quantityInput.value);
    const productName = selectedOption.text;

    if (cantidad > stock) {
        Swal.fire({
            icon: "error",
            title: "Stock insuficiente",
            text: "No se puede añadir más cantidad, Debido a que hay " + stock + " unidades disponibles."
        });
    } else {
        // Reproducir sonido pop
        const popSound = new Audio("./pop.mp3"); // Misma carpeta
        popSound.volume = 1.0; // Máximo volumen
        popSound.play();

        // Notificación tipo Toast
        Swal.fire({
            toast: true,
            position: 'bottom',
            icon: 'success',
            title: `Se ha añadido ${cantidad} unidades de ${productName} al carrito`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            background: '#28a745', // Verde bonito
            color: '#ffffff' // Texto blanco
        });
    }
});
</script>










<script>
   document.addEventListener('DOMContentLoaded', () => {
    // ✅ Llamar a actualizarMontos() solo si la página ha cargado
    actualizarMontos();

    // ✅ Manejar el botón de cerrar ventas si existe en la página
    let btnCerrarVenta = document.getElementById('cerrarVentaDia');
    if (btnCerrarVenta) {
        btnCerrarVenta.addEventListener('click', function () {
            fetch('cerrar_venta_dia.php')
                .then(response => response.blob())
                .then(blob => {
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = "reporte_ventas_dia.pdf";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                })
                .catch(error => console.error('Error:', error));
        });
    }

    // ✅ Manejar el botón de limpiar ventas si existe en la página
let btnLimpiarVenta = document.getElementById('limpiarVentaDia');
if (btnLimpiarVenta) {
    btnLimpiarVenta.addEventListener('click', function () {
        Swal.fire({
            title: '¿Descargaste el reporte del día?',
            text: "Antes de eliminar las ventas del día, asegúrate de haber descargado el reporte en PDF. ¿Confirmas que ya lo hiciste?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((confirmacion) => {
            if (!confirmacion.isConfirmed) return;

            // Si confirma, pedir la contraseña
            Swal.fire({
                title: 'Ingrese la contraseña',
                input: 'password',
                inputAttributes: { autocapitalize: 'off' },
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Debe ingresar una contraseña');
                    }
                    return password;
                }
            }).then((result) => {
                if (!result.isConfirmed || !result.value) return;

                let password = result.value;

                fetch('limpiar_venta_dia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: password })
                })
                .then(response => response.text())
                .then(message => {
                    Swal.fire('Resultado', message, message.includes("han sido cerradas") ? 'success' : 'error');

                    if (message.includes("han sido cerradas")) {
                        // 🔥 Actualiza los montos de ventas
                        actualizarMontos();

                        // 🔥 Limpia la caja a $0
                        localStorage.setItem('montoCaja', 0);
                        $('.caja-monto').text("Monto en caja: $0");
                        console.log("Caja y montos reiniciados a 0 correctamente");
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    });
}

});

// ✅ Función para obtener los montos y actualizar la UI
// ✅ Función para obtener los montos y actualizar la UI
function actualizarMontos() {
    fetch('get_payment_totals.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Error en la consulta de montos:", data.error);
                return;
            }

            console.log("Montos actualizados desde la BD:", data);

            function formatearMonto(monto) {
                return Math.floor(monto).toLocaleString('es-ES');
            }

            // Actualizar los métodos de pago
            document.getElementById('monto-efectivo').textContent = `Efectivo: $${formatearMonto(data.efectivo)}`;
            document.getElementById('monto-transferencia').textContent = `Transferencia: $${formatearMonto(data.transferencia)}`;
            document.getElementById('monto-debito').textContent = `Débito: $${formatearMonto(data.debito)}`;
            document.getElementById('monto-credito').textContent = `Crédito: $${formatearMonto(data.credito)}`;

            // Solo calcula el total, pero **NO** lo asigna a .caja-monto
            let total = parseFloat(data.efectivo) + parseFloat(data.transferencia) + parseFloat(data.debito) + parseFloat(data.credito);
            console.log("Total ventas del día:", total);
        })
        .catch(error => console.error("Error al obtener montos:", error));
}

// ✅ Función para resetear montos
function resetearMontos() {
    fetch('reset_montos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("✅ Montos reseteados a 0.");
                // Limpia la interfaz con valores a 0
                document.getElementById('monto-efectivo').textContent = `Efectivo: $0`;
                document.getElementById('monto-transferencia').textContent = `Transferencia: $0`;
                document.getElementById('monto-debito').textContent = `Débito: $0`;
                document.getElementById('monto-credito').textContent = `Crédito: $0`;
                
                let cajaMonto = document.querySelector('.caja-monto');
                if (cajaMonto) {
                    cajaMonto.textContent = `Monto en caja: $0`;
                }
            } else {
                console.error("❌ Error al resetear montos:", data.error);
            }
        })
        .catch(error => console.error("❌ Error en la solicitud:", error));
}

// ✅ Función para verificar si es la hora de resetear los montos
function verificarHoraReset() {
    let ahora = new Date();
    let horas = ahora.getHours();
    let minutos = ahora.getMinutes();

    if (horas === 23 && minutos === 59) {
        resetearMontos();
    }
}

// ✅ Ejecutar la verificación cada minuto
setInterval(verificarHoraReset, 60000);

// Llamar actualización inicial
document.addEventListener('DOMContentLoaded', actualizarMontos);

</script>


<script>
    $(document).ready(function() {
        $('#desechosTable').DataTable();

        $('#quantity').on('keypress', function(event) {
            if (event.which === 13) {
                event.preventDefault();
                $('#addProduct').click();
            }
        });
    });
</script>

