<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"];

$db = new Database();
$sql = "DELETE FROM valores_reparto WHERE id = ?";
$stmt = $db->conn->prepare($sql);
$stmt->bind_param("i", $id);

echo json_encode(["success" => $stmt->execute()]);
$stmt->close();
$db->close();
?>
