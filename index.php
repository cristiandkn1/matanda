<?php
// 1️⃣ Mostrar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2️⃣ Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3️⃣ Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 4️⃣ Verificar si el usuario tiene rol "usuario"
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: login.php"); // o acceso_denegado.php
    exit();
}
// 4️⃣ Conectar con la base de datos (solo una vez)
require_once 'db.php';
$database = new Database();
$conn = $database->conn;

// 5️⃣ Actualizar descuentos caducados
$query = "UPDATE descuento SET porcentaje = 0 WHERE fecha < NOW()";
if (!$conn->query($query)) {
    echo "Error al actualizar descuentos: " . $conn->error;
}

// 6️⃣ Obtener productos visibles únicamente
$queryProductos = "
    SELECT 
        p.idproducto,
        p.nombre,
        p.precio,
        p.codigo,
        p.fecha,
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

$result = $conn->query($queryProductos);

$productos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
    echo "Error al obtener productos: " . $conn->error;
}

// 7️⃣ Reiniciar "vendidos_mes" (ejecutar solo una vez por mes)
$query = "SELECT ultima_fecha FROM reinicio_mes ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$ultimaFecha = $result->fetch_assoc()['ultima_fecha'] ?? null;

$mesActual = date('Y-m');
$mesUltimaFecha = date('Y-m', strtotime($ultimaFecha));

if ($mesActual !== $mesUltimaFecha) {
    // Reiniciar la columna "vendidos_mes"
    $conn->query("UPDATE producto SET vendidos_mes = 0");
    
    // Registrar la nueva fecha en la tabla "reinicio_mes"
    $conn->query("INSERT INTO reinicio_mes (ultima_fecha) VALUES (CURDATE())");
}
date_default_timezone_set('America/Santiago');

// 8️⃣ Cerrar conexión a la base de datos
$database->close();
?>

















<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Inventario</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- jQuery (solo una vez) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Scripts de DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Incluye Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<!-- Incluye Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<meta name="viewport" content="width=800, user-scalable=yes">

<body>

<header style="display: flex; justify-content: center; align-items: center; padding: 10px;  color: white; position: relative;">
    <meta name="viewport" content="width=800, user-scalable=yes">

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
    <!-- Botones para Añadir -->
    <div class="button-group">
        <button id="openModalButton">Añadir Producto</button>
        <button id="openBrandModalButton">Añadir Proveedor</button>
        <button id="openDiscountModalButton">Añadir Descuento</button>
    </div>

    <!-- Botones para Editar (Debajo y más pequeños) -->
    <div class="button-group edit-buttons">
        <button id="editBrandModalButton">Editar Proveedor</button>
        <button id="editDiscountModalButton">Editar Descuento</button>
    </div>




    <div id="tableContainer">
    <h2>Lista de Productos</h2>
    <table id="productTable" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descuento</th>                   
                <th>Precio Unitario</th>
                <th>Precio Total</th>
                <th>Proveedor</th>    
                <th>Código</th>
                <th>Cantidad</th>
                <th>Fecha Vencimiento</th>

                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Aquí se cargarán los datos dinámicamente -->
        </tbody>
    </table>
</div>


</main>

<!-- Modal para Agregar Producto -->
<div id="modalAddProduct" class="modalproductoadd" style="display: none;">
    <div class="modal-content">
        <span class="close close-modal">&times;</span>
        <h2>Añadir Producto</h2>
        <form id="addProductForm">
            <label for="productName">Nombre:</label>
            <input type="text" id="productName" name="nombre" required>

            <label for="productPrice">Precio:</label>
            <input type="number" id="productPrice" name="precio" required>

            <label for="productCode">Código:</label>
            <input type="text" id="productCode" name="codigo" required>

            <label for="productQuantity">Cantidad:</label>
            <input type="number" id="productQuantity" name="cantidad" required>

            <label for="productDiscount">Descuento:</label>
            <select id="productDiscount" name="descuento_iddescuento">
                <option value="">Seleccione un descuento</option>
            </select>

            <label for="productBrand">Proveedor:</label>
            <select id="productBrand" name="marca_idmarca">
                <option value="">Seleccione un Proveedor</option>
            </select>

            <!-- ✅ Nuevo campo: Fecha de Vencimiento -->
            <label for="productExpiry">Fecha de Vencimiento:</label>
            <input type="date" id="productExpiry" name="fecha_vencimiento">

            <button type="submit">Guardar Producto</button>
        </form>
    </div>
</div>

 







