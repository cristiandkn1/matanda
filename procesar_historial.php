<?php
// Iniciar la sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Conectar a la base de datos
require_once 'db.php';
$database = new Database();
$conn = $database->conn;

// Obtener las fechas de inicio y fin desde el formulario (si se enviaron)
if (isset($_POST['startDate']) && isset($_POST['endDate'])) {
    $fechaInicio = $_POST['startDate'];  // Ejemplo: '2025-02-15 00:00:00'
    $fechaFin = $_POST['endDate'];      // Ejemplo: '2025-02-15 23:59:59'

    // Consulta SQL para obtener las ventas dentro del rango de fechas
    $query = "SELECT v.idventa, vd.producto_idproducto, p.nombre AS producto, vd.cantidad, vd.precio, vd.subtotal, v.pago_idpago
              FROM venta v
              JOIN venta_detalle vd ON v.idventa = vd.venta_idventa
              JOIN producto p ON vd.producto_idproducto = p.idproducto
              WHERE v.fecha BETWEEN ? AND ?";  // Se utiliza BETWEEN para seleccionar entre dos fechas

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $result = $stmt->get_result();

    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }

    // Aquí puedes generar la tabla HTML o enviar los datos como JSON
    echo json_encode($ventas);
}

// Cerrar conexión
$database->close();
?>
