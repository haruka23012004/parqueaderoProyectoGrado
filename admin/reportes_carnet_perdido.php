<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /parqueaderoProyectoGrado/paneles/administrador.php');
    exit();
}

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $reporte_id = (int)$_POST['reporte_id'];
    $nuevo_estado = $_POST['estado'];
    $respuesta = trim($_POST['respuesta']);
    $admin_id = $_SESSION['usuario_id'];
    
    $query = "UPDATE reportes_carnet_perdido 
              SET estado = ?, respuesta_admin = ?, fecha_respuesta = NOW(), administrador_id = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $nuevo_estado, $respuesta, $admin_id, $reporte_id);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Estado actualizado correctamente';
    } else {
        $_SESSION['error'] = 'Error al actualizar el estado';
    }
    
    header('Location: reportes_carnet_perdido.php');
    exit();
}

// Parámetros de búsqueda
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$tipo_contacto = $_GET['tipo_contacto'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_reporte_desc';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($busqueda)) {
    $where_conditions[] = "(r.nombre_completo LIKE ? OR r.cedula LIKE ? OR r.contacto LIKE ? OR r.descripcion LIKE ?)";
    $params = array_merge($params, ["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"]);
    $types .= 'ssss';
}

if (!empty($estado)) {
    $where_conditions[] = "r.estado = ?";
    $params[] = $estado;
    $types .= 's';
}

if (!empty($tipo_contacto)) {
    $where_conditions[] = "r.tipo_contacto = ?";
    $params[] = $tipo_contacto;
    $types .= 's';
}

