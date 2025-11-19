<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /ParqueaderoProyectoGrado/paneles/administrador.php');
    exit();
}

// Procesar eliminación de usuario
if (isset($_GET['eliminar'])) {
    $usuario_id = intval($_GET['eliminar']);
    
    // Verificar que el usuario existe y está aprobado
    $check_query = "SELECT * FROM usuarios_parqueadero WHERE id = ? AND estado = 'aprobado'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $usuario_id);
    $check_stmt->execute();
    $usuario = $check_stmt->get_result()->fetch_assoc();
    
    if ($usuario) {
        try {
            // Iniciar transacción
            $conn->begin_transaction();
            
            // 1. Eliminar vehículos asociados
            $delete_vehiculos = "DELETE FROM vehiculos WHERE usuario_id = ?";
            $stmt_vehiculos = $conn->prepare($delete_vehiculos);
            $stmt_vehiculos->bind_param("i", $usuario_id);
            $stmt_vehiculos->execute();
            
            // 2. Eliminar registros de acceso
            $delete_accesos = "DELETE FROM registros_acceso WHERE usuario_id = ?";
            $stmt_accesos = $conn->prepare($delete_accesos);
            $stmt_accesos->bind_param("i", $usuario_id);
            $stmt_accesos->execute();
            
            // 3. Eliminar código QR si existe
            if (!empty($usuario['qr_code']) && file_exists("../" . $usuario['qr_code'])) {
                unlink("../" . $usuario['qr_code']);
            }
            
            // 4. Eliminar foto de usuario si existe
            if (!empty($usuario['foto_usuario']) && file_exists("../" . $usuario['foto_usuario'])) {
                unlink("../" . $usuario['foto_usuario']);
            }
            
            // 5. Eliminar usuario
            $delete_usuario = "DELETE FROM usuarios_parqueadero WHERE id = ?";
            $stmt_usuario = $conn->prepare($delete_usuario);
            $stmt_usuario->bind_param("i", $usuario_id);
            $stmt_usuario->execute();
            
            // Confirmar transacción
            $conn->commit();
            
            header('Location: usuarios_aprobados.php?msg=Usuario eliminado correctamente');
            exit();
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();
            header('Location: usuarios_aprobados.php?error=Error al eliminar usuario: ' . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header('Location: usuarios_aprobados.php?error=Usuario no encontrado');
        exit();
    }
}

// Construir consulta base con filtros
$where_conditions = ["u.estado = 'aprobado'"];
$params = [];
$types = "";

// Aplicar filtros si existen
if (isset($_GET['busqueda']) && !empty(trim($_GET['busqueda']))) {
    $busqueda = "%" . trim($_GET['busqueda']) . "%";
    $where_conditions[] = "(u.nombre_completo LIKE ? OR u.cedula LIKE ? OR v.placa LIKE ?)";
    $params = array_merge($params, [$busqueda, $busqueda, $busqueda]);
    $types .= "sss";
}

if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
    $where_conditions[] = "u.tipo = ?";
    $params[] = $_GET['tipo'];
    $types .= "s";
}

// Construir consulta
$query = "SELECT 
            u.*, 
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo,
            e.nombre_completo as nombre_aprobador
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          LEFT JOIN empleados e ON u.aprobado_por = e.id
          WHERE " . implode(" AND ", $where_conditions);

// Añadir ordenamiento
if (isset($_GET['orden'])) {
    switch ($_GET['orden']) {
        case 'fecha_antigua':
            $query .= " ORDER BY u.fecha_aprobacion ASC";
            break;
        case 'nombre':
            $query .= " ORDER BY u.nombre_completo ASC";
            break;
        default:
            $query .= " ORDER BY u.fecha_aprobacion DESC";
            break;
    }
} else {
    $query .= " ORDER BY u.fecha_aprobacion DESC";
}

// Preparar y ejecutar consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Obtener parámetros actuales para mantenerlos en formulario
$busqueda_actual = isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '';
$tipo_actual = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$orden_actual = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_reciente';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Aprobados - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .qr-img {
            max-width: 100px;
            height: auto;
        }
        .badge-estado {
            font-size: 0.85rem;
        }
        .user-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
        }
        .action-buttons {
            opacity: 0.8;
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
        .clear-filters {
            margin-top: 10px;
        }
        .btn-view {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-view:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Usuarios Aprobados</h2>
            <a href="solicitudes_pendientes.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Solicitudes
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros y búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   placeholder="Nombre, cédula o placa" value="<?= $busqueda_actual ?>">
                            <?php if (!empty($busqueda_actual)): ?>
                                <button type="button" class="btn btn-outline-secondary" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo de Usuario</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <option value="estudiante" <?= $tipo_actual == 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                            <option value="profesor" <?= $tipo_actual == 'profesor' ? 'selected' : '' ?>>Profesor</option>
                            <option value="administrativo" <?= $tipo_actual == 'administrativo' ? 'selected' : '' ?>>Administrativo</option>
                            <option value="visitante" <?= $tipo_actual == 'visitante' ? 'selected' : '' ?>>Visitante</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="orden" class="form-label">Ordenar por</label>
                        <select class="form-select" id="orden" name="orden">
                            <option value="fecha_reciente" <?= $orden_actual == 'fecha_reciente' ? 'selected' : '' ?>>Fecha más reciente</option>
                            <option value="fecha_antigua" <?= $orden_actual == 'fecha_antigua' ? 'selected' : '' ?>>Fecha más antigua</option>
                            <option value="nombre" <?= $orden_actual == 'nombre' ? 'selected' : '' ?>>Nombre A-Z</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                    </div>
                </form>
                
                <?php if (!empty($busqueda_actual) || !empty($tipo_actual) || $orden_actual != 'fecha_reciente'): ?>
                    <div class="clear-filters">
                        <a href="usuarios_aprobados.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                        <span class="ms-2 text-muted">
                            <?php
                            $filtros_activos = [];
                            if (!empty($busqueda_actual)) $filtros_activos[] = "Búsqueda: \"$busqueda_actual\"";
                            if (!empty($tipo_actual)) $filtros_activos[] = "Tipo: " . ucfirst($tipo_actual);
                            if ($orden_actual != 'fecha_reciente') {
                                $orden_texto = [
                                    'fecha_antigua' => 'Fecha más antigua',
                                    'nombre' => 'Nombre A-Z'
                                ];
                                $filtros_activos[] = "Orden: " . $orden_texto[$orden_actual];
                            }
                            
                            if (!empty($filtros_activos)) {
                                echo "Filtros activos: " . implode(", ", $filtros_activos);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($usuarios) > 0): ?>
            <div class="row">
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 card-hover">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-success badge-estado">Aprobado</span>
                                <div class="action-buttons d-flex">
                                    <!-- BOTÓN VER -->
                                    <a href="ver_usuario.php?id=<?= $usuario['id'] ?>" 
                                       class="btn btn-sm btn-view btn-action me-1" 
                                       title="Ver usuario">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- BOTÓN EDITAR -->
                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" 
                                       class="btn btn-sm btn-warning btn-action me-1" 
                                       title="Editar usuario">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- BOTÓN ELIMINAR -->
                                    <button type="button" class="btn btn-sm btn-danger btn-action" 
                                            title="Eliminar usuario" data-bs-toggle="modal" 
                                            data-bs-target="#confirmDeleteModal" 
                                            data-usuario-id="<?= $usuario['id'] ?>"
                                            data-usuario-nombre="<?= htmlspecialchars($usuario['nombre_completo']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($usuario['foto_usuario']): ?>
                                        <img src="../<?= $usuario['foto_usuario'] ?>" alt="Foto usuario" class="user-img me-3">
                                    <?php else: ?>
                                        <div class="user-img bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($usuario['nombre_completo']) ?></h5>
                                        <p class="text-muted mb-0"><?= ucfirst($usuario['tipo']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-id-card me-1"></i> Información Personal</h6>
                                    <p class="mb-1"><strong>Cédula:</strong> <?= htmlspecialchars($usuario['cedula']) ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                                    <?php if ($usuario['codigo_universitario']): ?>
                                        <p class="mb-1"><strong>Código:</strong> <?= $usuario['codigo_universitario'] ?></p>
                                    <?php endif; ?>
                                    <?php if ($usuario['facultad']): ?>
                                        <p class="mb-1"><strong>Facultad:</strong> <?= $usuario['facultad'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($usuario['tipo_vehiculo']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="fas fa-car me-1"></i> Vehículo</h6>
                                        <p class="mb-1"><strong>Tipo:</strong> <?= ucfirst($usuario['tipo_vehiculo']) ?></p>
                                        <?php if ($usuario['placa']): ?>
                                            <p class="mb-1"><strong>Placa:</strong> <?= $usuario['placa'] ?></p>
                                        <?php endif; ?>
                                        <?php if ($usuario['marca']): ?>
                                            <p class="mb-1"><strong>Marca/Modelo:</strong> <?= $usuario['marca'] ?></p>
                                        <?php endif; ?>
                                        <?php if ($usuario['color']): ?>
                                            <p class="mb-1"><strong>Color:</strong> <?= $usuario['color'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-calendar-check me-1"></i> Información de Aprobación</h6>
                                    <p class="mb-1"><strong>Fecha aprobación:</strong> 
                                        <?= date('d/m/Y H:i', strtotime($usuario['fecha_aprobacion'])) ?>
                                    </p>
                                    <?php if ($usuario['nombre_aprobador']): ?>
                                        <p class="mb-1"><strong>Aprobado por:</strong> <?= $usuario['nombre_aprobador'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($usuario['qr_code']): ?>
                                    <div class="text-center mt-3">
                                        <p class="mb-1"><strong>Código QR:</strong></p>
                                        <img src="../<?= $usuario['qr_code'] ?>" alt="Código QR" class="qr-img img-thumbnail">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>
                                    </span>
                                    <!-- Enlace rápido para ver usuario -->
                                    <a href="ver_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i> Ver completo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginación (si es necesaria en el futuro) -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Anterior</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Siguiente</a>
                    </li>
                </ul>
            </nav>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay usuarios aprobados</h4>
                <p class="text-muted">
                    <?php if (!empty($busqueda_actual) || !empty($tipo_actual)): ?>
                        No se encontraron resultados con los filtros aplicados.
                    <?php else: ?>
                        Cuando apruebes solicitudes, aparecerán aquí.
                    <?php endif; ?>
                </p>
                <?php if (!empty($busqueda_actual) || !empty($tipo_actual)): ?>
                    <a href="usuarios_aprobados.php" class="btn btn-primary mt-2">
                        <i class="fas fa-times me-1"></i> Limpiar filtros
                    </a>
                <?php else: ?>
                    <a href="solicitudes_pendientes.php" class="btn btn-primary mt-2">
                        <i class="fas fa-list me-1"></i> Ver Solicitudes Pendientes
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar al usuario <strong id="usuarioNombre"></strong>?</p>
                    <p class="text-danger"><strong>Advertencia:</strong> Esta acción no se puede deshacer y se eliminarán todos los datos asociados al usuario, incluyendo vehículos y registros de acceso.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a id="confirmDeleteButton" href="#" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de eliminación
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const usuarioId = button.getAttribute('data-usuario-id');
            const usuarioNombre = button.getAttribute('data-usuario-nombre');
            
            const modalTitle = confirmDeleteModal.querySelector('.modal-title');
            const usuarioNombreElement = confirmDeleteModal.querySelector('#usuarioNombre');
            const confirmDeleteButton = confirmDeleteModal.querySelector('#confirmDeleteButton');
            
            usuarioNombreElement.textContent = usuarioNombre;
            confirmDeleteButton.href = `usuarios_aprobados.php?eliminar=${usuarioId}`;
        });

        // Limpiar búsqueda
        document.getElementById('clearSearch')?.addEventListener('click', function() {
            document.getElementById('busqueda').value = '';
            document.getElementById('filterForm').submit();
        });

        // Enviar formulario automáticamente cuando cambien algunos filtros
        document.getElementById('orden').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        // Opcional: Enviar formulario cuando se borre la búsqueda con tecla Escape
        document.getElementById('busqueda').addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.blur();
                document.getElementById('filterForm').submit();
            }
        });
    </script>
</body>
</html>