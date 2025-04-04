<?php
require_once 'db.php';

// Crea una instancia de la clase Database
$database = new Database();
$conn = $database->conn;

// Captura los filtros (si existen)
$dateFilter = isset($_GET['date']) ? $_GET['date'] : null;
$priceFilter = isset($_GET['price']) ? floatval($_GET['price']) : 0;

// Consulta base (agregamos v.reparto)
$query = "SELECT v.idventa, v.monto, v.fecha, p.nombre AS metodo_pago, v.estado, v.notas, v.reparto 
          FROM venta v
          LEFT JOIN pago p ON v.pago_idpago = p.idpago
          WHERE 1=1";

// Aplica filtros
if ($dateFilter) {
    $query .= " AND DATE_FORMAT(v.fecha, '%Y-%m') = ?";
}
if ($priceFilter > 0) {
    $query .= " AND v.monto >= ?";
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);
if ($dateFilter && $priceFilter > 0) {
    $stmt->bind_param("sd", $dateFilter, $priceFilter);
} elseif ($dateFilter) {
    $stmt->bind_param("s", $dateFilter);
} elseif ($priceFilter > 0) {
    $stmt->bind_param("d", $priceFilter);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['monto'] = round($row['monto']);
    $data[] = $row;
}

// Retorna los datos como JSON
echo json_encode(['data' => $data]);

$stmt->close();
$conn->close();
?>
