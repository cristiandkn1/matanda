<?php
// Iniciar la sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    // Si no tiene permiso, redirigir o mostrar error
    header('Location: login.php'); // o usar index.php, login.php, etc.
    exit;
}

// Conectar a la base de datos
require_once 'db.php';
$database = new Database();
$conn = $database->conn;

// Definir cuenta administradora
$admin_email = "vega@gmail.com";
$is_admin = ($_SESSION['user_email'] ?? '') === $admin_email;


// Cerrar conexión
$database->close();
?>


<!DOCTYPE html>



<html lang="es">
<head>
    <meta name="viewport" content="width=800, user-scalable=yes">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style3.css">

    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Scripts de DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
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

    <!-- Botón de cerrar sesión (Posicionable libremente) -->
    <form action="logout.php" method="POST" style="position: absolute; right: 20px;">
        <button type="submit" style="background: red; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px;">Cerrar Sesión</button>
    </form>
</header>



<main>
<h1>Historial de Ventas</h1>

<!-- Filtros para ventas -->
<div class="filter-container">
    <div class="filter-group">
        <label>Precio Mínimo:</label>
        <input type="number" id="minPrice" placeholder="Mínimo">
    </div>

    <div class="filter-group">
        <label>Precio Máximo:</label>
        <input type="number" id="maxPrice" placeholder="Máximo">
    </div>

    <button id="clearPriceFilter">Limpiar</button>
</div>

<div class="filter-container">
    <div class="filter-group">
        <label>Fecha Inicio:</label>
        <input type="datetime-local" id="startDate">
    </div>

    <div class="filter-group">
        <label>Fecha Fin:</label>
        <input type="datetime-local" id="endDate">
    </div>

    <button id="clearDateFilter">Limpiar</button>

</div>
<!-- Contenedor único para mostrar el resumen de ventas -->
<div id="salesSummary" class="summary-container">
    <!-- Aquí se inyectará dinámicamente el contenido de resumen de ventas -->
</div>

<style>
    .summary-container {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1.2em;
        font-weight: bold;
        position: relative;
        left: 790px; /* ✅ Desplazamiento efectivo hacia la derecha */
        display: inline-block; /* ✅ Asegura que se comporte como bloque ajustable */
    }

    .summary-details {
        font-size: 0.9em;
        font-weight: normal;
        color: #555;
        margin-top: 5px;
    }
</style>

<!-- Contenedor para la tabla de historial de ventas -->
<div class="table-container">
    <table id="salesHistoryTable" class="display">
        <thead>
            <tr>
                <th>ID Venta</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Método de Pago</th>
                <th>Estado</th>
                <th>Notas</th>
                <th>Reparto</th> <!-- ✅ Nueva columna -->
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Los datos se llenarán dinámicamente -->
        </tbody>
    </table>
</div>

    <!-- Modal para edición -->
    <div id="editSaleOverlay"></div>
    <div id="editSaleModal" class="edit-modal" style="display: none;">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Editar Venta</h2>
            <form id="editSaleForm">
                <div style="margin-bottom: 15px;">
                    <label><strong>Estado:</strong></label>
                    <select id="editSaleStatus" class="form-control" style="width: 100%;"></select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label><strong>Notas:</strong></label>
                    <textarea id="editNotes" class="form-control" style="width: 100%;"></textarea>
                </div>
                <div style="text-align: center;">
                    <button type="button" id="saveEdit" class="btn btn-primary">Guardar Cambios</button>
                    <?php if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === "vega@gmail.com") : ?>
                        <button type="button" id="deleteSale" class="btn btn-danger">Eliminar Venta</button>
                    <?php endif; ?>
                    <button type="button" id="cancelEdit" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</main>




<div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;">
    <div id="saleDetailsModal" style="overflow-y: auto; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; padding: 20px; border: 1px solid #ccc; border-radius: 8px; width: 80%; max-width: 600px; background-color: #fff;">
        <h1 id="modalTitle" style="text-align: center;">Orden de Compra</h1>
        <div style="margin-bottom: 20px;">
            <h2>Nombre de la Empresa: <span style="font-weight: normal;">Matanda</span></h2>

            <p><strong>Forma de Pago:</strong> <span id="paymentMethod"><?php echo $metodoPago; ?></span></p>

            <p id="voucherInfo" style="display: none;"><strong>Número de Voucher:</strong> <span id="voucherNumber"></span></p>

            <p><strong>Fecha:</strong> <span id="currentDate">No definido</span></p>
            <p><strong>Reparto:</strong> <span id="repartoEstado">-</span></p>

        </div>

        <h2>Detalles de los Productos</h2>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <table id="saleDetailsTable" class="display" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 10px;">Producto</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Cantidad</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Precio Unitario</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Precio Dcto</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se llenarán dinámicamente -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold;">Sin IVA:</td>
                        <td class="iva" style="border: 1px solid #ccc; padding: 10px;">$0.00</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold;">Total:</td>
                        <td class="total-con-iva" style="border: 1px solid #ccc; padding: 10px;">$0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button id="closeModal" style="padding: 10px 20px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Cerrar</button>
    </div>
