<?php
require_once 'db.php';

// Crea una instancia de la clase Database
$database = new Database();
$conn = $database->conn;

// Consulta para obtener productos
$query = "SELECT p.idproducto, p.nombre, p.precio, p.codigo, p.descripcion, p.cantidad, 
          m.nombre AS marca, c.nombre AS categoria, d.porcentaje AS descuento
          FROM producto p
          LEFT JOIN marca m ON p.marca_idmarca = m.idmarca
          LEFT JOIN categoria c ON p.categoria_idcategoria = c.idcategoria
          LEFT JOIN descuento d ON p.descuento_iddescuento = d.iddescuento";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Ferretería</title>
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</head>
<body>
<header>
    <ul>
        <li><a href="index.php">Inventario</a></li>
        <li><a href="ventas.php">Ventas</a></li>
        <li><a href="historial.php">Historial</a></li>
    </ul>
</header>

<main>
    <h1>Pestaña de Ventas</h1>
    <div class="container">
        <table id="productos" class="display table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Descuento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['idproducto'] ?></td>
                            <td><?= $row['nombre'] ?></td>
                            <td>$<?= number_format($row['precio'], 2) ?></td>
                            <td><?= $row['codigo'] ?></td>
                            <td><?= $row['descripcion'] ?></td>
                            <td><?= $row['cantidad'] ?></td>
                            <td><?= $row['marca'] ?></td>
                            <td><?= $row['categoria'] ?></td>
                            <td><?= $row['descuento'] ?>%</td>
                            <td>
                                <button class="btn btn-success btn-vender" data-id="<?= $row['idproducto'] ?>">Vender</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No hay productos disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#productos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Botón de vender
    $('.btn-vender').on('click', function() {
        const idProducto = $(this).data('id');
        alert('Producto vendido con ID: ' + idProducto);
        // Aquí puedes añadir tu lógica para registrar la venta en la base de datos
    });
});
</script>
</body>
</html>