<!-- Modal para Editar Producto -->
<div id="editProductModal" class="modalproductoadd" style="display: none;">
    <div class="modal-content">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Editar Producto</h2>
        <form id="editProductForm">
            <input type="hidden" id="editProductId" name="productId">

            <label for="editProductName">Nombre:</label>
            <input type="text" id="editProductName" name="productName" required>

            <label for="editProductPrice">Precio:</label>
            <input type="number" id="editProductPrice" name="productPrice" required>

            <label for="editProductCode">Código:</label>
            <input type="text" id="editProductCode" name="productCode" required>

            <label for="editProductQuantity">Cantidad:</label>
            <input type="number" id="editProductQuantity" name="productQuantity" required>

            <!-- Selector de Marca -->
            <label for="editProductBrand">Proveedor:</label>
            <select id="editProductBrand" name="productBrand" required>
                <option value="">Seleccione una Proveedor</option>
            </select>

            <!-- Selector de Descuento -->
            <label for="editProductDiscount">Descuento:</label>
            <select id="editProductDiscount" name="productDiscount">
                <option value="">Sin descuento</option>
            </select>

            <!-- ✅ Nuevo campo: Fecha de Vencimiento -->
            <label for="editProductExpiry">Fecha de Vencimiento:</label>
            <input type="date" id="editProductExpiry" name="fecha_vencimiento">

            <button type="submit">Guardar Cambios</button>
        </form>
    </div>
</div>




<!-- Modal para Agregar Categoría -->
<div id="categoryModal" class="modal-category">
    <div class="modal-content-category">
    <span class="close" id="closeModal">&times;</span>
    <h2>Agregar Categoría</h2>
        <form id="categoryForm">
            <label for="categoryName">Nombre:</label>
            <input type="text" id="categoryName" name="nombre" required>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para Agregar Marca -->
<div id="brandModal" class="modal-brand">
    <div class="modal-content-brand">
    <span class="close" id="closeModal">&times;</span>
    <h2>Agregar Proveedor</h2>
        <form id="brandForm">
            <label for="brandName">Nombre:</label>
            <input type="text" id="brandName" name="nombre" required>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para Agregar Descuento -->
<div id="discountModal" class="modal-discount">
    <div class="modal-content-discount">
    <span class="close" id="closeModal">&times;</span>
    <h2>Agregar Descuento</h2>
        <form id="discountForm">
            <label for="discountName">Nombre:</label>
            <input type="text" id="discountName" name="nombre" required>
            <label for="discountPercentage">Porcentaje:</label>
            <input type="number" id="discountPercentage" name="porcentaje" required>
            <label for="discountDate">Fecha:</label>
            <input type="datetime-local" id="discountDate" name="fecha" required>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>




<!-- Modal para Editar Categoría -->
<div id="editCategoryModal" class="modal-edit-category">
    <div class="modal-content-edit-category">

        <h2>Editar Categoría</h2>
        <form id="editCategoryForm">
            <label for="categorySelect">Seleccionar Categoría:</label>
            <select id="categorySelect" name="idcategoria" class="select-with-search" required></select>

            <label for="newCategoryName">Nuevo Nombre:</label>
            <input type="text" id="newCategoryName" name="nuevo_nombre" placeholder="Nuevo nombre de categoría" required>

            <button type="submit" class="btn-save">Guardar Cambios</button>
        </form>
    </div>
</div>

<!-- Modal para Editar Marca -->
<div id="editBrandModal" class="modal-edit-brand">
    <div class="modal-content-edit-brand">
        <h2>Editar Proveedor</h2>
        <form id="editBrandForm">
            <label for="brandSelect">Seleccionar Proveedor:</label>
            <select id="brandSelect" name="idmarca" class="select-with-search" required></select>

            <label for="newBrandName">Nuevo Nombre:</label>
            <input type="text" id="newBrandName" name="nuevo_nombre" placeholder="Nuevo nombre de Proveedor" required>

            <!-- Contenedor de botones para centrar -->
            <div class="button-container">
                <button type="submit" class="btn-save">Guardar Cambios</button>
                <button type="button" class="btn-danger" onclick="deleteBrand()">Eliminar Marca</button>
            </div>
        </form>
    </div>
</div>



<!-- Modal para Editar Descuento -->
<div id="editDiscountModal" class="modal-edit-discount">
    <div class="modal-content-edit-discount">

        <h2>Editar Descuento</h2>
        <form id="editDiscountForm">
        <select id="discountSelect" name="iddescuento" class="select-with-search" required>
            <option value="" disabled selected>Seleccione Descuento</option>
            </select>


            <label for="newDiscountName">Nuevo Nombre:</label>
            <input type="text" id="newDiscountName" name="nuevo_nombre" placeholder="Nuevo nombre de descuento" required>

            <label for="newDiscountPercentage">Nuevo Porcentaje:</label>
            <input type="number" id="newDiscountPercentage" name="nuevo_porcentaje" placeholder="Nuevo porcentaje" required>

            <label for="newDiscountDate">Nueva Fecha:</label>
            <input type="datetime-local" id="newDiscountDate" name="nueva_fecha" required>

            <!-- Contenedor para centrar los botones con separación -->
            <div class="button-container">
                <button type="submit" class="btn-save">Guardar Cambios</button>
                <button type="button" class="btn-danger" onclick="deleteDiscount()">Eliminar Descuento</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal para Eliminar Categoría -->