</div>



<script>
$(document).ready(function () {
    $('#salesHistoryTable').on('click', '.view-details', function () {
        const saleId = $(this).data('id');
        $('#modalTitle').text(`Orden de Compra #${saleId}`);

        $.get(`get_sale_details.php?idventa=${saleId}`, function (response) {
            if (response && response.length > 0) {

                const saleDate = response[0].fecha || 'No definido';
                const paymentMethod = response[0].forma_pago || 'No definido';
                const voucherNumber = response[0].voucher || '';
                const repartoEstado = response[0].reparto || '-';


                $('#paymentMethod').text(paymentMethod);
                $('#currentDate').text(saleDate);

                if (paymentMethod === 'Debito' || paymentMethod === 'Credito') {
                    $('#voucherInfo').show();
                    $('#voucherNumber').text(voucherNumber ? voucherNumber : 'No registrado');
                } else {
                    $('#voucherInfo').hide();
                }
            }
        }, 'json');
    });

    function updateSalesSummary() {
        console.log("Actualizando resumen de ventas...");

        let totalSales = 0;
        let totalAmount = 0;

        let paymentMethods = {
            "Debito": 0,
            "Credito": 0,
            "Transferencia": 0,
            "Efectivo": 0
        };

        $('#salesHistoryTable tbody tr').each(function () {
            const amount = parseFloat($(this).find('td:eq(1)').text().replace(/\D/g, '')) || 0;
            const paymentMethod = $(this).find('td:eq(3)').text().trim();

            totalSales++;
            totalAmount += amount;

            if (paymentMethods.hasOwnProperty(paymentMethod)) {
                paymentMethods[paymentMethod] += amount;
            }
        });

        $('#salesSummary').html(`
            <div class="summary-box">
                <strong>Total Ventas:</strong> ${totalSales} |
                <strong>Monto Total:</strong> $${totalAmount.toLocaleString('es-CL')}
            </div>
            <div class="summary-details">
                Debito: $${paymentMethods["Debito"].toLocaleString('es-CL')} |
                Credito: $${paymentMethods["Credito"].toLocaleString('es-CL')} |
                Transferencia: $${paymentMethods["Transferencia"].toLocaleString('es-CL')} |
                Efectivo: $${paymentMethods["Efectivo"].toLocaleString('es-CL')}
            </div>
        `);
    }

    $('#salesHistoryTable').on('draw.dt', updateSalesSummary);
});


</script>





