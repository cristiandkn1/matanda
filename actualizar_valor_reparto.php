<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"];
$nombre = $data["nombre"];
$precio = $data["precio"];

$db = new Database();
$sql = "UPDATE valores_reparto SET nombre = ?, precio = ? WHERE id = ?";
$stmt = $db->conn->prepare($sql);
$stmt->bind_param("sdi", $nombre, $precio, $id);

echo json_encode(["success" => $stmt->execute()]);
$stmt->close();
$db->close();
?>