<div id="deleteCategoryModal" class="modal-delete">
    <div class="modal-content-delete">
        <h2>Eliminar Categoría</h2>
        <p>Selecciona la categoría que deseas eliminar:</p>
        <select id="deleteCategorySelect"></select>
        <button id="confirmDeleteCategory" class="btn-delete">Eliminar</button>
    </div>
</div>






<script>
// eliminar marca
function deleteBrand() {
    let brandId = document.getElementById('brandSelect').value;

    if (!brandId) {
        alert("Seleccione una marca para eliminar.");
        return;
    }

    if (confirm("¿Estás seguro de que deseas eliminar esta marca?")) {
        fetch('delete_brand.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: brandId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Marca eliminada correctamente.");
                // Recargar la página o volver a cargar las marcas en el select
                location.reload();
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}





// Cargar Descuentos al cargar la página
document.addEventListener("DOMContentLoaded", function () {
    fetch('load_discounts.php')
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById('discountSelect');
            select.innerHTML = '<option value="" disabled selected>Seleccione Descuento</option>';
            data.forEach(descuento => {
                let option = document.createElement('option');
                option.value = descuento.id;
                option.textContent = descuento.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => console.error("Error cargando descuentos:", error));
});

// Cargar datos al seleccionar un descuento
document.getElementById('discountSelect').addEventListener('change', function () {
    let id = this.value;

    if (id) {
        fetch(`get_discount.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newDiscountName').value = data.discount.nombre;
                    document.getElementById('newDiscountPercentage').value = data.discount.porcentaje;
                    document.getElementById('newDiscountDate').value = data.discount.fecha;
                } else {
                    alert("No se pudo cargar el descuento.");
                }
            })
            .catch(error => console.error("Error al cargar el descuento:", error));
    }
});
// eliminar descuento #
function deleteDiscount() {
    let discountId = document.getElementById('discountSelect').value;

    if (!discountId) {
        alert("Seleccione un descuento para eliminar.");
        return;
    }

    if (confirm("¿Estás seguro de que deseas eliminar este descuento?")) {
        fetch('delete_discount.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: discountId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Descuento eliminado correctamente.");
                location.reload();
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
document.getElementById('discountSelect').addEventListener('change', function () {
    if (this.value == "3") {
        document.getElementById('newDiscountName').disabled = true;
        document.getElementById('newDiscountPercentage').disabled = true;
        document.getElementById('newDiscountDate').disabled = true;
    } else {
        document.getElementById('newDiscountName').disabled = false;
        document.getElementById('newDiscountPercentage').disabled = false;
        document.getElementById('newDiscountDate').disabled = false;
    }
});
</script>



<script>document.addEventListener("DOMContentLoaded", function () {
    // Función para cerrar modal al hacer clic fuera del contenido
    function closeModalOnClickOutside(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // Aplicar la función a cada modal
    closeModalOnClickOutside("deleteCategoryModal");
    closeModalOnClickOutside("deleteBrandModal");
});
</script>


<script>
    // Función para abrir y cerrar modales
function setupModal(modalId, openButtonId, closeClass) {
    const modal = document.getElementById(modalId);
    const openButton = document.getElementById(openButtonId);
    const closeButton = modal.querySelector(`.${closeClass}`);

    openButton.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    closeButton.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Configurar los modales
setupModal('categoryModal', 'openCategoryModalButton', 'close-category-modal');
setupModal('brandModal', 'openBrandModalButton', 'close-brand-modal');
setupModal('discountModal', 'openDiscountModalButton', 'close-discount-modal');





</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Abrir y cerrar modal de categoría
    const categoryModal = document.getElementById('categoryModal');
    const openCategoryModalButton = document.getElementById('openCategoryModalButton');
    const closeCategoryModalButton = document.querySelector('.close-category-modal');
    const addCategoryForm = document.getElementById('categoryForm');

    if (openCategoryModalButton) {
        openCategoryModalButton.addEventListener('click', () => {
            categoryModal.style.display = 'block';
        });
    }

    if (closeCategoryModalButton) {
        closeCategoryModalButton.addEventListener('click', () => {
            categoryModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === categoryModal) {
            categoryModal.style.display = 'none';
        }
    });

    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(addCategoryForm);

            try {
                const response = await fetch('save_category.php', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('Error al guardar la categoría.');
                }

                const result = await response.json();

                if (result.success) {
                    alert('Categoría guardada con éxito.');
                    addCategoryForm.reset();
                    categoryModal.style.display = 'none';
                } else {
                    alert(result.error || 'Error desconocido.');
                }
            } catch (error) {
                alert(error.message);
            }
        });
    }

    // Abrir y cerrar modal de marca
    const brandModal = document.getElementById('brandModal');
    const openBrandModalButton = document.getElementById('openBrandModalButton');
    const closeBrandModalButton = document.querySelector('.close-brand-modal');
    const addBrandForm = document.getElementById('brandForm');

    if (openBrandModalButton) {
        openBrandModalButton.addEventListener('click', () => {
            brandModal.style.display = 'block';
        });
    }

    if (closeBrandModalButton) {
        closeBrandModalButton.addEventListener('click', () => {
            brandModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === brandModal) {
            brandModal.style.display = 'none';
        }
    });

    if (addBrandForm) {
        addBrandForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(addBrandForm);

            try {
                const response = await fetch('save_brand.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    alert('Marca guardada con éxito.');
                    brandModal.style.display = 'none';
                    addBrandForm.reset();
                } else {
                    alert(result.error || 'Error desconocido.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al intentar guardar la marca.');
            }
        });
    }

    // Abrir y cerrar modal de descuento
    const discountModal = document.getElementById('discountModal');
    const openDiscountModalButton = document.getElementById('openDiscountModalButton');
    const closeDiscountModalButton = document.querySelector('.close-discount-modal');
    const addDiscountForm = document.getElementById('discountForm');

    if (openDiscountModalButton) {
        openDiscountModalButton.addEventListener('click', () => {
            discountModal.style.display = 'block';
        });
    }

    if (closeDiscountModalButton) {
        closeDiscountModalButton.addEventListener('click', () => {
            discountModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === discountModal) {
            discountModal.style.display = 'none';
        }
    });

    if (addDiscountForm) {
        addDiscountForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(addDiscountForm);

            try {
                const response = await fetch('save_discount.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    alert('Descuento guardado con éxito.');
                    discountModal.style.display = 'none';
                    addDiscountForm.reset();
                } else {
                    alert(result.error || 'Error desconocido.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al intentar guardar el descuento.');
            }
        });
    }
});

    
</script>

<script>
    let table; // Definir table en un ámbito global
$(document).ready(function () {
    let table = $('#productTable').DataTable({
        ajax: {
            url: 'load_products.php',
            type: 'GET',
            dataType: 'json',
            error: function (xhr, status, error) {
                console.error('Error al cargar los datos:', error);
            }
        },
        columns: [
    { data: 'idproducto' },
    { data: 'nombre' },
    { 
    data: 'descuento', 
    render: function(data, type, row) {
        if (row.nombre_descuento) {
            let nombreDescuento = row.nombre_descuento.trim().toLowerCase();

            // Si el descuento es "sin descuento", mostrar en gris
            if (nombreDescuento === "sin descuento") {
                return `<span style="color: gray;">Sin descuento</span>`;
            } 
            
            // Si el descuento es 0% pero tiene un nombre, mostrar en rojo
            if (row.descuento == 0) {
                return `<span style="color: red; font-weight: bold;">
                            ${row.nombre_descuento} - 0%
                        </span>`;
            }

            // Si el descuento es mayor a 0%, mostrar en verde
            return `<span style="color: green; font-weight: bold;">
                        ${row.nombre_descuento} - ${row.descuento}%
                    </span>`;
        }

        // En caso de que no haya nombre de descuento, se asume que no tiene descuento
        return `<span style="color: gray;">Sin descuento</span>`;
    }
},




{  
    data: 'precio', // Precio Unitario
    render: {
        display: function(data, type, row) {
            const precio = parseFloat(data);
            const descuento = row.descuento || 0;

            // Formateo del precio normal
            let precioNormal = precio.toLocaleString('es-CL', {
                style: 'currency',
                currency: 'CLP',
                minimumFractionDigits: 0
            });

            // Si el descuento es mayor a 0%, calcular y mostrar el precio con descuento
            if (descuento > 0) {
                const precioConDescuento = precio * (1 - descuento / 100);
                let precioDescuento = precioConDescuento.toLocaleString('es-CL', {
                    style: 'currency',
                    currency: 'CLP',
                    minimumFractionDigits: 0
                });

                return `
                    <div>
                        ${precioNormal} 
                        <br>
                        <small style="color: green;">
                            (${precioDescuento} con descuento)
                        </small>
                    </div>
                `;
            }

            // Si el descuento es 0% o null, solo muestra el precio normal
            return precioNormal;
        },
        sort: function(data) {
            return parseFloat(data);
        }
    }
},


{ 
    data: 'precio',
    render: {
        display: function(data, type, row) {
            const descuento = row.descuento || 0;
            const precioOriginal = parseFloat(data);
            const precioConDescuento = precioOriginal * (1 - descuento / 100);
            const precioTotal = Math.floor(precioConDescuento * row.cantidad);

            let formattedPrice = precioTotal.toLocaleString('es-CL', {
                style: 'currency',
                currency: 'CLP',
                minimumFractionDigits: 0
            });

            // Si el descuento es mayor a 0%, mostrar en verde
            if (descuento > 0) {
                return `<span style="color: green; font-weight: bold;">${formattedPrice}</span>`;
            }
            
            // Si el descuento es 0%, mostrar en rojo
            if (descuento === 0 && row.nombre_descuento && row.nombre_descuento.toLowerCase() !== "sin descuento") {
                const precioTotalOriginal = Math.floor(precioOriginal * row.cantidad).toLocaleString('es-CL', {
                    style: 'currency',
                    currency: 'CLP',
                    minimumFractionDigits: 0
                });

                return `<span style="color: red; font-weight: bold;">${precioTotalOriginal}</span>`;
            }

            // Si es "Sin descuento", mostrar normal
            return formattedPrice;
        },
        sort: function(data, type, row) {
            const descuento = row.descuento || 0;
            return parseFloat(data) * (1 - descuento / 100) * row.cantidad;
        }
    }
},

    { data: 'marca' }, // Marca ahora después de los precios
    { data: 'codigo' },
    { data: 'cantidad' },
    
    { // ✅ NUEVO: Fecha de Vencimiento
        data: 'fecha_vencimiento',
        render: function(data, type, row) {
            if (!data) return '<span style="color: gray;">—</span>';
            const hoy = new Date();
            const vencimiento = new Date(data);
            const diasRestantes = (vencimiento - hoy) / (1000 * 60 * 60 * 24);
            if (diasRestantes < 0) {
                return `<span style="color: red;">Vencido: ${data}</span>`;
            } else if (diasRestantes <= 3) {
                return `<span style="color: orange;">Por vencer: ${data}</span>`;
            } else {
                return `<span>${data}</span>`;
            }
        }
    },

    { 
        data: null,
        render: function(data, type, row) {
            return `
                <button class="btn-increase" data-id="${row.idproducto}">+</button>
                <button class="btn-decrease" data-id="${row.idproducto}">−</button>
                <button class="btn-edit" data-id="${row.idproducto}">Editar</button>
                <button class="btn-delete" data-id="${row.idproducto}">Eliminar</button>
            `;
        }
    }
],

        responsive: true,
        paging: true,
        searching: true,
        pageLength: 100, 
        order: [[0, 'desc']],

        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },

        initComplete: function() {
            $('#productTable_filter input').on('focus', function() {
                $(this).select();
            });
        },

        createdRow: function(row, data, dataIndex) {
            if (data.cantidad < 4) {
                $(row).css('background-color', '#f8d7da'); // Color rojo claro
            }
        }
    });
});



   // Evento para el botón "Editar"
$('#productTable tbody').on('click', '.btn-edit', function () {
    const productId = $(this).data('id');
    if (!productId) {
        console.error('El ID del producto no está definido');
        return;
    }

    $.get('get_product.php', { id: productId }, function (data) {
        if (data.error) {
            alert(data.error);
        } else {
            $('#editProductId').val(data.idproducto);
            $('#editProductName').val(data.nombre);
            $('#editProductPrice').val(data.precio);
            $('#editProductCode').val(data.codigo);
            $('#editProductDate').val(data.fecha);
            $('#editProductDescription').val(data.descripcion);
            $('#editProductQuantity').val(data.cantidad);
            $('#editProductBrand').html(data.brandOptions || '');
            $('#editProductCategory').html(data.categoryOptions || '');
            $('#editProductDiscount').html(data.discountOptions || '');

            // ✅ Nuevo: Asignar fecha de vencimiento
            $('#editProductExpiry').val(data.fecha_vencimiento);

            $('#editProductModal').fadeIn();
        }
    }, 'json').fail(function (xhr) {
        alert('Error al obtener el producto: ' + xhr.responseText);
    });
});


    // Cerrar el modal de edición
    $('#closeEditModal').on('click', function () {
        $('#editProductModal').fadeOut();
    });
// Cerrar el modal de edición al hacer clic fuera del modal
$(document).on('click', function (event) {
    if ($(event.target).is('#editProductModal')) {
        $('#editProductModal').fadeOut();
    }
});
    // Envío del formulario de edición
    $('#editProductForm').on('submit', function (e) {
        e.preventDefault();
        $.post('update_product.php', $(this).serialize(), function (response) {
            $('#editProductModal').fadeOut();
            $('#productTable').DataTable().ajax.reload(null, false);
        }, 'json').fail(function (xhr) {
            alert('Error al actualizar el producto: ' + xhr.responseText);
        });
    });

    // Evento para el botón "Eliminar" (ahora ocultar)
$('#productTable tbody').on('click', '.btn-delete', function () {
    const productId = $(this).data('id');
    if (!productId) {
        console.error('El ID del producto no está definido');
        return;
    }

    if (confirm('¿Estás seguro de que deseas ocultar este producto?')) {
        $.ajax({
            url: 'hide_product.php', // Archivo que manejará el ocultado
            type: 'POST',
            data: { id: productId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Producto Eliminado correctamente.');
                    $('#productTable').DataTable().ajax.reload(null, false); // Recargar tabla sin reiniciar la paginación
                } else {
                    alert('Error: ' + (response.message || 'Error desconocido.'));
                }
            },
            error: function (xhr) {
                alert('Error al intentar ocultar el producto: ' + xhr.responseText);
            }
        });
    }
});

function reproducirSonido() {
    const sonido = new Audio('pop.mp3');
    sonido.volume = 0.2;
    sonido.play();
}

function recargarTablaSinPerderScroll() {
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    $('#productTable').DataTable().ajax.reload(function () {
        // Restaurar posición del scroll
        window.scrollTo({ top: scrollY, behavior: 'instant' });
    }, false);
}

// Aumentar cantidad
$('#productTable tbody').on('click', '.btn-increase', function () {
    const productId = $(this).data('id');
    $.post('ajustar_cantidad.php', { id: productId, tipo: 'incrementar' }, function (response) {
        if (response.success) {
            recargarTablaSinPerderScroll();
            Swal.fire({
                toast: true,
                position: 'bottom',
                icon: 'success',
                title: 'Se ha aumentado 1 cantidad',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
            });
            reproducirSonido();
        } else {
            Swal.fire('Error', response.message || 'No se pudo aumentar la cantidad', 'error');
        }
    }, 'json');
});

// Disminuir cantidad
$('#productTable tbody').on('click', '.btn-decrease', function () {
    const productId = $(this).data('id');
    $.post('ajustar_cantidad.php', { id: productId, tipo: 'disminuir' }, function (response) {
        if (response.success) {
            recargarTablaSinPerderScroll();
            Swal.fire({
                toast: true,
                position: 'bottom',
                icon: 'info',
                title: 'Se ha reducido 1 cantidad',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
            });
            reproducirSonido();
        } else {
            Swal.fire('Error', response.message || 'No se pudo reducir la cantidad', 'error');
        }
    }, 'json');
});
</script>

<script>
    // Función para eliminar el producto
function deleteProduct(productId) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        $.ajax({
            url: 'delete_product.php',
            type: 'POST',
            data: { idproducto: productId },
            success: function (response) {
                if (response.success) {
                    alert('Producto eliminado correctamente.');
                    $('#productTable').DataTable().ajax.reload(null, false); // Recargar la tabla sin reiniciar la paginación
                } else {
                    alert(response.error || 'Ocurrió un error al eliminar el producto.');
                }
            },
            error: function (xhr, status, error) {
                alert('Error al procesar la solicitud: ' + xhr.responseText);
            }
        });
    }
}

</script>

<script>
   $(document).ready(function () {
    // Función para abrir el modal CREAR PRODUCTO
    $('#openModalButton').on('click', function () {
        $("#productBrand").show(); // Asegurar que el select sea visible

        $('#modalAddProduct').fadeIn();
        loadBrands(); // Cargar marcas al abrir el modal
        loadCategories(); // Cargar categorías al abrir el modal
        loadDiscounts(); // Cargar descuentos al abrir el modal
    });

    // Función para cerrar el modal
    $('.close-modal').on('click', function () {
        $('#modalAddProduct').fadeOut();
    });

    function loadBrands() {
    console.log("Ejecutando loadBrands...");
    $.ajax({
        url: 'load_brands.php',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            console.log("Datos recibidos:", data); // Ver los datos en consola
            let brandSelect = $('#productBrand');
            brandSelect.empty();
            brandSelect.append('<option value="">Seleccione una marca</option>');
            data.forEach(function (brand) {
                brandSelect.append('<option value="' + brand.id + '">' + brand.nombre + '</option>');
            });
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar las marcas:', error);
        }
    });
}


    // Cargar categorías dinámicamente
    function loadCategories() {
        $.ajax({
            url: 'load_categories.php',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                let categorySelect = $('#productCategory');
                categorySelect.empty();
                categorySelect.append('<option value="">Seleccione una categoría</option>');
                data.forEach(function (category) {
                    categorySelect.append('<option value="' + category.id + '">' + category.nombre + '</option>');
                });
            },
            error: function (xhr, status, error) {
                console.error('Error al cargar las categorías:', error);
            }
        });
    }

    function loadDiscounts() {
        fetch("load_discounts.php")
            .then((response) => response.json())
            .then((data) => {
                const select = document.getElementById("productDiscount");
                if (select) {
                    select.innerHTML = '<option value="">Seleccione un descuento</option>';
                    data.forEach((item) => {
                        select.insertAdjacentHTML(
                            "beforeend",
                            `<option value="${item.id}" 
                                data-nombre="${item.nombre}" 
                                data-porcentaje="${item.porcentaje}" 
                                data-fecha="${item.fecha}">
                                ${item.nombre} - ${item.porcentaje}%
                            </option>`
                        );
                    });
                }
            })
            .catch((error) => {
                console.error(`Error al cargar los descuentos: ${error}`);
            });
    }

    $("#productDiscount").on("change", function () {
        const selectedOption = this.options[this.selectedIndex];

        if (selectedOption.value) {
            $("#newDiscountName").val(selectedOption.dataset.nombre);
            $("#newDiscountPercentage").val(selectedOption.dataset.porcentaje);
        } else {
            $("#newDiscountName").val("");
            $("#newDiscountPercentage").val("");
        }
    });

    $("#editDiscountModalButton").on("click", function () {
        loadDiscounts();
        $("#editDiscountModal").fadeIn();
    });

    $(".close-modal").on("click", function () {
        $("#editDiscountModal").fadeOut();
    });

    // Manejo del envío del formulario
    $('#addProductForm').on('submit', function (event) {
        event.preventDefault(); // Evita la recarga de la página

        // Verificar si la marca o categoría están vacías y asignar "Ninguna"
        let marca = $("#productBrand").val();
        let categoria = $("#productCategory").val();
        let fecha = $("#productDate").val();

        if (!marca && $("#productBrand option[value='0']").length === 0) {
            $("#productBrand").append('<option value="0" selected>Ninguna</option>');
        }
        if (!categoria && $("#productCategory option[value='0']").length === 0) {
            $("#productCategory").append('<option value="0" selected>Ninguna</option>');
        }
        if (!fecha) $("#productDate").val(""); // Dejar el campo vacío

        // Obtener datos del formulario
        let formData = $(this).serialize();

        // Enviar datos al servidor mediante AJAX
        $.ajax({
            url: 'save_product.php', // Archivo PHP que procesará los datos
            type: 'POST',
            data: formData,
            success: function (response) {
                alert('Producto guardado exitosamente.');
                $('#modalAddProduct').fadeOut(); // Cerrar el modal
                $('#addProductForm')[0].reset(); // Reiniciar formulario
                // Recargar correctamente la tabla DataTable sin reiniciar la paginación
                $('#productTable').DataTable().ajax.reload(null, false); // Mantener paginación
            },
            error: function (xhr, status, error) {
                console.error('Error al guardar el producto:', error);
                alert('Ocurrió un error al guardar el producto. Intente nuevamente.');
            }
        });
    });
});

    // Cerrar el modal al hacer clic fuera de él
    $(document).on('click', function (event) {
        if ($(event.target).is('#modalAddProduct')) {
            $('#modalAddProduct').fadeOut();
        }
    });

</script>


<script>
   $(document).ready(function () {
    // -----------------------------------------------------------------EDITAR PRODUCTO--------------------------------------------------------------------
    // Función para abrir el modal de edición
    function openEditModal(productId) {
    $.get('get_product.php', { id: productId }, function (data) {
        if (data.error) {
            alert(data.error);
        } else {
            // Rellenar el modal con los datos del producto
            $('#editProductId').val(data.idproducto);
            $('#editProductName').val(data.nombre);
            $('#editProductPrice').val(data.precio);
            $('#editProductCode').val(data.codigo);
            $('#editProductDate').val(data.fecha);
            $('#editProductDescription').val(data.descripcion);
            $('#editProductQuantity').val(data.cantidad);

            // ✅ Nuevo campo: fecha de vencimiento
            $('#editProductExpiry').val(data.fecha_vencimiento);

            // Rellenar opciones dinámicas para los select
            $('#editProductBrand').html(data.brandOptions || '');
            $('#editProductCategory').html(data.categoryOptions || '');
            $('#editProductDiscount').html(data.discountOptions || '');

            // Mostrar el modal
            $('#editProductModal').fadeIn();
        }
    }, 'json').fail(function (xhr) {
        // Manejo de errores
        alert('Error al obtener el producto: ' + xhr.responseText);
    });
}


    // Asignar evento click a un botón para abrir el modal (ajusta según tu botón en la tabla)
    $(document).on('click', '.edit-product-btn', function () {
        const productId = $(this).data('id'); // Suponiendo que el botón tiene un atributo data-id
        openEditModal(productId);
    });

    // Envío del formulario de edición
    $('#editProductForm').on('submit', function (e) {
        e.preventDefault();

        // Realizar la solicitud para actualizar el producto
        $.post('update_product.php', $(this).serialize(), function (response) {
            alert('Producto actualizado con éxito');
            
            // Cerrar el modal
            $('#editProductModal').fadeOut();

            // Recargar la tabla de productos
            $('#productTable').DataTable().ajax.reload(null, false); // Evita recargar toda la página
        }).fail(function (xhr) {
            // Manejo de errores
            alert('Error al actualizar el producto: ' + xhr.responseText);
        });
    });

    // Cerrar el modal de edición al hacer clic en el botón de cerrar
    $('#closeEditModal').on('click', function () {
        $('#editProductModal').fadeOut();
    });
});


</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Función para abrir un modal y cargar opciones dinámicamente si es necesario
    function openModal(modalId, loadUrl = null, selectId = null, formatFunction = null) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "block";
            if (loadUrl && selectId) {
                loadOptions(loadUrl, selectId, formatFunction);
            }
        }
    }

    // Función para cerrar un modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "none";
        }
    }

    // Cerrar modal al hacer clic fuera del contenido
  // Asegurar cierre al hacer clic fuera del modal
  document.querySelectorAll(".modal-edit-brand, .modal-edit-discount").forEach(modal => {
        modal.addEventListener("click", function (event) {
            const contenido = modal.querySelector(".modal-content-edit-brand, .modal-content-edit-discount");
            if (!contenido.contains(event.target)) {
                closeModal(modal.id);
            }
        });
    });

    // Cerrar modales al hacer clic en la "X"
    document.querySelectorAll(".close-modal").forEach(button => {
        button.addEventListener("click", function () {
            closeModal(button.closest(".modal").id);
        });
    });

    // Función para cargar opciones dinámicamente en un select
    function loadOptions(url, selectId, formatFunction = null) {
    console.log(`Intentando cargar opciones para ${selectId} desde ${url}...`);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log(`Datos recibidos de ${url}:`, data);

            const select = document.getElementById(selectId);
            if (!select) {
                console.error(`Error: No se encontró el <select> con ID: ${selectId}`);
                return;
            }

            select.innerHTML = '<option value="">Seleccione una opción</option>';
            data.forEach(item => {
                const optionText = formatFunction ? formatFunction(item) : item.nombre;
                select.insertAdjacentHTML("beforeend", `<option value="${item.id}">${optionText}</option>`);
            });

            console.log(`Opciones agregadas al <select> #${selectId}`);
        })
        .catch(error => console.error(`Error al cargar las opciones desde ${url}:`, error));
}


    // Formatear opciones de descuento
    function formatDiscountOption(item) {
        const fecha = new Date(item.fecha);
        const fechaFormateada = fecha.toLocaleDateString("es-ES", { year: "numeric", month: "long" });
        return `${item.nombre} \ ${item.porcentaje}% \ ${fechaFormateada}`;
    }

    // Eventos para abrir modales de edición
    document.getElementById("editBrandModalButton")?.addEventListener("click", () => openModal("editBrandModal", "load_brands.php", "brandSelect"));
    document.getElementById("editDiscountModalButton")?.addEventListener("click", () => openModal("editDiscountModal", "load_discounts.php", "discountSelect", formatDiscountOption));

    // Eventos para abrir modales de eliminación
    document.getElementById("openDeleteCategoryModal")?.addEventListener("click", () => openModal("deleteCategoryModal", "list_categories.php", "deleteCategorySelect"));
    document.getElementById("openDeleteBrandModal")?.addEventListener("click", () => openModal("deleteBrandModal", "list_brands.php", "deleteBrandSelect"));
    document.getElementById("openDeleteDiscountModal")?.addEventListener("click", () => openModal("deleteDiscountModal", "list_discounts.php", "deleteDiscountSelect"));

    // Función para manejar envíos de formularios de edición
    function handleFormSubmit(formId, url, modalId) {
        document.getElementById(formId)?.addEventListener("submit", function (event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch(url, { method: "POST", body: formData })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || "Actualización exitosa");
                    closeModal(modalId);
                    setTimeout(() => table?.ajax.reload(null, false), 500);
                })
                .catch(error => alert(`Error: ${error}`));
        });
    }

    handleFormSubmit("editBrandForm", "update_brand.php", "editBrandModal");
    handleFormSubmit("editDiscountForm", "update_discount.php", "editDiscountModal");

    // Función para eliminar elementos
    function deleteItem(url, selectId, successMessage) {
        const id = document.getElementById(selectId)?.value;
        if (!id) return alert("Selecciona un elemento para eliminar.");
        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? successMessage : data.error || "Error al eliminar");
                if (data.success) {
                    document.getElementById(selectId)?.querySelector(`option[value="${id}"]`)?.remove();
                }
            })
            .catch(error => console.error("Error al eliminar:", error));
    }

    // Eventos para confirmar eliminación
    document.getElementById("confirmDeleteCategory")?.addEventListener("click", () => deleteItem("delete_category.php", "deleteCategorySelect", "Categoría eliminada exitosamente."));
    document.getElementById("confirmDeleteBrand")?.addEventListener("click", () => deleteItem("delete_brand.php", "deleteBrandSelect", "Marca eliminada exitosamente."));
    document.getElementById("confirmDeleteDiscount")?.addEventListener("click", () => deleteItem("delete_discount.php", "deleteDiscountSelect", "Descuento eliminado exitosamente."));
});


</script>






</body>

</html>