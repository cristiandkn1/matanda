<?php
session_start();
session_destroy(); // Elimina la sesión
header("Location: login.php"); // Redirige a login.php después de cerrar sesión
exit();
?>
