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

<?php
// Establecer la zona horaria de Chile
date_default_timezone_set('America/Santiago');

// Iniciar la conexión con la base de datos
$db = new Database();
$conn = $db->conn;

// Obtener la fecha actual con la zona horaria correcta
$mesActual = date('m');
$anioActual = date('Y');

// Verificar si ya se hizo el reinicio este mes
$query = "SELECT valor FROM configuracion WHERE clave = ?";
$stmt = $conn->prepare($query);
$clave = 'ultimo_reset_vendidos_mes';
$stmt->bind_param("s", $clave);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    throw new Exception("Error en la consulta de configuración: " . $conn->error);
}

$row = $result->fetch_assoc();
$ultimoReset = $row['valor'] ?? null;

if ($ultimoReset !== "$anioActual-$mesActual") {
    echo "<p style='color:green;'>✅ Reiniciando valores porque ha cambiado el mes...</p>";

    // Guardar los datos del mes actual en historial antes de reiniciar
    $query = "INSERT INTO historial_ventas (idproducto, mes, anio, total_vendido, ganancia)
              SELECT 
                  p.idproducto, 
                  MONTH(CURRENT_DATE), 
                  YEAR(CURRENT_DATE), 
                  IFNULL(SUM(vd.cantidad * vd.precio), 0), 
                  IFNULL(SUM(vd.cantidad * vd.precio) - (SUM(vd.cantidad) * p.precio_compra), 0)
              FROM producto p
              LEFT JOIN venta_detalle vd ON p.idproducto = vd.producto_idproducto
              LEFT JOIN venta v ON vd.venta_idventa = v.idventa
              WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE)
              AND YEAR(v.fecha) = YEAR(CURRENT_DATE)
              GROUP BY p.idproducto";

    if (!$conn->query($query)) {
        throw new Exception("Error al insertar en historial_ventas: " . $conn->error);
    }

    // Reiniciar `total_vendido` y `ganancia`
    $query = "UPDATE producto SET total_vendido = 0, ganancia = 0";
    if (!$conn->query($query)) {
        throw new Exception("Error al reiniciar valores de producto: " . $conn->error);
    }

    // Guardar la fecha del último reinicio
    $query = "INSERT INTO configuracion (clave, valor) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE valor = ?";
    $stmt = $conn->prepare($query);
    $nuevoValor = "$anioActual-$mesActual";
    $stmt->bind_param("sss", $clave, $nuevoValor, $nuevoValor);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar configuración: " . $conn->error);
    }

    // Mostrar enlace para descargar el PDF
    echo "<p><a href='reporte_mes_actual.pdf' download>📄 Descargar reporte del mes actual</a></p>";
}
?>





