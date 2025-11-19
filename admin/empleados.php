<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cambiar_estado'])) {
        $empleado_id = intval($_POST['empleado_id']);
        $nuevo_estado = $_POST['nuevo_estado'];
        $razon = trim($_POST['razon'] ?? '');
        $fecha_inicio_suspension = $_POST['fecha_inicio_suspension'] ?? null;
        $fecha_fin_suspension = $_POST['fecha_fin_suspension'] ?? null;
        
        try {
            // Preparar la consulta según el estado
            if ($nuevo_estado === 'suspendido') {
                // Validar fechas para suspensión
                if (empty($fecha_inicio_suspension) || empty($fecha_fin_suspension)) {
                    throw new Exception("Para suspender debe especificar fechas de inicio y fin");
                }
                
                if (strtotime($fecha_fin_suspension) <= strtotime($fecha_inicio_suspension)) {
                    throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio");
                }
                
                $query = "UPDATE empleados SET estado = ?, razon_estado = ?, 
                         fecha_inicio_suspension = ?, fecha_fin_suspension = ?, 
                         fecha_cambio_estado = NOW() 
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $nuevo_estado, $razon, $fecha_inicio_suspension, $fecha_fin_suspension, $empleado_id);
            } else {
                $query = "UPDATE empleados SET estado = ?, razon_estado = ?, 
                         fecha_inicio_suspension = NULL, fecha_fin_suspension = NULL,
                         fecha_cambio_estado = NOW() 
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $nuevo_estado, $razon, $empleado_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "Estado del empleado actualizado correctamente";
            } else {
                throw new Exception("Error al actualizar el estado: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $_SESSION['mensaje_error'] = $e->getMessage();
        }
        
        header('Location: empleados.php');
        exit();
    }
}

// Obtener lista de empleados
$query = "SELECT 
            e.id,
            e.nombre_completo,
            e.cedula,
            e.email,
            e.usuario_login,
            e.estado,
            e.razon_estado,
            e.fecha_inicio_suspension,
            e.fecha_fin_suspension,
            e.fecha_cambio_estado,
            r.nombre as rol_nombre,
            r.descripcion as rol_descripcion
          FROM empleados e
          JOIN roles_sistema r ON e.rol_id = r.id
          ORDER BY e.nombre_completo ASC";

