
<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de conexión a la base de datos
require_once 'db.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    // Si no tiene permiso, redirigir o mostrar error
    header('Location: login.php'); // o usar index.php, login.php, etc.
    exit;
}

// Crear la instancia de la base de datos
$db = new Database();
$conn = $db->conn;
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
<!-- Estilos de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<!-- Script de Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Scripts de DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<?php include 'navbar.html'; ?>


<h2 style="text-align: center; margin-top: 20px;">Gestión de Reparto</h2>

<div style="text-align: center; margin: 40px 0;">
  <button id="abrirModalVentas">Seleccionar Venta</button>
  <button id="abrirModalValoresReparto" class="boton-azul">Editar Valores de Reparto</button>
</div>






<!-- Modal para Editar y Eliminar Valores de Reparto -->
<div id="modalValoresReparto" class="modal-valores">
    <div class="modal-valores-content">
        <span class="close-valores" onclick="cerrarModalValoresReparto()">&times;</span>
        <h2>Editar Valores de Reparto</h2>
        <table id="tablaValoresReparto" class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Se llenará dinámicamente -->
            </tbody>
        </table>
        <button onclick="abrirFormularioNuevoValor()">Agregar Nuevo</button>
    </div>
</div>

<!-- Modal para Agregar/Editar un Valor de Reparto -->
<div id="modalEditarValorReparto" class="modal-valores">
    <div class="modal-valores-content">
        <span class="close-valores" onclick="cerrarFormularioValorReparto()">&times;</span>
        <h2 id="tituloModalValor">Agregar Valor de Reparto</h2>
        <input type="hidden" id="idValorReparto">
        <label>Nombre:</label>
        <input type="text" id="nombreValorReparto">
        <label>Precio:</label>
        <input type="number" id="precioValorReparto">
        <button onclick="guardarValorReparto()">Guardar</button>
    </div>
</div>





















<!-- Modal de Ventas-->
<div id="modalVentas" class="modal-ventas">
    <div class="modal-ventas-content">
        <span class="close closeVentas">&times;</span>
        <h2>Seleccionar Venta</h2>

       <!-- Select2 para buscar ventas -->
<select id="buscadorVentas" class="input-busqueda" style="width: 100%;">
    <option value="">Buscar por ID</option>
</select>


        <!-- Contenedor con desplazamiento -->
        <div class="tabla-container">
            <table id="tablaVentas" class="table">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Se cargan dinámicamente las ventas desde la BD -->
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Tabla de Reparto -->
<div class="table-container">
    <table id="tablaReparto" class="table table-striped">
        <thead>
            <tr>
                <th>ID Venta</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Estado de Pago</th>
                <th>Reparto</th>
                <th>Estado</th> <!-- ✅ Nueva columna -->
                <th>Valor del Reparto</th>
                <th>Cant. Pagada</th>
                <th>Método de Pago</th> <!-- ✅ Nueva columna -->
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Las filas se agregarán dinámicamente -->
        </tbody>
    </table>
</div>

<style>
/* Estilos del modal */
.modal-reparto {
    display: none; /* Oculto por defecto */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7); /* Fondo oscuro semitransparente */
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Contenido del modal */
.modal-reparto-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
    position: relative;
}

/* Botón de cerrar */
.close-reparto {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
}

.modal-reparto.activo {
    display: flex; /* Se muestra cuando tiene la clase 'activo' */
}

/* Contenedor del formulario en el modal */
#formReparto {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Etiquetas del formulario */
#formReparto label {
    font-weight: bold;
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
}

/* Estilos generales para inputs y selects */
#formReparto input,
#formReparto select {
    width: 100%;
    padding: 8px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: inset 0px 1px 3px rgba(0, 0, 0, 0.1);
    transition: border-color 0.3s, box-shadow 0.3s;
}

/* Cambia el borde cuando el usuario escribe */
#formReparto input:focus,
#formReparto select:focus {
    border-color: #007bff;
    box-shadow: 0px 0px 6px rgba(0, 123, 255, 0.5);
    outline: none;
}