<script>
$(document).ready(function () {
    let salesTable;

    if (!$.fn.dataTable.isDataTable('#salesHistoryTable')) {
        salesTable = $('#salesHistoryTable').DataTable({
            ajax: 'get_sales.php',
            columns: [
                { data: 'idventa' },
                {
                    data: 'monto',
                    render: {
                        display: function (data) {
                            const amount = parseFloat(data);
                            return isNaN(amount) ? 'N/A' : `$${Math.round(amount).toLocaleString('es-CL')}`;
                        },
                        sort: function (data) {
                            return parseFloat(data) || 0;
                        }
                    },
                    type: 'num',
                },
                { data: 'fecha' },
                { data: 'metodo_pago' },
                { data: 'estado' },
                { data: 'notas' },
                {
                    data: 'reparto',
                    render: function (data) {
                        if (data === "Sí") {
                            return '<span style="color: green; font-weight: bold;">Sí</span>';
                        } else if (data === "No") {
                            return '<span style="color: red; font-weight: bold;">No</span>';
                        } else {
                            return data || '-';
                        }
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return `
                            <button class="view-details" data-id="${data.idventa}">Ver Detalles</button>
                            <button class="edit-sale-btn" data-id="${data.idventa}" data-product-id="${data.producto_idproducto}">Editar</button>
                            <a href="https://zeusr.sii.cl/AUT2000/InicioAutenticacion/IngresoRutClave.html?https://www1.sii.cl/cgi-bin/Portal001/mipeSelEmpresa.cgi?DESDE_DONDE_URL=OPCION%3D33%26TIPO%3D4" target="_blank">
                                <button class="invoice-btn" data-id="${data.idventa}" style="background-color: blue; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Factura</button>
                            </a>
                        `;
                    }
                }
            ],
            pageLength: 100,
            order: [[0, 'desc']],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    }

    $('#minPrice, #maxPrice').on('keyup change', function () {
        const min = parseFloat($('#minPrice').val()) || 0;
        const max = parseFloat($('#maxPrice').val()) || Infinity;

        $.fn.dataTable.ext.search.push(function (settings, data) {
            const price = parseFloat(data[1]) || 0;
            return price >= min && price <= max;
        });

        salesTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    $('#sortPriceAsc').on('click', function () {
        salesTable.order([[1, 'asc']]).draw();
    });

    $('#sortPriceDesc').on('click', function () {
        salesTable.order([[1, 'desc']]).draw();
    });

    $('#clearPriceFilter').on('click', function () {
        $('#minPrice').val('');
        $('#maxPrice').val('');
        salesTable.order([]).draw();
    });

    $('#startDate, #endDate').on('change', function () {
        const startDate = $('#startDate').val() ? new Date($('#startDate').val()).getTime() : 0;
        const endDate = $('#endDate').val() ? new Date($('#endDate').val()).getTime() : Infinity;

        $.fn.dataTable.ext.search.push(function (settings, data) {
            const date = new Date(data[2]).getTime();
            return date >= startDate && date <= endDate;
        });

        salesTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    $('#clearDateFilter').on('click', function () {
        $('#startDate').val('');
        $('#endDate').val('');
        salesTable.draw();
    });

    $('#salesHistoryTable').on('click', '.view-details', function () {
        const saleId = $(this).data('id');
        $('#modalTitle').text(`Orden de Compra #${saleId}`);

        $.get(`get_sale_details.php?idventa=${saleId}`, function (response) {
            const detailsTable = $('#saleDetailsTable tbody');
            detailsTable.empty();

            if (response && response.length > 0) {
                const saleDate = response[0].fecha || 'No definido';
                const paymentMethod = response[0].forma_pago || 'No definido';
                const repartoEstado = response[0].reparto || '-';

                $('#saleDetailsModal').find('#currentDate').text(saleDate);
                $('#saleDetailsModal').find('#paymentMethod').text(paymentMethod);

                $('#saleDetailsModal').find('#repartoEstado').html(
                    repartoEstado === 'Sí'
                        ? '<span style="color: green; font-weight: bold;">Sí</span>'
                        : '<span style="color: red; font-weight: bold;">No</span>'
                );

                let subtotal = 0;

                response.forEach(item => {
                    const price = parseFloat(item.precio) || 0;
                    const discountAmount = parseFloat(item.descuento) || 0;
                    const quantity = parseInt(item.cantidad) || 1;

                    let priceDcto = '-';
                    let itemSubtotal = price * quantity;

                    if (discountAmount > 0) {
                        const descuentoUnidad = discountAmount / quantity;
                        priceDcto = price - descuentoUnidad;
                        itemSubtotal = priceDcto * quantity;
                        priceDcto = `$${Math.round(priceDcto).toLocaleString('es-CL')}`;
                    }

                    subtotal += itemSubtotal;

                    detailsTable.append(`
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 10px;">${item.nombre}</td>
                            <td style="border: 1px solid #ccc; padding: 10px; text-align: center;">${quantity}</td>
                            <td style="border: 1px solid #ccc; padding: 10px; text-align: right;">$${Math.round(price).toLocaleString('es-CL')}</td>
                            <td style="border: 1px solid #ccc; padding: 10px; text-align: center; color: green; font-weight: bold;">${priceDcto}</td>
                            <td style="border: 1px solid #ccc; padding: 10px; text-align: right;">$${Math.round(itemSubtotal).toLocaleString('es-CL')}</td>
                        </tr>
                    `);
                });

                const sinIVA = Math.round(subtotal / 1.19);
                const totalConIva = Math.round(subtotal);

                $('#saleDetailsModal').find('.iva').css({
                    'color': '#555',
                    'text-align': 'center'
                }).text(`$${sinIVA.toLocaleString('es-CL')}`);

                $('#saleDetailsModal').find('.total-con-iva').css({
                    'font-weight': 'bold',
                    'text-align': 'center'
                }).text(`$${totalConIva.toLocaleString('es-CL')}`);
            } else {
                alert('No se encontraron detalles para esta venta.');
            }

            $('#modalOverlay').fadeIn();
        }, 'json').fail(() => {
            alert('Hubo un error al intentar obtener los detalles de la venta.');
        });
    });

    // Cerrar modal al hacer click fuera
    $('#modalOverlay').on('click', function (e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });

    // Botón cerrar modal
    $('#closeModal').on('click', function () {
        $('#modalOverlay').fadeOut();
    });
});
</script>




<script>
document.getElementById('closeModal').addEventListener('click', function () {
    document.getElementById('modalOverlay').style.display = 'none';
});

function openModal() {
    document.getElementById('modalOverlay').style.display = 'block';
}
</script>






<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1️⃣ Al cargar la página, deja los campos vacíos (sin filtro aplicado)
        document.getElementById("startDate").value = "";
        document.getElementById("endDate").value = "";

        function formatDateTime(date, hour, minute) {
            date.setHours(hour, minute, 0, 0);
            return date.toISOString().slice(0, 16); // Formato YYYY-MM-DDTHH:MM
        }

        // 2️⃣ Al seleccionar fecha, se definen las horas automáticamente
        document.getElementById("startDate").addEventListener("change", function() {
            if (this.value) {
                let selectedDate = new Date(this.value);
                this.value = formatDateTime(selectedDate, -3, 0); // 00:00
            }
        });

        document.getElementById("endDate").addEventListener("change", function() {
            if (this.value) {
                let selectedDate = new Date(this.value);
                this.value = formatDateTime(selectedDate, 20, 59); // 23:59
            }
        });

        // 3️⃣ Botón para limpiar filtros y mostrar todo
        document.getElementById("clearDateFilter").addEventListener("click", function() {
            document.getElementById("startDate").value = "";
            document.getElementById("endDate").value = "";
        });
    });
</script>





<script>
$(document).ready(function () {
    // Delegación de eventos para botones generados dinámicamente
    $(document).on('click', '.edit-sale-btn', function () {
        const saleId = $(this).data('id'); // ID de la venta desde el botón
        console.log('Botón clickeado, saleId:', saleId);

        if (!saleId) {
            alert('Error: ID de venta no encontrado');
            return;
        }

        // Guardamos el ID en el formulario para reutilizarlo
        $('#editSaleForm').data('id', saleId);

        // Obtener los detalles de la venta desde PHP
        $.get(`get_sale_edit_details.php?idventa=${saleId}`, function (response) {
            console.log('Respuesta recibida:', response);

            if (response.error) {
                alert(response.error);
                return;
            }

            // Vaciar el select antes de llenarlo
            $('#editSaleStatus').empty();

            // Opciones de estado
            const estados = ['pendiente', 'completado', 'cancelado'];

            // Llenar el select con los estados
            estados.forEach(function (estado) {
                let selected = (estado.toLowerCase() === response.estado.toLowerCase()) ? 'selected' : '';
                $('#editSaleStatus').append(`<option value="${estado}" ${selected}>${estado.charAt(0).toUpperCase() + estado.slice(1)}</option>`);
            });

            // Mostrar las notas
            $('#editNotes').val(response.notas);

            // Abrir modal
            $('#editSaleModal').fadeIn();
            $('#editSaleOverlay').fadeIn();

        }, 'json').fail(function () {
            alert('Error al obtener los detalles de la venta.');
        });
    });

    // Cerrar modal al hacer click fuera
    $('#editSaleOverlay').on('click', function (e) {
        if (e.target === this) {
            $('#editSaleModal').fadeOut();
            $(this).fadeOut();
        }
    });

    // Botón de cerrar y cancelar
    $('.modal-close, #cancelEdit').on('click', function () {
        $('#editSaleModal').fadeOut();
        $('#editSaleOverlay').fadeOut();
    });
});

$(document).on('click', '#saveEdit', function () {
    const saleId = $('#editSaleForm').data('id'); // ID de venta desde el formulario
    const estado = $('#editSaleStatus').val(); // Estado
    const notas = $('#editNotes').val(); // Notas

    if (!saleId) {
        alert('Error: ID de venta no encontrado.');
        return;
    }

    $.ajax({
        url: 'update_sale.php',
        type: 'POST',
        data: {
            idventa: saleId,
            estado: estado,
            notas: notas
        },
        dataType: 'json',
        success: function (response) {
            console.log(response);
            if (response.success) {
                alert(response.message);
                $('#editSaleModal').fadeOut();
                $('#editSaleOverlay').fadeOut();
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function () {
            alert('Error de conexión al servidor.');
        }
    });
});

$(document).on('click', '#deleteSale', function () {
    const saleId = $('#editSaleForm').data('id');

    if (!saleId) {
        alert('Error: ID de venta no encontrado');
        return;
    }

    if (confirm('¿Estás seguro que quieres eliminar esta venta? 🤔')) {
        $.ajax({
            url: 'delete_sale.php',
            type: 'POST',
            data: {
                idventa: saleId
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    $('#editSaleModal').fadeOut();
                    $('#editSaleOverlay').fadeOut();
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function () {
                alert('Error al conectar con el servidor');
            }
        });
    }
});
</script>









</body>
</html>