$result = $conn->query($query);
$empleados = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $empleados[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .badge-estado {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        .user-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        .action-buttons {
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        .action-buttons:hover {
            opacity: 1;
        }
        .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .suspension-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            font-size: 0.85rem;
        }
        .razon-text {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Gestión de Empleados</h2>
            <a href="../paneles/administrador.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>

        <!-- Mensajes de éxito/error -->
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['mensaje_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>

        <!-- Resumen de estados -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Empleados</h5>
                        <p class="card-text display-6"><?= count($empleados) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Activos</h5>
                        <p class="card-text display-6">
                            <?= count(array_filter($empleados, fn($e) => $e['estado'] === 'activo')) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h5 class="card-title">Suspendidos</h5>
                        <p class="card-text display-6">
                            <?= count(array_filter($empleados, fn($e) => $e['estado'] === 'suspendido')) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Inactivos</h5>
                        <p class="card-text display-6">
                            <?= count(array_filter($empleados, fn($e) => $e['estado'] === 'inactivo')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($empleados) > 0): ?>
            <!-- Vista de Tabla -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Lista de Empleados
                        <span class="badge bg-primary ms-2"><?= count($empleados) ?> registros</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Información</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empleados as $empleado): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-img bg-light d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($empleado['nombre_completo']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= $empleado['usuario_login'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Cédula:</strong> <?= $empleado['cedula'] ?>
                                                <br>
                                                <strong>Email:</strong> <?= $empleado['email'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $empleado['rol_nombre'])) ?></span>
                                            <br>
                                            <small class="text-muted"><?= $empleado['rol_descripcion'] ?></small>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                <?= $empleado['estado'] === 'activo' ? 'bg-success' : 
                                                   ($empleado['estado'] === 'suspendido' ? 'bg-warning text-dark' : 'bg-secondary') ?> 
                                                badge-estado">
                                                <?= ucfirst($empleado['estado']) ?>
                                            </span>
                                            
                                            <?php if (!empty($empleado['razon_estado'])): ?>
                                                <br>
                                                <small class="razon-text"><?= htmlspecialchars($empleado['razon_estado']) ?></small>
                                            <?php endif; ?>
                                            
                                            <?php if ($empleado['estado'] === 'suspendido' && $empleado['fecha_fin_suspension']): ?>
                                                <div class="suspension-info mt-1">
                                                    <small>
                                                        <strong>Suspensión:</strong><br>
                                                        <?= date('d/m/Y', strtotime($empleado['fecha_inicio_suspension'])) ?> 
                                                        al 
                                                        <?= date('d/m/Y', strtotime($empleado['fecha_fin_suspension'])) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons d-flex gap-1">
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning btn-action" 
                                                        title="Cambiar estado"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cambiarEstadoModal"
                                                        data-empleado-id="<?= $empleado['id'] ?>"
                                                        data-empleado-nombre="<?= htmlspecialchars($empleado['nombre_completo']) ?>"
                                                        data-empleado-estado="<?= $empleado['estado'] ?>">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay empleados registrados</h4>
                <p class="text-muted">Registre el primer empleado en el sistema.</p>
                <a href="../registro_empleados.php" class="btn btn-primary mt-2">
                    <i class="fas fa-user-plus me-1"></i> Registrar Primer Empleado
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-labelledby="cambiarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cambiarEstadoModalLabel">Cambiar Estado del Empleado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="empleado_id" id="empleado_id">
                        <input type="hidden" name="cambiar_estado" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Empleado:</label>
                            <p class="form-control-plaintext fw-bold" id="empleado_nombre"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado:</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="suspendido">Suspendido</option>
                            </select>
                        </div>
                        
                        <!-- Campos para suspensión (se muestran solo cuando se selecciona "suspendido") -->
                        <div id="campos_suspension" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicio_suspension" class="form-label">Fecha Inicio Suspensión:</label>
                                    <input type="date" class="form-control" id="fecha_inicio_suspension" name="fecha_inicio_suspension">
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_fin_suspension" class="form-label">Fecha Fin Suspensión:</label>
                                    <input type="date" class="form-control" id="fecha_fin_suspension" name="fecha_fin_suspension">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="razon" class="form-label">Razón del Cambio:</label>
                            <textarea class="form-control" id="razon" name="razon" rows="3" 
                                      placeholder="Explique la razón del cambio de estado..." required></textarea>
                            <div class="form-text">
                                <strong>Activo:</strong> El empleado puede acceder al sistema normalmente.<br>
                                <strong>Inactivo:</strong> El empleado no puede acceder hasta que se reactive manualmente.<br>
                                <strong>Suspendido:</strong> El empleado no puede acceder hasta la fecha de fin de suspensión.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de cambio de estado
        const cambiarEstadoModal = document.getElementById('cambiarEstadoModal');
        cambiarEstadoModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const empleadoId = button.getAttribute('data-empleado-id');
            const empleadoNombre = button.getAttribute('data-empleado-nombre');
            const empleadoEstado = button.getAttribute('data-empleado-estado');
            
            document.getElementById('empleado_id').value = empleadoId;
            document.getElementById('empleado_nombre').textContent = empleadoNombre;
            document.getElementById('nuevo_estado').value = empleadoEstado;
            
            // Mostrar/ocultar campos de suspensión
            toggleCamposSuspension();
        });

        // Mostrar/ocultar campos de suspensión según el estado seleccionado
        document.getElementById('nuevo_estado').addEventListener('change', toggleCamposSuspension);

        function toggleCamposSuspension() {
            const estado = document.getElementById('nuevo_estado').value;
            const camposSuspension = document.getElementById('campos_suspension');
            
            if (estado === 'suspendido') {
                camposSuspension.style.display = 'block';
                
                // Establecer fechas por defecto
                const hoy = new Date().toISOString().split('T')[0];
                const unaSemanaDespues = new Date();
                unaSemanaDespues.setDate(unaSemanaDespues.getDate() + 7);
                const fechaFin = unaSemanaDespues.toISOString().split('T')[0];
                
                document.getElementById('fecha_inicio_suspension').value = hoy;
                document.getElementById('fecha_fin_suspension').value = fechaFin;
            } else {
                camposSuspension.style.display = 'none';
            }
        }

        // Validar fechas antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const estado = document.getElementById('nuevo_estado').value;
            
            if (estado === 'suspendido') {
                const fechaInicio = document.getElementById('fecha_inicio_suspension').value;
                const fechaFin = document.getElementById('fecha_fin_suspension').value;
                
                if (!fechaInicio || !fechaFin) {
                    e.preventDefault();
                    alert('Para suspender debe especificar ambas fechas');
                    return;
                }
                
                if (new Date(fechaFin) <= new Date(fechaInicio)) {
                    e.preventDefault();
                    alert('La fecha de fin debe ser posterior a la fecha de inicio');
                    return;
                }
            }
        });
    </script>
</body>
</html>