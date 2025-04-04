<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que esté logueado y sea repartidor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'repartidor') {
    header("Location: index.php");
    exit();
}

require_once 'db.php';
$db = new Database();
$conn = $db->conn;

// Definir el nombre del repartidor desde la sesión
$repartidorActual = $_SESSION['nombre'] ?? ''; // Asegura que no esté indefinido

// Consulta de repartos pendientes
$query = "
SELECT 
    r.idreparto, 
    r.idventa, 
    r.direccion, 
    r.telefono, 
    r.estado_pago, 
    vr.precio AS valor_reparto, 
    r.estado, 
    r.reparto_tomado_por
FROM reparto r
LEFT JOIN valores_reparto vr ON r.valor_reparto_id = vr.id
WHERE r.estado = 'Pendiente'
ORDER BY r.idreparto DESC
";

$result = $conn->query($query);

// Verificar si este repartidor ya tomó un reparto PENDIENTE
$repartoAsignado = null;
$check = $conn->prepare("SELECT idreparto FROM reparto WHERE reparto_tomado_por = ? AND estado = 'Pendiente'");
$check->bind_param("s", $repartidorActual);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    $repartoAsignado = intval($res->fetch_assoc()['idreparto']);
}



$check2 = $conn->prepare("SELECT idreparto, estado FROM reparto WHERE reparto_tomado_por = ?");
$check2->bind_param("s", $repartidorActual);
$check2->execute();
$res2 = $check2->get_result();


while ($r = $res2->fetch_assoc()) {
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repartos - Repartidor</title>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style2.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
<header class="bg-dark text-white p-3 d-flex justify-content-between align-items-center flex-wrap">
    <nav>
        <a href="repartoRepartidor.php" class="text-white text-decoration-none fw-bold">
            MODULO DE REPARTOS 🏍️
        </a>
    </nav>

    <form action="logout.php" method="POST" class="mt-2 mt-md-0 logout-form">
        <button type="submit" class="btn-logout-clean">Cerrar Sesión</button>
    </form>
</header>

<style>
.logout-form {
    background-color: transparent !important;
    padding: 0;
    margin: 0;
    border: none;
}

.btn-logout-clean {
    background-color: #dc3545; /* rojo Bootstrap */
    color: white;
    border: none;
    font-size: 0.875rem;
    padding: 0.35rem 0.75rem;
    border-radius: 4px;
    font-weight: 500;
}

.btn-logout-clean:hover {
    background-color: #c82333;
    color: white;
}
</style>






<style>
@media (max-width: 576px) {
    table#tablaRepartos td,
    table#tablaRepartos th {
        padding: 0.25rem !important; /* reduce el padding */
        font-size: 12px; /* reduce tamaño de fuente */
    }

    table#tablaRepartos .btn {
        padding: 0.25rem 0.4rem;
        font-size: 11px;
    }

    h2.text-center {
        font-size: 1.2rem;
    }
}
</style>
<div class="container mt-5 px-2 px-sm-3">
    <h2 class="text-center mb-4">Repartos asignados</h2>
    <div class="table-responsive"> <!-- scroll horizontal en móviles -->
        <table id="tablaRepartos" class="table table-bordered table-striped table-hover w-100">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID Reparto</th>
                    <th>ID Venta</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Estado Pago</th>
                    <th>Valor Reparto ID</th>
                    <th>Estado</th>
                    <th>Reparto Tomado Por</th>
                    <th>Acción</th>
                    <th>Finalizar</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['idreparto'] ?></td>
                    <td><?= $row['idventa'] ?></td>
                    <td><?= $row['direccion'] ?></td>
                    <td><?= $row['telefono'] ?></td>
                    <td><?= $row['estado_pago'] ?></td>
                    <td><?= number_format($row['valor_reparto'], 0, ',', '.') ?> CLP</td>
                    <td><?= $row['estado'] ?></td>
                    <td><?= $row['reparto_tomado_por'] ?? '—' ?></td>

                    <!-- Acciones -->
                    <td>
                        <?php if (empty($row['reparto_tomado_por'])): ?>
                            <?php if (!empty($repartoAsignado)): ?>
                                <button class="btn btn-sm btn-secondary" disabled>
                                    Ya tienes un reparto
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success btnTomarReparto" data-id="<?= $row['idreparto'] ?>">
                                    <i class="fas fa-hand-pointer"></i> Tomar Reparto
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Ya tomado</span>
                        <?php endif; ?>
                    </td>

                    <!-- Finalizar -->
                    <td>
                        <?php if (
                            isset($row['reparto_tomado_por'], $_SESSION['nombre']) &&
                            strcasecmp(trim($row['reparto_tomado_por']), trim($_SESSION['nombre'])) === 0 &&
                            $row['estado'] === 'Pendiente'
                        ): ?>
                            <button class="btn btn-primary btn-sm btnFinalizar"
                                    data-id="<?= $row['idreparto'] ?>"
                                    data-idventa="<?= $row['idventa'] ?>"
                                    data-pagado="<?= $row['estado_pago'] === 'Pagado' ? '1' : '0' ?>">
                                <i class="fas fa-check-circle"></i> Finalizar
                            </button>

                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>



