<?php
session_start();
require_once 'db.php';

$database = new Database();
$conn = $database->conn;

// Validar si el usuario ha iniciado sesión y verificar su correo desde la tabla "rol"
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    // Si no tiene permiso, redirigir o mostrar error
    header('Location: login.php'); // o usar index.php, login.php, etc.
    exit;
}

$userId = $_SESSION['user_id'];

// Consulta desde la tabla ROL
$query = "SELECT correo FROM rol WHERE idrol = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario || $usuario['correo'] !== 'vega@gmail.com') {
    echo "<h2 style='color: red; text-align: center;'>Acceso denegado</h2>";
    exit();
}
// Obtener todos los usuarios
$usuarios = $conn->query("SELECT idrol, nombre, correo FROM rol");
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=800, user-scalable=yes">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style2.css">

    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" /><!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


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



<div class="text-center mb-3" style="margin-top: 50px;">
    <button class="btn btn-success" id="btnAgregarUsuario">
        <i class="fas fa-user-plus"></i> Agregar Usuario
    </button>
</div>




<div class="container mt-5" style="max-width: 1400px; margin: 0 auto;">
    <h2 class="text-center mb-4">Usuarios del sistema</h2>
    <div class="table-responsive">
        <table id="tablaUsuarios" class="table table-bordered table-striped">
            <thead class="table-dark text-center">
                <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Acciones</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php
                require_once 'db.php';
                $db = new Database();
                $conn = $db->conn;
                $result = $conn->query("SELECT * FROM rol");

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['idrol']}</td>
                        <td>{$row['nombre']}</td>
                        <td>{$row['correo']}</td>
                        <td>{$row['rol']}</td>

                        <td>
    <button class='btn btn-sm btn-primary btnEditar' 
    data-id='{$row['idrol']}'
    data-nombre='{$row['nombre']}'
    data-correo='{$row['correo']}'
    data-rol='{$row['rol']}'>
    <i class='fas fa-edit'></i> Editar
</button>

    
    <button class='btn btn-sm btn-danger btnEliminar' 
        data-id='{$row['idrol']}'>
        <i class='fas fa-trash-alt'></i> Eliminar
    </button>
</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>



<!-- Modal para agregar usuario -->
<div id="modalAgregar" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAgregarUsuario">
        <div class="modal-header">
          <h5 class="modal-title">Agregar Usuario</h5>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre:</label>
            <input type="text" class="form-control" name="nombre" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Rol:</label>
            <select class="form-select" name="rol" required>
                <option value="usuario">Usuario</option>
                <option value="repartidor">Repartidor</option>
            </select>
        </div>

          <div class="mb-3">
            <label class="form-label">Correo:</label>
            <input type="email" class="form-control" name="correo" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Contraseña:</label>
            <input type="password" class="form-control" name="password" required>
          </div>
        </div>
       
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Usuario
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
 
<!-- al final de tu HTML, antes de </body> -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditarUsuario">
        <div class="modal-header">
          <h5 class="modal-title">Editar Usuario</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editId" name="id">

          <div class="mb-3">
            <label class="form-label">Nombre:</label>
            <input type="text" class="form-control" id="editNombre" name="nombre" required>
          </div>
<div class="mb-3">
  <label class="form-label">Rol:</label>
  <select class="form-select" id="editRol" name="rol" required>
    <option value="usuario">Usuario</option>
    <option value="repartidor">Repartidor</option>
  </select>
</div>

          <div class="mb-3">
            <label class="form-label">Correo:</label>
            <input type="email" class="form-control" id="editCorreo" name="correo" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Contraseña:</label>
            <input type="password" class="form-control" id="editPassword" name="password" placeholder="(Opcional) Nueva contraseña">
          </div>
        </div>
    
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Guardar Cambios
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    $('#tablaUsuarios').DataTable({
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
    });

    $('.btnEditar').on('click', function () {
    $('#editId').val($(this).data('id'));
    $('#editNombre').val($(this).data('nombre'));
    $('#editCorreo').val($(this).data('correo'));
    $('#editPassword').val('');
    $('#editRol').val($(this).data('rol')); // Agregar esto
    var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
    modal.show();
});



    $('#formEditarUsuario').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('editar_usuario.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
    if (data.success) {
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: data.message,
            timer: 1000,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message
        });
    }
})

        .catch(err => alert('Error al editar usuario'));
    });
});

function cerrarModalEditar() {
    $('#modalEditar').modal('hide');
}
</script>
<script>
    // Manejar botón Eliminar
$(document).on('click', '.btnEliminar', function () {
    const id = $(this).data('id');

    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡Esta acción no se puede deshacer!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('eliminar_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar el usuario.'
                });
            });
        }
    });
});
</script>

<script>

// Abrir modal de agregar usuario
$('#btnAgregarUsuario').on('click', function () {
    var modal = new bootstrap.Modal(document.getElementById('modalAgregar'));
    modal.show();
});

// Guardar nuevo usuario
$('#formAgregarUsuario').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('agregar_usuario.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Usuario Agregado!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el usuario.'
        });
    });
});

</script>
<!-- Bootstrap 5 Bundle (incluye Popper para modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
