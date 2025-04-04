<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$nombre = $data["nombre"];
$precio = $data["precio"];

$db = new Database();
$sql = "INSERT INTO valores_reparto (nombre, precio) VALUES (?, ?)";
$stmt = $db->conn->prepare($sql);
$stmt->bind_param("sd", $nombre, $precio);

echo json_encode(["success" => $stmt->execute()]);
$stmt->close();
$db->close();
?>
