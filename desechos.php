<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de conexión a la base de datos
require_once 'db.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    // Si no tiene permiso, redirigir o mostrar error
    header('Location: login.php'); // o usar index.php, login.php, etc.
    exit;
}

// Crear la instancia de la base de datos
$db = new Database();
$conn = $db->conn;
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=800, user-scalable=yes">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desechos - Ferretería</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style2.css">

    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Scripts de DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        #tableContainer {
            width: 93%;
            margin: 2% auto 0;
            padding: 10px;
            background-color: #f9f9f9;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        #desechosTable {
            width: 100%;
            border-collapse: collapse;
        }

        #desechosTable th {
            background-color: #28a745;
            color: white;
            padding: 10px;
            text-align: center;
        }

        #desechosTable td {
            padding: 8px;
            text-align: center;
        }

        #desechosTable tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_filter input {
            width: auto !important;
            padding: 5px !important;
            border: 1px solid #ccc !important;
        }
    </style>


</head>
<body>

<header style="display: flex; justify-content: center; align-items: center; padding: 10px; color: white; position: relative;">
<nav>
    <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 20px;">
        <li><a href="index.php" style="color: white; text-decoration: none;">
            <i class="fas fa-box"></i> Inventario
        </a></li>
        <li><a href="ventas.php" style="color: white; text-decoration: none;">
            <i class="fas fa-money-bill-wave"></i> Ventas
        </a></li>
        <li><a href="historial.php" style="color: white; text-decoration: none;">
            <i class="fas fa-file-alt"></i> Historial
        </a></li>
        <li><a href="bodega.php" style="color: white; text-decoration: none;">
            <i class="fas fa-chart-line"></i> Gestión
        </a></li>
        <li><a href="reparto.php" style="color: white; text-decoration: none;">
            <i class="fas fa-truck"></i> Reparto
        </a></li>
        <li><a href="desechos.php" style="color: white; text-decoration: none;">
            <i class="fas fa-trash"></i> Desechos
        </a></li>
        <li><a href="usuario.php" style="color: white; text-decoration: none;">
            <i class="fas fa-user"></i> Usuarios
        </a></li>
    </ul>
</nav>

    <div class="logout-container">
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Cerrar Sesión</button>
        </form>
    </div>
</header>

<div class="container">
    <h2>Productos con Baja Rotación</h2>
    
    <table id="desechosTable" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Stock</th>
                <th>Precio</th>
                <th>Cantidad Vendida</th>
                <th>Días Sin Vender</th>
                <th>Frecuencia de Venta</th>
            </tr>
        </thead>
        <tbody>
            <?php
            require_once 'db.php'; // Conexión a la base de datos
            $database = new Database();
            $conn = $database->conn;

            $query = "SELECT p.idproducto, p.nombre, p.cantidad, p.precio, 
                             IFNULL(SUM(vd.cantidad), 0) AS cantidad_vendida, 
                             DATEDIFF(NOW(), MAX(v.fecha)) AS dias_sin_vender,
                             IFNULL(ROUND(DATEDIFF(MAX(v.fecha), MIN(v.fecha)) / COUNT(DISTINCT v.fecha)), 'Venta sin existencia') AS frecuencia_venta
                      FROM producto p
                      LEFT JOIN venta_detalle vd ON p.idproducto = vd.producto_idproducto
                      LEFT JOIN venta v ON vd.venta_idventa = v.idventa
                      WHERE p.visible = 1
                      GROUP BY p.idproducto
                      HAVING cantidad_vendida <= 2 OR cantidad_vendida IS NULL
                      ORDER BY cantidad_vendida ASC, dias_sin_vender DESC";

            $result = $conn->query($query);

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['idproducto']}</td>
                        <td>{$row['nombre']}</td>
                        <td>{$row['cantidad']}</td>
                        <td>\${$row['precio']}</td>
                        <td>{$row['cantidad_vendida']}</td>
                        <td>{$row['dias_sin_vender']}</td>
                        <td>{$row['frecuencia_venta']}</td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#desechosTable').DataTable();
    });
</script>

</body>
</html>