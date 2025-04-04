

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=800, user-scalable=yes">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Ferretería</title>

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
    <!-- Agrega esto en tu HTML si aún no tienes SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

<header style="display: flex; justify-content: center; align-items: center; padding: 10px;  color: white; position: relative;">
    <nav>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 20px;">
            <li><a href="index.php" style="color: white; text-decoration: none;">Inventario📦</a></li>
            <li><a href="ventas.php" style="color: white; text-decoration: none;">Ventas💸</a></li>
            <li><a href="historial.php" style="color: white; text-decoration: none;">Historial🗒️</a></li>
            <li><a href="bodega.php" style="color: white; text-decoration: none;">Gestion💹</a></li>
            <li><a href="reparto.php" style="color: white; text-decoration: none;">Reparto🚚</a></li>
            <li><a href="desechos.php" style="color: white; text-decoration: none;">Desechos🚮</a></li>
            <li><a href="peluqueria.php" style="color: white; text-decoration: none;">Peluqueria💈</a></li>
            <li><a href="usuario.php" style="color: white; text-decoration: none;">Usuarios👤</a></li>


        </ul>
    </nav>

    <!-- Contenedor para el botón de cerrar sesión -->
    <div class="logout-container">
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Cerrar Sesión</button>
        </form>
    </div>
</header>






</body>