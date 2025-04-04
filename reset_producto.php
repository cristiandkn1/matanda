<?php
include 'db.php';

if (isset($_POST['idproducto'])) {
    $idproducto = $_POST['idproducto'];
    $query = "UPDATE producto SET vendidos_mes = 0 WHERE idproducto = $idproducto";
    
    if ($conn->query($query)) {
        echo "Ventas del mes reiniciadas para el producto";
    } else {
        echo "Error al reiniciar ventas: " . $conn->error;
    }
}

$conn->close();
?>
