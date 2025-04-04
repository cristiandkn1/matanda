<?php 
require_once 'db.php';

// Crea una instancia de la clase Database
$database = new Database();
$conn = $database->conn;

// Obtener el ID de la venta
$saleId = isset($_GET['idventa']) ? intval($_GET['idventa']) : 0;

if ($saleId > 0) {
    // Consulta para obtener detalles de la venta
    $query = "SELECT estado, notas FROM venta WHERE idventa = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $saleId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sale = $result->fetch_assoc();

        // Garantizar que las notas no estén vacías
        if (empty($sale['notas'])) {
            $sale['notas'] = ''; // Establece una cadena vacía si no hay notas
        }

        echo json_encode($sale);
    } else {
        echo json_encode(['error' => 'Venta no encontrada']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'ID de venta inválido']);
}

$conn->close();
?>
