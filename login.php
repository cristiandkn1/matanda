<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    $db = new Database();

    // Consulta preparada incluyendo el rol
    $sql = "SELECT idrol, nombre, contraseña, correo, rol FROM rol WHERE correo = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['contraseña'])) {
            // Guardar datos en la sesión
            $_SESSION['user_id'] = $user['idrol'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['rol']; // ← importante que sea "rol"

            // Redirección según rol
            if ($user['rol'] === 'repartidor') {
                header("Location: repartoRepartidor.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Correo no encontrado";
    }

    $stmt->close();
    $db->conn->close();
}

// Redirigir si ya está logueado
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'repartidor') {
        header("Location: repartoRepartidor.php");
    } else {
        header("Location: index.php");
    }
    exit();
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
       /* Fondo con imagen personalizada */
/* Fondo con imagen personalizada */
body {
    position: relative;
    background: url('img/fondo.jpg') no-repeat center center fixed;
    background-size: cover;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Capa oscura sobre la imagen de fondo */
body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4); /* Oscurecer con una capa semitransparente */
    z-index: -1; /* Colocar detrás del contenido */
}

.login-container {
    background: rgba(255, 255, 255, 0.19); /* Blanco más brillante con transparencia */
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
    animation: fadeIn 1s;
    backdrop-filter: blur(10px) brightness(1.2); /* Efecto de desenfoque y brillo */
}

/* Animación de aparición */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Estilo para el botón de ingresar */
.btn-orange {
    background-color: #FF7F50;
    border: none;
    color: white;
    font-size: 16px;
    transition: 0.3s ease;
}

.btn-orange:hover {
    background-color: #FF6347;
}

/* Inputs blancos con bordes redondeados */
.form-control {
    border-radius: 8px;
    background: white;
}

    </style>
</head>
<body>

    <div class="login-container">
        <h3 class="text-center">Iniciar Sesión</h3>

        <?php if (isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo" required>
                
                
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-orange w-100">Ingresar</button>
        </form>
    </div>

</body>
</html>