/* Input de solo lectura */
#formReparto input[readonly] {
    background-color: #f0f0f0;
    cursor: not-allowed;
}

/* Botón de guardar cambios */
#formReparto button {
    background-color: #28a745; /* Verde */
    color: white;
    font-size: 14px;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
    margin-top: 10px;
}

#formReparto button:hover {
    background-color: #218838;
    transform: scale(1.05);
}

#formReparto button:active {
    transform: scale(0.95);
}

</style>

<!-- Modal para Ajustar Reparto -->
<div id="modalReparto" class="modal-reparto">
    <div class="modal-reparto-content">
        <span class="close-reparto" onclick="cerrarModalReparto()">&times;</span>
        <h2>Ajustar Reparto</h2>
        <form id="formReparto">
            <label>ID Venta:</label>
            <input type="text" id="idVentaModal" readonly>

            <label>Monto:</label>
            <input type="text" id="montoModal" readonly>

            <label>Dirección:</label>
            <input type="text" id="direccionModal">

            <label>Teléfono:</label>
            <input type="text" id="telefonoModal">

            <label>Estado de Pago:</label>
            <select id="estadoPagoModal">
                <option value="Por pagar">Por pagar</option>
                <option value="Pagado">Pagado</option>
            </select>

            <label>Reparto:</label>
            <select id="repartoModal">
                <option value="Sí">Sí</option>
                <option value="No">No</option>
            </select>


            <label>Seleccionar Valor del Reparto:</label>
            <select   select id="valorRepartoModal">
            <option value="">Seleccione una opción</option>
            </select>

            <button type="button" onclick="guardarAjustesReparto()">Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    // Elementos del DOM
    var modalVentas = document.getElementById("modalVentas");
    var abrirModalVentas = document.getElementById("abrirModalVentas");
    var cerrarModalVentas = document.querySelector(".closeVentas");

    // Verifica que el modal esté oculto al inicio
    modalVentas.style.display = "none";

    // Función para abrir el modal
    abrirModalVentas.addEventListener("click", function () {
        modalVentas.style.display = "flex"; // Mostrar solo cuando se haga clic
        cargarVentas(); // Llamar a la función aquí
    });

    // Función para cerrar el modal al hacer clic en la "X"
    cerrarModalVentas.addEventListener("click", function () {
        modalVentas.style.display = "none";
    });

    // Cerrar el modal si el usuario hace clic fuera del contenido
    window.addEventListener("click", function (event) {
        if (event.target === modalVentas) {
            modalVentas.style.display = "none";
        }
    });
});