if (!empty($fecha_desde)) {
    $where_conditions[] = "DATE(r.fecha_reporte) >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "DATE(r.fecha_reporte) <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

// Construir ORDER BY
$order_by = '';
switch ($orden) {
    case 'nombre_asc':
        $order_by = 'r.nombre_completo ASC';
        break;
    case 'nombre_desc':
        $order_by = 'r.nombre_completo DESC';
        break;
    case 'fecha_asc':
        $order_by = 'r.fecha_reporte ASC';
        break;
    case 'fecha_reporte_desc':
    default:
        $order_by = 'r.fecha_reporte DESC';
        break;
}

// Consulta base
$query = "SELECT r.*, e.nombre_completo as admin_nombre 
          FROM reportes_carnet_perdido r 
          LEFT JOIN empleados e ON r.administrador_id = e.id";

// Agregar condiciones WHERE
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Agregar ORDER BY
$query .= " ORDER BY $order_by";

// Preparar y ejecutar consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Contadores para estadísticas
$total_reportes = $resultado->num_rows;
$contadores_estado = [
    'pendiente' => 0,
    'en_proceso' => 0,
    'resuelto' => 0
];

// Obtener contadores reales
$query_contadores = "SELECT estado, COUNT(*) as total FROM reportes_carnet_perdido GROUP BY estado";
$result_contadores = $conn->query($query_contadores);
while ($row = $result_contadores->fetch_assoc()) {
    $contadores_estado[$row['estado']] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Carnet Perdido - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-pendiente { background-color: #dc3545; }
        .badge-en_proceso { background-color: #ffc107; color: #000; }
        .badge-resuelto { background-color: #198754; }
        
        .reporte-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .reporte-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .reporte-pendiente { border-left-color: #dc3545; }
        .reporte-en_proceso { border-left-color: #ffc107; }
        .reporte-resuelto { border-left-color: #198754; }
        
        .contacto-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
        
        .search-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            color: white;
        }
        
        .stats-card {
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .filter-badge {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-badge:hover {
            transform: scale(1.05);
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            color: white;
        }
        
        .search-input {
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Header con estadísticas -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-id-card me-2"></i>
                Reportes de Carnet Perdido
            </h2>
            <div class="d-flex gap-2">
                <span class="badge bg-primary fs-6">
                    Total: <?php echo $total_reportes; ?>
                </span>
                <span class="badge bg-danger fs-6">
                    Pendientes: <?php echo $contadores_estado['pendiente']; ?>
                </span>
            </div>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Sección de Búsqueda Avanzada -->
        <div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <input type="text" 
                                   name="busqueda" 
                                   class="form-control search-input" 
                                   placeholder="Buscar por nombre, cédula, contacto o descripción..."
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                        </div>
                        <button type="submit" class="btn btn-light">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-export me-2" onclick="exportarReportes()">
                        <i class="fas fa-download me-1"></i> Exportar
                    </button>
                    <a href="reportes_carnet_perdido.php" class="btn btn-clear">
                        <i class="fas fa-times me-1"></i> Limpiar
                    </a>
                </div>
            </div>

            <!-- Filtros Avanzados -->
            <div class="filter-section mt-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-dark"><strong>Estado</strong></label>
                        <select name="estado" class="form-select" onchange="this.form.submit()" form="filtrosForm">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_proceso" <?php echo $estado == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="resuelto" <?php echo $estado == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-dark"><strong>Tipo de Contacto</strong></label>
                        <select name="tipo_contacto" class="form-select" onchange="this.form.submit()" form="filtrosForm">
                            <option value="">Todos los tipos</option>
                            <option value="telefono" <?php echo $tipo_contacto == 'telefono' ? 'selected' : ''; ?>>Teléfono</option>
                            <option value="correo" <?php echo $tipo_contacto == 'correo' ? 'selected' : ''; ?>>Correo</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-dark"><strong>Fecha Desde</strong></label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>" form="filtrosForm">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-dark"><strong>Fecha Hasta</strong></label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>" form="filtrosForm">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-dark"><strong>Ordenar por</strong></label>
                        <select name="orden" class="form-select" onchange="this.form.submit()" form="filtrosForm">
                            <option value="fecha_reporte_desc" <?php echo $orden == 'fecha_reporte_desc' ? 'selected' : ''; ?>>Fecha (más reciente)</option>
                            <option value="fecha_asc" <?php echo $orden == 'fecha_asc' ? 'selected' : ''; ?>>Fecha (más antigua)</option>
                            <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                            <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre (Z-A)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-9 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2" form="filtrosForm">
                            <i class="fas fa-filter me-1"></i> Aplicar Filtros
                        </button>
                        
                        <!-- Filtros rápidos -->
                        <div class="btn-group">
                            <span class="badge bg-danger filter-badge me-2" onclick="aplicarFiltroRapido('pendiente')">
                                Pendientes: <?php echo $contadores_estado['pendiente']; ?>
                            </span>
                            <span class="badge bg-warning filter-badge me-2" onclick="aplicarFiltroRapido('en_proceso')">
                                En Proceso: <?php echo $contadores_estado['en_proceso']; ?>
                            </span>
                            <span class="badge bg-success filter-badge" onclick="aplicarFiltroRapido('resuelto')">
                                Resueltos: <?php echo $contadores_estado['resuelto']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario oculto para filtros -->
        <form method="GET" id="filtrosForm" style="display: none;">
            <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <input type="hidden" name="estado" value="<?php echo htmlspecialchars($estado); ?>">
            <input type="hidden" name="tipo_contacto" value="<?php echo htmlspecialchars($tipo_contacto); ?>">
            <input type="hidden" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
            <input type="hidden" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
            <input type="hidden" name="orden" value="<?php echo htmlspecialchars($orden); ?>">
        </form>

        <!-- Resultados -->
        <div class="row">
            <div class="col-12">
                <?php if ($resultado->num_rows > 0): ?>
                    <div class="mb-3 text-muted">
                        Mostrando <?php echo $total_reportes; ?> reporte(s) encontrado(s)
                    </div>
                    
                    <?php while ($reporte = $resultado->fetch_assoc()): ?>
                        <div class="card reporte-card reporte-<?php echo $reporte['estado']; ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($reporte['nombre_completo']); ?>
                                            <span class="badge badge-<?php echo $reporte['estado']; ?>">
                                                <?php echo ucfirst($reporte['estado']); ?>
                                            </span>
                                        </h5>
                                        
                                        <p class="card-text">
                                            <strong>Cédula:</strong> <?php echo htmlspecialchars($reporte['cedula']); ?><br>
                                            <strong>Fecha Reporte:</strong> <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?>
                                        </p>
                                        
                                        <div class="contacto-info">
                                            <strong>Contacto:</strong> 
                                            <?php echo htmlspecialchars($reporte['contacto']); ?>
                                            <small class="text-muted">
                                                (<?php echo $reporte['tipo_contacto'] == 'telefono' ? 'Teléfono' : 'Correo'; ?>)
                                            </small>
                                        </div>
                                        
                                        <p class="card-text">
                                            <strong>Descripción:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                                        </p>
                                        
                                        <?php if (!empty($reporte['respuesta_admin'])): ?>
                                            <div class="alert alert-info mt-2">
                                                <strong>Respuesta del Administrador:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($reporte['respuesta_admin'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    Respondido por: <?php echo htmlspecialchars($reporte['admin_nombre'] ?? 'Sistema'); ?>
                                                    el <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_respuesta'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <form method="POST" class="mb-3">
                                            <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Actualizar Estado</strong></label>
                                                <select name="estado" class="form-select" required>
                                                    <option value="pendiente" <?php echo $reporte['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="en_proceso" <?php echo $reporte['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                                    <option value="resuelto" <?php echo $reporte['estado'] == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Respuesta/Comentarios</label>
                                                <textarea name="respuesta" class="form-control" rows="3" 
                                                          placeholder="Ingrese comentarios o instrucciones para el usuario..."><?php echo htmlspecialchars($reporte['respuesta_admin'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <button type="submit" name="actualizar_estado" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-1"></i> Actualizar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No se encontraron reportes</h4>
                        <p class="mb-0">No hay reportes que coincidan con los criterios de búsqueda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-expand textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });

        // Aplicar filtro rápido
        function aplicarFiltroRapido(estado) {
            document.querySelector('select[name="estado"]').value = estado;
            document.getElementById('filtrosForm').submit();
        }

        // Exportar reportes (función de ejemplo)
        function exportarReportes() {
            const params = new URLSearchParams({
                busqueda: '<?php echo $busqueda; ?>',
                estado: '<?php echo $estado; ?>',
                tipo_contacto: '<?php echo $tipo_contacto; ?>',
                fecha_desde: '<?php echo $fecha_desde; ?>',
                fecha_hasta: '<?php echo $fecha_hasta; ?>',
                exportar: 'excel'
            });
            
            window.location.href = 'exportar_reportes.php?' + params.toString();
        }

        // Aplicar filtros al cambiar fechas
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('filtrosForm').submit();
            });
        });
    </script>
</body>
</html>