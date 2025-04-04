<?php
require_once 'db.php';
$database = new Database();
$conn = $database->conn;

$id = $_POST['id'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$id || !$tipo) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Obtener cantidad actual
$stmt = $conn->prepare("SELECT cantidad FROM producto WHERE idproducto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$cantidad = (int)$row['cantidad'];

if ($tipo === 'incrementar') {
    $cantidad++;
} elseif ($tipo === 'disminuir') {
    if ($cantidad <= 0) {
        echo json_encode(['success' => false, 'message' => 'No se puede reducir más. La cantidad ya es 0']);
        exit;
    }
    $cantidad--;
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo no válido']);
    exit;
}

// Actualizar cantidad
$update = $conn->prepare("UPDATE producto SET cantidad = ? WHERE idproducto = ?");
$update->bind_param("ii", $cantidad, $id);
$update->execute();

echo json_encode(['success' => true]);
?>