// Función para cargar ventas en el modal de seleccionar ventas
function cargarVentas() {
    fetch("cargar_ventas.php") // Llama al script PHP
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector("#tablaVentas tbody");
            tbody.innerHTML = ""; // Limpia la tabla antes de agregar nuevas filas

            data.forEach(venta => {
                let fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${venta.idventa}</td>
                    <td>${venta.monto}</td>
                    <td>${venta.fecha}</td>
                    <td><button class="seleccionarVenta" data-id="${venta.idventa}" data-monto="${venta.monto}" data-fecha="${venta.fecha}">Seleccionar</button></td>
                `;
                tbody.appendChild(fila);
            });

            // Agregar eventos a los botones de selección
            document.querySelectorAll(".seleccionarVenta").forEach(boton => {
                boton.addEventListener("click", function () {
                    let id = this.getAttribute("data-id");
                    let monto = this.getAttribute("data-monto");
                    let fecha = this.getAttribute("data-fecha");

                    agregarVentaAReparto(id, monto, fecha);
                });
            });
        })
        .catch(error => console.error("Error al cargar ventas:", error));
}

</script>

<script>
    //buscar ventas en el modal de buscar ventas
    document.addEventListener("DOMContentLoaded", function () {
        let selectVentas = $("#buscadorVentas");

        // Inicializar Select2
        selectVentas.select2({
            placeholder: "Buscar por ID",
            allowClear: true
        });

        // Cargar ventas en el Select2 desde la BD
        function cargarVentasEnSelect() {
            fetch("cargar_ventas.php")
                .then(response => response.json())
                .then(data => {
                    selectVentas.empty(); // Limpiar el select antes de agregar opciones
                    selectVentas.append(new Option("Buscar por ID", "", false, false)); // Opción por defecto

                    data.forEach(venta => {
                        let opcion = new Option(`${venta.idventa} - $${venta.monto} - ${venta.fecha}`, venta.idventa, false, false);
                        selectVentas.append(opcion);
                    });

                    selectVentas.trigger("change"); // Refrescar Select2
                })
                .catch(error => console.error("Error al cargar ventas:", error));
        }

        // Cargar ventas cuando se abra el modal
        document.getElementById("abrirModalVentas").addEventListener("click", function () {
            cargarVentasEnSelect();
        });

        // Evento cuando se selecciona una venta en el Select2
        selectVentas.on("select2:select", function (e) {
            let idSeleccionado = e.params.data.id;

            // Filtrar la tabla y mostrar solo la venta seleccionada
            let filas = document.querySelectorAll("#tablaVentas tbody tr");
            filas.forEach(fila => {
                fila.style.display = fila.cells[0].innerText === idSeleccionado ? "" : "none";
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        console.log("Documento cargado, listo para agregar eventos.");

        // Ocultar el modal al cargar la página
        document.getElementById("modalReparto").style.display = "none";

        // Delegación de eventos para botones "Ajustar Reparto"
        document.addEventListener("click", function (event) {
            if (event.target.classList.contains("ajustarReparto")) {
                let boton = event.target;

                let id = boton.getAttribute("data-id");
                let monto = boton.getAttribute("data-monto");
                let direccion = boton.getAttribute("data-direccion");
                let telefono = boton.getAttribute("data-telefono");
                let estadoPago = boton.getAttribute("data-estado-pago") || "Por pagar";
                let reparto = boton.getAttribute("data-reparto");

                console.log("Botón Ajustar Reparto clickeado con datos:", { id, monto, direccion, telefono, estadoPago, reparto });

                abrirModalReparto(id, monto, direccion, telefono, estadoPago, reparto);
            }
        });

        // Delegación de eventos para botones "Eliminar"
        document.addEventListener("click", function (event) {
            if (event.target.classList.contains("btnEliminar")) {
                let id = event.target.getAttribute("data-id");
                eliminarVenta(id);
            }
        });
    });

    // AGREGAR VENTA A LA TABLA REPARTO
function agregarVentaAReparto(id, monto, fecha, direccion = "Indefinido", telefono = "Sin número", estadoPago = "Por pagar", reparto = "No") {
    let tablaReparto = document.querySelector("#tablaReparto tbody");

    if (document.querySelector(`#tablaReparto tbody tr[data-id="${id}"]`)) {
        Swal.fire({ icon: "warning", title: "Venta duplicada", text: "Esta venta ya está en la tabla de repartos." });
        return;
    }

    let fila = document.createElement("tr");
    fila.setAttribute("data-id", id);
    fila.innerHTML = `
        <td>${id}</td>
        <td>${Math.round(monto)}</td>
        <td>${fecha}</td>
        <td>${direccion}</td>
        <td>${telefono}</td>
        <td>${estadoPago}</td>
        <td>${reparto}</td>
        <td>
            <button class="ajustarReparto" data-id="${id}">Ajustar Reparto</button>
            <button class="btnEliminar" data-id="${id}">Eliminar</button>
        </td>
    `;
    tablaReparto.appendChild(fila);

    Swal.fire({ icon: "success", title: "Venta añadida", text: "Ajústala para aplicar los cambios." });
}

// ABRIR MODAL CON DATOS
function abrirModalReparto(id, monto, direccion, telefono, estadoPago, reparto) {
    if (!id) return;

    document.getElementById("idVentaModal").value = id;
    document.getElementById("montoModal").value = Math.round(monto);

    // Limpiar "Indefinido" o valores vacíos
    document.getElementById("direccionModal").value = (direccion && direccion !== "Indefinido") ? direccion : "";
    document.getElementById("telefonoModal").value = (telefono && telefono !== "Sin número") ? telefono : "";

    document.getElementById("estadoPagoModal").value = estadoPago || "Por pagar";

    // Siempre cargar con "Sí"
    document.getElementById("repartoModal").value = "Sí";

    document.getElementById("modalReparto").style.display = "flex";
}

// ACTUALIZAR REPARTO
function guardarAjustesReparto() {
    let id = document.getElementById("idVentaModal").value;
    let direccion = document.getElementById("direccionModal").value || "Indefinido";
    let telefono = document.getElementById("telefonoModal").value || "Sin número";
    let estadoPago = document.getElementById("estadoPagoModal").value;
    let reparto = document.getElementById("repartoModal").value;
    let valorRepartoId = document.getElementById("valorRepartoModal").value; // ID del valor de reparto seleccionado

    if (estadoPago === "Pagado") {
        reparto = "Sí";
        document.getElementById("repartoModal").value = "Sí";
    }

    fetch("actualizar_reparto.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ 
            idventa: id, 
            direccion, 
            telefono, 
            estado_pago: estadoPago, 
            reparto,
            valor_reparto_id: valorRepartoId // Enviar el valor de reparto seleccionado
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: "success", title: "Reparto actualizado", text: "Se han guardado los cambios." });
            
            // ✅ ACTUALIZAR LA TABLA DESPUÉS DE GUARDAR
            actualizarFilaEnTabla(id);
            
            cerrarModalReparto();
        } else {
            Swal.fire({ icon: "error", title: "Error", text: "Inténtalo nuevamente." });
        }
    })
    .catch(error => console.error("Error:", error));
}

