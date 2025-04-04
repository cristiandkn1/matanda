<?php
// ✅ Habilitar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // Conexión a la base de datos

$database = new Database();
$conn = $database->conn;

// 📌 Solo permitir POST (a menos que sea depuración)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['debug'])) {
    echo "Método no permitido."; 
    exit;
}

// 📌 Verificar si el usuario está autenticado
if (!isset($_SESSION['user_email'])) {
    echo "Acceso denegado. Debe iniciar sesión.";
    exit;
}

$admin_email = "vega@gmail.com";
if ($_SESSION['user_email'] !== $admin_email) {
    echo "Acceso denegado. No tiene permisos para limpiar ventas.";
    exit;
}

// 📌 Leer los datos enviados como JSON
$data = json_decode(file_get_contents("php://input"), true);
$password = $data['password'] ?? '';

if (!$password) {
    echo "Debe ingresar una contraseña.";
    exit;
}

// 📌 Buscar la contraseña del administrador
$query = "SELECT contraseña FROM rol WHERE correo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

if (!$hashed_password) {
    echo "Error: La contraseña no fue encontrada en la base de datos.";
    exit;
}

// 📌 Comparar la contraseña ingresada con la almacenada
if (!password_verify($password, $hashed_password)) { // ✅ Aquí estaba el error
    echo "Contraseña incorrecta.";
    exit;
}


// 📌 Obtener la fecha actual
date_default_timezone_set('America/Santiago');
$fechaHoy = date('Y-m-d');

$conn->begin_transaction();

try {
    // 📌 Verificar si hay ventas abiertas antes de cerrarlas
    $query = "SELECT COUNT(*) FROM venta WHERE DATE(fecha) = ? AND cierre_id IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $fechaHoy);
    $stmt->execute();
    $stmt->bind_result($ventasCount);
    $stmt->fetch();
    $stmt->close();
    
    echo "Ventas abiertas encontradas: " . $ventasCount . "\n";

    if ($ventasCount == 0) {
        // 📌 Verificar si la tabla metodo_pago_monto tiene datos antes de actualizar
        $query = "SELECT COUNT(*) FROM metodo_pago_monto";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stmt->bind_result($totalMontos);
        $stmt->fetch();
        $stmt->close();
        
        if ($totalMontos === 0) {
            echo "No hay montos en la tabla metodo_pago_monto.";
            exit;
        }

        // Si no hay ventas, solo reiniciar los montos
        $query = "UPDATE metodo_pago_monto SET monto = 0";
        $stmt = $conn->prepare($query);
        if (!$stmt->execute()) {
            throw new Exception("Error al reiniciar los montos.");
        }
        $filas_afectadas = $stmt->affected_rows;
        $stmt->close();

        if ($filas_afectadas === 0) {
            throw new Exception("Advertencia: No se actualizaron los montos. Verifica los datos.");
        }

        echo "No hay ventas abiertas, pero los montos han sido reiniciados.";
        $conn->commit();
        exit;
    }

    // 📌 Generar un ID único para el cierre de ventas
    $cierreId = time();

    // 📌 Cerrar las ventas del día
    $query = "UPDATE venta SET cierre_id = ? WHERE DATE(fecha) = ? AND cierre_id IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $cierreId, $fechaHoy);
    if (!$stmt->execute()) {
        throw new Exception("Error al cerrar las ventas.");
    }
    $stmt->close();

    // 📌 Reiniciar los montos de la tabla metodo_pago_monto
    $query = "UPDATE metodo_pago_monto SET monto = 0";
    $stmt = $conn->prepare($query);
    if (!$stmt->execute()) {
        throw new Exception("Error al reiniciar los montos.");
    }
    $filas_afectadas = $stmt->affected_rows;
    $stmt->close();

    if ($filas_afectadas === 0) {
        throw new Exception("Advertencia: No se actualizaron los montos. Verifica los datos.");
    }

    // 📌 Confirmar la transacción
    $conn->commit();

    echo "Las ventas del día han sido cerradas y los montos reiniciados.";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

// 📌 Cerrar conexión
$conn->close();
?>