<script>
$(document).ready(function () {
    $('#tablaRepartos').DataTable({
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
    });

    $('.btnTomarReparto').on('click', function () {
        const id = $(this).data('id');
        const nombre = <?= json_encode($_SESSION['nombre']) ?>;
        const rut = <?= json_encode($_SESSION['user_rut'] ?? 'Sin RUT') ?>;

        Swal.fire({
            title: '¿Estás seguro de tomar este reparto?',
            html: `El reparto será asignado a:<br><strong>${nombre}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, tomar reparto',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('tomar_reparto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Reparto tomado',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });
});
</script>


<script>
    $(document).ready(function () {
    $('.btnFinalizar').on('click', function () {
        const idReparto = $(this).data('id');
        const idVenta = $(this).data('idventa');
        const yaPagado = $(this).data('pagado') === 1 || $(this).data('pagado') === '1';

        $('#finalizarIdReparto').val(idReparto);
        $('#finalizarIdVenta').val(idVenta);

        if (yaPagado) {
            // Ocultar campos si ya está pagado
            $('#grupoMetodoPago').hide();
            $('#grupoMonto').hide();
            $('#grupoVoucher').hide();

            $('#metodo_pago').removeAttr('required').val('');
            $('#monto').removeAttr('required').val('');
            $('#voucher').val('').removeAttr('required');
        } else {
            // Mostrar los campos normalmente
            $('#grupoMetodoPago').show();
            $('#grupoMonto').show();
            $('#grupoVoucher').hide(); // por defecto oculto
            $('#metodo_pago').attr('required', true);
            $('#monto').attr('required', true);
        }

        const modal = new bootstrap.Modal(document.getElementById('modalFinalizarReparto'));
        modal.show();
    });

    $('#metodo_pago').on('change', function () {
        const val = $(this).val();
        if (val === '1' || val === '4') {
            $('#grupoVoucher').show();
            $('#voucher').attr('required', true);
        } else {
            $('#grupoVoucher').hide();
            $('#voucher').val('');
            $('#voucher').removeAttr('required');
        }
    });

    // Finalizar reparto
    $('#formFinalizarReparto').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    console.log([...formData.entries()]); // 👉 esto te mostrará los campos y sus valores

    fetch('finalizar_reparto.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.fire(data.success ? '¡Completado!' : 'Error', data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1500);
    });
});

    // Cancelar reparto
    $('#btnCancelarReparto').on('click', function () {
        const id = $('#finalizarIdReparto').val();
        Swal.fire({
            title: '¿Cancelar este reparto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'Volver'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('cancelar_reparto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire(data.success ? 'Cancelado' : 'Error', data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                });
            }
        });
    });
});

</script>


<div class="modal fade" id="modalFinalizarReparto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formFinalizarReparto" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Finalizar Reparto</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="finalizarIdReparto" name="idreparto">
        <input type="hidden" id="finalizarIdVenta" name="idventa">

        <!-- Agrupado para poder ocultar cuando ya está pagado -->
        <div class="mb-3" id="grupoMetodoPago">
          <label>Método de Pago</label>
          <select class="form-select" name="metodo_pago" id="metodo_pago">
            <option value="">Seleccione...</option>
            <option value="1">Débito</option>
            <option value="2">Efectivo</option>
            <option value="3">Transferencia</option>
            <option value="4">Crédito</option>
          </select>
        </div>

        <div class="mb-3" id="grupoVoucher" style="display:none;">
          <label>Código de Voucher</label>
          <input type="text" class="form-control" name="voucher" id="voucher">
        </div>

        <div class="mb-3" id="grupoMonto">
          <label>Cantidad Pagada</label>
          <input type="number" step="0.01" min="0" class="form-control" name="monto" id="monto">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-danger" id="btnCancelarReparto">
          Cancelar Reparto
        </button>
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>







<!-- Bootstrap 5 Bundle (incluye Popper para modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