// ✅ FUNCIÓN PARA ACTUALIZAR LA FILA EN LA TABLA
function actualizarFilaEnTabla(id) {
    fetch("obtener_repartos.php")
        .then(response => response.json())
        .then(ventas => {
            let ventaActualizada = ventas.find(venta => venta.idventa == id);
            if (!ventaActualizada) return;

            let fila = document.querySelector(`#tablaReparto tbody tr[data-id="${id}"]`);
            if (fila) {
                // Estilo dinámico para estado
                let colorEstado = '';
                switch (ventaActualizada.estado) {
                    case 'Entregado':
                        colorEstado = 'green';
                        break;
                    case 'Cancelado':
                        colorEstado = 'red';
                        break;
                    default:
                        colorEstado = 'orange';
                }

                // Método de pago legible
                let metodoPagoTexto = '';
                switch (ventaActualizada.pago_idpago) {
                    case '1':
                    case 1:
                        metodoPagoTexto = 'Débito';
                        break;
                    case '2':
                    case 2:
                        metodoPagoTexto = 'Efectivo';
                        break;
                    case '3':
                    case 3:
                        metodoPagoTexto = 'Transferencia';
                        break;
                    case '4':
                    case 4:
                        metodoPagoTexto = 'Crédito';
                        break;
                    default:
                        metodoPagoTexto = 'No especificado';
                }

                fila.innerHTML = `
                    <td>${ventaActualizada.idventa}</td>
                    <td>${Math.floor(parseFloat(ventaActualizada.monto) || 0).toLocaleString("es-ES")}</td>
                    <td>${ventaActualizada.fecha}</td>
                    <td>${ventaActualizada.direccion}</td>
                    <td>${ventaActualizada.telefono}</td>
                    <td>${ventaActualizada.estado_pago}</td>
                    <td>${ventaActualizada.reparto}</td>
                    <td><span style="color: ${colorEstado}; font-weight: bold;">${ventaActualizada.estado}</span></td>
                    <td>${ventaActualizada.nombre_reparto} - $${ventaActualizada.precio_reparto.toLocaleString("es-ES")}</td>
                    <td>$${parseFloat(ventaActualizada.cantidad_pagada || 0).toLocaleString("es-ES")}</td>
                    <td>${metodoPagoTexto}</td> <!-- ✅ Método de pago -->
                    <td>
                        <button class="ajustarReparto"
                            data-id="${ventaActualizada.idventa}"
                            data-monto="${ventaActualizada.monto}"
                            data-direccion="${ventaActualizada.direccion}"
                            data-telefono="${ventaActualizada.telefono}"
                            data-estado-pago="${ventaActualizada.estado_pago}"
                            data-reparto="${ventaActualizada.reparto}"
                            data-valor-reparto="${ventaActualizada.precio_reparto}">
                            Ajustar Reparto
                        </button>
                        <button class="btnEliminar" data-id="${ventaActualizada.idventa}">Eliminar</button>
                    </td>
                `;
            }
        })
        .catch(error => console.error("Error al actualizar la tabla:", error));
}



