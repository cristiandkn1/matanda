<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start(); // Captura errores PHP para debugging

session_start();
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if (isset($_POST['idreparto'], $_POST['idventa'])) {
    $idReparto = intval($_POST['idreparto']);
    $idVenta = intval($_POST['idventa']);

    $db = new Database();
    $conn = $db->conn;

    if (!$conn) {
        $response['message'] = 'Error de conexión a la base de datos.';
    } else {
        // Verificar si la venta ya está pagada
        $checkPago = $conn->prepare("SELECT estado_pago FROM reparto WHERE idreparto = ?");
        $checkPago->bind_param("i", $idReparto);
        $checkPago->execute();
        $estado = $checkPago->get_result()->fetch_assoc()['estado_pago'];
        $checkPago->close();

        $yaPagado = (strtolower($estado) === 'pagado');

        if ($yaPagado) {
            // Solo actualizar estado y dejar monto como 0
            $stmt = $conn->prepare("UPDATE reparto SET estado = 'Entregado' WHERE idreparto = ?");
            $stmt->bind_param("i", $idReparto);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Reparto marcado como entregado (ya estaba pagado).';
            } else {
                $response['message'] = 'Error al actualizar el estado.';
            }

            $stmt->close();
        } else {
            // Requiere datos completos
            if (isset($_POST['metodo_pago'], $_POST['monto'])) {
                $pago = intval($_POST['metodo_pago']);
                $voucher = isset($_POST['voucher']) ? trim($_POST['voucher']) : null;
                $monto = floatval($_POST['monto']);

                $stmt1 = $conn->prepare("UPDATE reparto SET estado = 'Entregado', cantidad_pagada = ? WHERE idreparto = ?");
                $stmt2 = $conn->prepare("UPDATE venta SET pago_idpago = ?, voucher = ? WHERE idventa = ?");
                $stmt3 = $conn->prepare("UPDATE venta_detalle SET voucher = ? WHERE venta_idventa = ?");

                $debug = [];
                if (!$stmt1) $debug['stmt1'] = $conn->error;
                if (!$stmt2) $debug['stmt2'] = $conn->error;
                if (!$stmt3) $debug['stmt3'] = $conn->error;

                if ($stmt1 && $stmt2 && $stmt3) {
                    $stmt1->bind_param("di", $monto, $idReparto);
                    $stmt2->bind_param("isi", $pago, $voucher, $idVenta);
                    $stmt3->bind_param("si", $voucher, $idVenta);

                    if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Reparto finalizado correctamente.';
                    } else {
                        $response['message'] = 'Error al ejecutar las actualizaciones.';
                        $debug['stmt1_execute'] = $stmt1->error;
                        $debug['stmt2_execute'] = $stmt2->error;
                        $debug['stmt3_execute'] = $stmt3->error;
                    }

                    $stmt1->close();
                    $stmt2->close();
                    $stmt3->close();
                } else {
                    $response['message'] = 'Error al preparar las consultas.';
                }
            } else {
                $response['message'] = 'Faltan datos de pago.';
            }
        }

        $conn->close();
    }
} else {
    $response['message'] = 'Faltan parámetros básicos: ' . json_encode($_POST);
}

// Capturar cualquier salida inesperada de PHP
$phpDebug = ob_get_clean();
if (!empty($phpDebug)) {
    $debug['php_output'] = $phpDebug;
}

if (!empty($debug ?? [])) {
    $response['debug'] = $debug;
}

header('Content-Type: application/json');
echo json_encode($response);