<?php
// Obtener los productos y filtrar solo ventas del mes actual
$query = "SELECT 
    p.idproducto, 
    p.nombre, 
    p.cantidad, 
    p.precio, 
    p.precio_compra,
    (SELECT IFNULL(SUM(vd.cantidad), 0)  
     FROM venta_detalle vd
     JOIN venta v ON vd.venta_idventa = v.idventa
     WHERE vd.producto_idproducto = p.idproducto 
     AND v.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') 
     AND v.fecha < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')) AS cantidad_vendida,

    FORMAT(
        (SELECT IFNULL(SUM(vd2.cantidad * vd2.precio), 0)
         FROM venta_detalle vd2
         JOIN venta v2 ON vd2.venta_idventa = v2.idventa
         WHERE vd2.producto_idproducto = p.idproducto 
         AND v2.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') 
         AND v2.fecha < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')),
        0, 'de_DE') AS total_vendido,

    FORMAT(
        (SELECT IFNULL(SUM(vd3.cantidad * vd3.precio), 0) - 
                      (IFNULL(SUM(vd3.cantidad), 0) * p.precio_compra)
         FROM venta_detalle vd3
         JOIN venta v3 ON vd3.venta_idventa = v3.idventa
         WHERE vd3.producto_idproducto = p.idproducto 
         AND v3.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') 
         AND v3.fecha < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')),
        0, 'de_DE') AS ganancia,

    CASE 
        WHEN p.cantidad < 5 AND 
            (SELECT SUM(vd4.cantidad) 
             FROM venta_detalle vd4 
             JOIN venta v4 ON vd4.venta_idventa = v4.idventa
             WHERE vd4.producto_idproducto = p.idproducto 
             AND v4.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') 
             AND v4.fecha < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')) > 5 
        THEN 'Reponer' 
        WHEN 
            (SELECT SUM(vd5.cantidad) 
             FROM venta_detalle vd5 
             JOIN venta v5 ON vd5.venta_idventa = v5.idventa
             WHERE vd5.producto_idproducto = p.idproducto 
             AND v5.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') 
             AND v5.fecha < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')) < 2 
        THEN 'No comprar más' 
        ELSE 'En stock' 
    END AS estado
FROM producto p
WHERE p.visible = 1
ORDER BY total_vendido DESC;";

$result = $conn->query($query);

// Verificar si la consulta de productos falló
if (!$result) {
    die("Error al obtener productos: " . $conn->error);
}
?>








<?php
require('fpdf/fpdf.php');
require_once 'db.php';
require_once 'mail.php'; // Archivo para enviar correos
use PHPMailer\PHPMailer\PHPMailer;

// Establecer zona horaria correcta para Chile
date_default_timezone_set('America/Santiago');

// Iniciar la conexión a la base de datos
$db = new Database();
$conn = $db->conn;

// Obtener la fecha actual
$mesActual = date('m');
$anioActual = date('Y');

// Verificar si el botón fue presionado
if (isset($_POST['generar_pdf'])) {
    generarPDF($conn, $anioActual, $mesActual);
    
    // Forzar la descarga del PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_mes_actual.pdf"');
    readfile('reporte_mes_actual.pdf');
    exit();
}

// Función para generar el PDF


function generarPDF($conn, $anioActual, $mesActual) {
    $query = "SELECT 
            p.nombre, 
            p.cantidad, 
            p.precio, 
            p.precio_compra,
            SUM(vd.cantidad) AS cantidad_vendida,
            SUM(vd.cantidad * vd.precio) AS total_vendido,
            (SUM(vd.cantidad * vd.precio) - (SUM(vd.cantidad) * p.precio_compra)) AS ganancia
          FROM venta_detalle vd
          JOIN venta v ON vd.venta_idventa = v.idventa
          JOIN producto p ON vd.producto_idproducto = p.idproducto
          WHERE DATE_FORMAT(v.fecha, '%Y-%m') = '$anioActual-$mesActual'
          GROUP BY p.idproducto
          ORDER BY total_vendido DESC";


    $result = $conn->query($query);
    if (!$result) {
        die("Error al obtener historial: " . $conn->error);
    }

    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(190, 10, "Historial de Ventas - $mesActual/$anioActual", 1, 1, 'C');
    $pdf->Ln(10);
    
    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 10, 'Producto', 1);
    $pdf->Cell(20, 10, 'Stock', 1);
    $pdf->Cell(20, 10, 'Precio', 1);
    $pdf->Cell(30, 10, 'Cant. Vendida', 1);
    $pdf->Cell(30, 10, 'Total Vendido', 1);
    $pdf->Cell(30, 10, 'Ganancia', 1);
    $pdf->Ln();

    // Insertar datos en la tabla
    $pdf->SetFont('Arial', '', 10);
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(50, 10, mb_convert_encoding($row['nombre'], 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(20, 10, $row['cantidad'], 1, 0, 'C');
        $pdf->Cell(20, 10, "$" . number_format($row['precio'], 2), 1, 0, 'C');
        $pdf->Cell(30, 10, $row['cantidad_vendida'], 1, 0, 'C');
        $pdf->Cell(30, 10, "$" . number_format($row['total_vendido'], 2), 1, 0, 'C');
        $pdf->Cell(30, 10, "$" . number_format($row['ganancia'], 2), 1, 0, 'C');
        $pdf->Ln();
    }

    // Guardar el PDF en el servidor
    $pdf->Output('F', 'reporte_mes_actual.pdf');
}

// Enviar reporte por correo el último día del mes a las 23:59
$ultimoDiaMes = date('t');
$horaActual = date('H:i');
if (date('d') == $ultimoDiaMes && $horaActual == '23:59') {
    enviarCorreo('reporte_mes_actual.pdf');
}

// Función para enviar el correo
function enviarCorreo($archivo) {
    $correoDestino = 'destinatario@example.com';
    $asunto = '📊 Reporte de Ventas Mensual';
    $mensaje = 'Adjunto encontrarás el reporte de ventas del mes actual.';

    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.tudominio.com'; // Servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'tuemail@tudominio.com'; // Email remitente
        $mail->Password = 'tu_contraseña'; // Contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del correo
        $mail->setFrom('tuemail@tudominio.com', 'Nombre Remitente');
        $mail->addAddress($correoDestino);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->addAttachment($archivo);

        // Enviar correo
        $mail->send();
        echo "✅ Correo enviado exitosamente a $correoDestino.";
    } catch (Exception $e) {
        echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
    }
}
?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bodega</title>

<link rel="stylesheet" href="css/style3.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <style>
    .reporte-container {
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* Alinea todo a la izquierda */
        text-align: left;
        margin-top: 20px;
        margin-left: 85%; /* Modifica este valor para moverlo más a la derecha */
    }

    .btn-generar {
        background-color: #007BFF;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-generar:hover {
        background-color: #0056b3;
    }

    .contador {
        margin-top: 15px;
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin-left: 83.4%; /* Modifica este valor para moverlo más a la derecha */

    }
</style>

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

    <!-- Botón de cerrar sesión -->
<form action="logout.php" method="POST" style="position: absolute; right: 20px;">
    <button type="submit" style="background: red; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px;">
        Cerrar Sesión
    </button>
</form>


</header>
 
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>
<div id="modalEditar" class="overlay-modal">
    <div class="modal-editar-precio">
        <div class="modal-content">
            <h3>Editar Precio de Compra</h3>
            <input type="hidden" id="productoId">
            <input type="number" id="nuevoPrecioCompra" step="1" placeholder="$0.00">
            <button id="guardarPrecio">Guardar</button>
            <button id="cerrarModal">Cancelar</button>
        </div>
    </div>
</div>

<div class="reporte-container">
    <form action="bodega.php" method="post">
        <button type="submit" name="generar_pdf" class="btn-generar">Descargar Reporte Mes</button>
    </form>
</div>




        <!-- Contador de días restantes -->
        <div class="contador" id="contador"></div>
    </div>

    <script>
        function actualizarContador() {
            const hoy = new Date();
            const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            const diasRestantes = (ultimoDia - hoy) / (1000 * 60 * 60 * 24);
            document.getElementById('contador').innerText = "📅 Días restantes del mes: " + Math.ceil(diasRestantes);
        }

        actualizarContador();
        setInterval(actualizarContador, 1000); // Actualiza el contador cada segundo
    </script>


<style>
    .modal { display: none; position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>

<div class="container mt-4">
    <h2 class="text-center">Bodega - Gestión de Productos</h2>
    <table id="tablaBodega" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Cantidad Vendida</th>
                <th>Vendido En $CLP</th>
                <th>Ganancia Del Pdto</th>
                <th>Estado</th>
                <th>Precio de Compra</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php
while ($row = $result->fetch_assoc()) {
    if ($row['cantidad_vendida'] > 0) { // Filtra los productos con cantidad vendida mayor a 0

        // Determinar la clase según la ganancia
        $ganancia = $row['ganancia'];
        $total_vendido = $row['total_vendido'];
        $clase_ganancia = "";

        if ($ganancia < 0) {
            $clase_ganancia = "ganancia-negativa"; // Rojo oscuro para pérdidas
        } elseif ($ganancia == $total_vendido) {
            $clase_ganancia = "ganancia-igual-total"; // Rojo claro si ganancia == total vendido
        }

        echo "<tr>
                <td>{$row['nombre']}</td>
                <td>{$row['cantidad']}</td>
                <td>\${$row['precio']}</td>
                <td>{$row['cantidad_vendida']}</td>
                <td>\${$row['total_vendido']}</td>
                
                <td class='{$clase_ganancia}'>\${$ganancia}</td>
                <td>{$row['estado']}</td>
                <td>\$" . number_format($row['precio_compra'], 0, '', '') . "</td>
                <td>
                    <button class='btn-editar' data-id='{$row['idproducto']}' data-precio='{$row['precio_compra']}'>
                        Agregar Precio de Compra
                    </button>
                </td>
              </tr>";
    }
}
?>

    </tbody>
    </table>
</div>






<script>
    $(document).ready(function() {
        $(".btn-editar").click(function() {
            let id = $(this).data("id");
            $("#productoId").val(id);
            $("#nuevoPrecioCompra").val("").attr("placeholder", "$0.00");
            $("#modalEditar").show();
        });

        $("#cerrarModal").click(function() {
            $("#modalEditar").hide();
        });

        $("#guardarPrecio").click(function() {
            let id = $("#productoId").val();
            let nuevoPrecio = $("#nuevoPrecioCompra").val();
            
            $.post("actualizar_precio.php", { id: id, precio: nuevoPrecio }, function(response) {
                alert(response);
                location.reload();
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#tablaBodega').DataTable({
            "pageLength": 100,
            "order": [[4, "desc"]] // Ordena por la columna 5 (Cantidad Vendida)
        });
    });
</script>






<script>
$(document).ready(function() {
    $(".btn-editar").click(function() {
        let id = $(this).data("id");
        let precio = $(this).data("precio");

        $("#productoId").val(id);
        $("#nuevoPrecioCompra").val(precio ? precio : ""); 
        $("#modalEditar").fadeIn(); 
    });

    $("#cerrarModal").click(function() {
        $("#modalEditar").fadeOut(); 
    });

    // Cerrar modal al hacer clic fuera de él
    $("#modalEditar").click(function(event) {
        if ($(event.target).closest(".modal-editar-precio").length === 0) {
            $("#modalEditar").fadeOut();
        }
    });

    $("#modalEditar").hide();
});
</script>



</body>
</html>