// ELIMINAR VENTA
function eliminarVenta(id) {
    Swal.fire({
        title: "¿Eliminar reparto?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('eliminar_reparto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `idventa=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Eliminado", "El reparto ha sido eliminado.", "success");

                    // ✅ Vuelve a cargar la tabla completa para reflejar el cambio
                    actualizarTablaReparto();
                } else {
                    Swal.fire("Error", data.error || "No se pudo eliminar el reparto.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
            });
        }
    });
}

// CERRAR MODAL
function cerrarModalReparto() {
    console.log("Cerrando modal...");
    document.getElementById("modalReparto").style.display = "none";
}

// CERRAR MODAL AL HACER CLIC FUERA DEL CONTENIDO
window.addEventListener("click", function (event) {
    let modal = document.getElementById("modalReparto");
    if (event.target === modal) {
        cerrarModalReparto();
    }
});

// EVENTO PARA ACTUALIZAR REPARTO AL SELECCIONAR PAGADO
document.getElementById("estadoPagoModal").addEventListener("change", function() {
    if (this.value === "Pagado") {
        document.getElementById("repartoModal").value = "Sí";
    }
});

</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    actualizarTablaReparto(); // ✅ Usa la función que ya limpia y vuelve a cargar
});

function agregarVentaAReparto(id, monto, fecha, direccion = "Indefinido", telefono = "Sin número", estadoPago = "Por pagar", reparto = "No", nombreReparto = "No asignado", precioReparto = "$0", mostrarAlerta = true, cantidadPagada = 0) {
    let tablaReparto = document.querySelector("#tablaReparto tbody");

    if (document.querySelector(`#tablaReparto tbody tr[data-id="${id}"]`)) {
        if (mostrarAlerta) {
            Swal.fire({
                icon: "warning",
                title: "Venta duplicada",
                text: "Esta venta ya está en la tabla de repartos."
            });
        }
        return;
    }

    const estado = "Pendiente";
    const colorEstado = "orange";

    let fila = document.createElement("tr");
    fila.setAttribute("data-id", id);
    fila.innerHTML = `
        <td>${id}</td>
        <td>${Math.round(monto)}</td>
        <td>${fecha}</td>
        <td>${direccion}</td>
        <td>${telefono}</td>
        <td>${estadoPago}</td>
        <td>${reparto}</td>
        <td><span style="color: ${colorEstado}; font-weight: bold;">${estado}</span></td>
        <td>${nombreReparto} - ${precioReparto}</td>
        <td>$${parseFloat(cantidadPagada || 0).toLocaleString("es-ES")}</td>
        <td>
            <button class="ajustarReparto"
                data-id="${id}"
                data-monto="${monto}"
                data-direccion="${direccion}"
                data-telefono="${telefono}"
                data-estado-pago="${estadoPago}"
                data-reparto="${reparto}"
                data-valor-reparto="${precioReparto}">
                Ajustar Reparto
            </button>
            <button class="btnEliminar" data-id="${id}">Eliminar</button>
        </td>
    `;
    tablaReparto.prepend(fila);

    if (mostrarAlerta) {
        Swal.fire({
            icon: "success",
            title: "Venta añadida a la tabla reparto",
            text: "Ajústala para aplicar los cambios."
        });
    }
}

</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    fetch("obtener_valores_reparto.php") // Archivo PHP que traerá los valores
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById("valorRepartoModal");
            data.forEach(valor => {
                let option = document.createElement("option");
                option.value = valor.id; // Debe ser el ID del valor de reparto
                option.textContent = `${valor.nombre} - $${valor.precio}`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error("Error al cargar valores de reparto:", error));
});

</script>

<script>
    document.getElementById("abrirModalValoresReparto").addEventListener("click", function () {
    cargarValoresReparto();
    document.getElementById("modalValoresReparto").style.display = "flex";
});

// Cerrar modales
function cerrarModalValoresReparto() {
    document.getElementById("modalValoresReparto").style.display = "none";
}
function cerrarFormularioValorReparto() {
    document.getElementById("modalEditarValorReparto").style.display = "none";
}

// Cargar los valores de reparto desde la BD
function cargarValoresReparto() {
    fetch("obtener_valores_reparto.php")
        .then(response => response.json())
        .then(data => {
            let tabla = document.querySelector("#tablaValoresReparto tbody");
            tabla.innerHTML = ""; // Limpiar antes de cargar
            data.forEach(valor => {
                let fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${valor.id}</td>
                    <td>${valor.nombre}</td>
                    <td>$${valor.precio.toLocaleString("es-ES")}</td>
                    <td>
                        <button onclick="editarValorReparto(${valor.id}, '${valor.nombre}', ${valor.precio})">Editar</button>
                        <button onclick="eliminarValorReparto(${valor.id})">Eliminar</button>
                    </td>
                `;
                tabla.appendChild(fila);
            });
        })
        .catch(error => console.error("Error al cargar valores de reparto:", error));
}

// Abrir modal para agregar un nuevo valor de reparto
function abrirFormularioNuevoValor() {
    document.getElementById("tituloModalValor").textContent = "Agregar Valor de Reparto";
    document.getElementById("idValorReparto").value = "";
    document.getElementById("nombreValorReparto").value = "";
    document.getElementById("precioValorReparto").value = "";
    document.getElementById("modalEditarValorReparto").style.display = "flex";
}

// Abrir modal para editar un valor de reparto existente
function editarValorReparto(id, nombre, precio) {
    document.getElementById("tituloModalValor").textContent = "Editar Valor de Reparto";
    document.getElementById("idValorReparto").value = id;
    document.getElementById("nombreValorReparto").value = nombre;
    document.getElementById("precioValorReparto").value = precio;
    document.getElementById("modalEditarValorReparto").style.display = "flex";
}

// Guardar nuevo o editar valor de reparto
function guardarValorReparto() {
    let id = document.getElementById("idValorReparto").value;
    let nombre = document.getElementById("nombreValorReparto").value;
    let precio = document.getElementById("precioValorReparto").value;

    let url = id ? "actualizar_valor_reparto.php" : "insertar_valor_reparto.php";
    let datos = { id, nombre, precio };

    fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: "success", title: "Éxito", text: "Valor de reparto guardado correctamente." });
            cargarValoresReparto(); // ✅ Actualiza la tabla de valores de reparto
            actualizarTablaReparto(); // ✅ Ahora actualiza la tabla de reparto
            cerrarFormularioValorReparto();
        } else {
            Swal.fire({ icon: "error", title: "Error", text: "No se pudo guardar." });
        }
    })
    .catch(error => console.error("Error:", error));
}


// Eliminar un valor de reparto
function eliminarVenta(id) {
    Swal.fire({
        title: "¿Eliminar reparto?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('eliminar_reparto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `idventa=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Eliminado", "El reparto ha sido eliminado.", "success");

                    // ✅ Recargar la tabla desde la BD
                    actualizarTablaReparto();
                } else {
                    Swal.fire("Error", data.error || "No se pudo eliminar el reparto.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
            });
        }
    });
}

// Cerrar modales al hacer clic fuera
window.addEventListener("click", function (event) {
    let modalValores = document.getElementById("modalValoresReparto");
    let modalEditar = document.getElementById("modalEditarValorReparto");

    if (event.target === modalValores) {
        cerrarModalValoresReparto();
    }
    if (event.target === modalEditar) {
        cerrarFormularioValorReparto();
    }
});

// Cerrar modal de valores de reparto
function cerrarModalValoresReparto() {
    document.getElementById("modalValoresReparto").style.display = "none";
}

// Cerrar formulario de edición/agregar valor de reparto
function cerrarFormularioValorReparto() {
    document.getElementById("modalEditarValorReparto").style.display = "none";
}

function actualizarTablaReparto() {
    fetch("obtener_repartos.php")
        .then(response => response.json())
        .then(ventas => {
            let tabla = document.querySelector("#tablaReparto tbody");
            tabla.innerHTML = "";

            ventas.forEach(venta => {
                let fila = document.createElement("tr");
                fila.setAttribute("data-id", venta.idventa);

                let colorEstado = "orange";
                if (venta.estado === "Entregado") colorEstado = "green";
                else if (venta.estado === "Cancelado") colorEstado = "red";

                // ✅ Interpretar método de pago
                let metodoPagoTexto = '';
                switch (venta.pago_idpago) {
                    case '1':
                    case 1:
                        metodoPagoTexto = 'Débito';
                        break;
                    case '2':
                    case 2:
                        metodoPagoTexto = 'Efectivo';
                        break;
                    case '3':
                    case 3:
                        metodoPagoTexto = 'Transferencia';
                        break;
                    case '4':
                    case 4:
                        metodoPagoTexto = 'Crédito';
                        break;
                    default:
                        metodoPagoTexto = 'No especificado';
                }

                fila.innerHTML = `
                    <td>${venta.idventa}</td>
                    <td>${Math.floor(parseFloat(venta.monto) || 0).toLocaleString("es-ES")}</td>
                    <td>${venta.fecha}</td>
                    <td>${venta.direccion}</td>
                    <td>${venta.telefono}</td>
                    <td>${venta.estado_pago}</td>
                    <td>${venta.reparto}</td>
                    <td><span style="color: ${colorEstado}; font-weight: bold;">${venta.estado}</span></td>
                    
                    <td>
                        ${venta.nombre_reparto} - $${venta.precio_reparto.toLocaleString("es-ES")}<br>
                        <small><strong>Reparto tomado por:</strong> ${venta.reparto_tomado_por || "—"}</small>
                    </td>
                    <td>$${parseFloat(venta.cantidad_pagada || 0).toLocaleString("es-ES")}</td>
                    <td>${metodoPagoTexto}</td> <!-- ✅ Método de pago -->

                    <td>
                        <button class="ajustarReparto"
                            data-id="${venta.idventa}"
                            data-monto="${venta.monto}"
                            data-direccion="${venta.direccion}"
                            data-telefono="${venta.telefono}"
                            data-estado-pago="${venta.estado_pago}"
                            data-reparto="${venta.reparto}"
                            data-valor-reparto="${venta.precio_reparto}">
                            Ajustar Reparto
                        </button>
                        <button class="btnEliminar" data-id="${venta.idventa}">Eliminar</button>
                    </td>
                `;
                tabla.appendChild(fila);
            });
        })
        .catch(error => console.error("Error al actualizar la tabla de reparto:", error));
}


</script>
</body>