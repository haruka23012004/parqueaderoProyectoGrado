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

// Consulta base
$query = "SELECT r.*, e.nombre_completo as admin_nombre 
          FROM reportes_carnet_perdido r 
          LEFT JOIN empleados e ON r.administrador_id = e.id";

// Aplicar filtros
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

// Agregar condiciones WHERE si existen
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Construir ORDER BY
switch ($orden) {
    case 'nombre_asc':
        $query .= " ORDER BY r.nombre_completo ASC";
        break;
    case 'nombre_desc':
        $query .= " ORDER BY r.nombre_completo DESC";
        break;
    case 'fecha_asc':
        $query .= " ORDER BY r.fecha_reporte ASC";
        break;
    case 'fecha_reporte_desc':
    default:
        $query .= " ORDER BY r.fecha_reporte DESC";
        break;
}

// Preparar y ejecutar consulta
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conn->query($query);
}

// Contadores para estadísticas
$total_reportes = $resultado->num_rows;

// Obtener contadores reales de todos los reportes
$query_contadores = "SELECT estado, COUNT(*) as total FROM reportes_carnet_perdido GROUP BY estado";
$result_contadores = $conn->query($query_contadores);
$contadores_estado = [
    'pendiente' => 0,
    'en_proceso' => 0,
    'resuelto' => 0
];

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
        
        .active-filters {
            background: #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
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

        <!-- Formulario Principal de Búsqueda -->
        <form method="GET" id="mainSearchForm">
            <!-- Sección de Búsqueda Avanzada -->
            <div class="search-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-2">
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
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-export me-2" onclick="exportarReportes()">
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
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="en_proceso" <?php echo $estado == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="resuelto" <?php echo $estado == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label text-dark"><strong>Tipo de Contacto</strong></label>
                            <select name="tipo_contacto" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="telefono" <?php echo $tipo_contacto == 'telefono' ? 'selected' : ''; ?>>Teléfono</option>
                                <option value="correo" <?php echo $tipo_contacto == 'correo' ? 'selected' : ''; ?>>Correo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label text-dark"><strong>Fecha Desde</strong></label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label text-dark"><strong>Fecha Hasta</strong></label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label text-dark"><strong>Ordenar por</strong></label>
                            <select name="orden" class="form-select">
                                <option value="fecha_reporte_desc" <?php echo $orden == 'fecha_reporte_desc' ? 'selected' : ''; ?>>Fecha (más reciente)</option>
                                <option value="fecha_asc" <?php echo $orden == 'fecha_asc' ? 'selected' : ''; ?>>Fecha (más antigua)</option>
                                <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                                <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre (Z-A)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 d-flex align-items-end justify-content-between">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Aplicar Filtros
                            </button>
                            
                            <!-- Filtros rápidos -->
                            <div class="btn-group">
                                <span class="badge bg-danger filter-badge me-2" onclick="setFilter('estado', 'pendiente')">
                                    Pendientes: <?php echo $contadores_estado['pendiente']; ?>
                                </span>
                                <span class="badge bg-warning filter-badge me-2" onclick="setFilter('estado', 'en_proceso')">
                                    En Proceso: <?php echo $contadores_estado['en_proceso']; ?>
                                </span>
                                <span class="badge bg-success filter-badge" onclick="setFilter('estado', 'resuelto')">
                                    Resueltos: <?php echo $contadores_estado['resuelto']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Filtros activos -->
        <?php if ($busqueda || $estado || $tipo_contacto || $fecha_desde || $fecha_hasta): ?>
        <div class="active-filters">
            <strong>Filtros activos:</strong>
            <?php if ($busqueda): ?>
                <span class="badge bg-info me-1">Búsqueda: <?php echo htmlspecialchars($busqueda); ?></span>
            <?php endif; ?>
            <?php if ($estado): ?>
                <span class="badge bg-primary me-1">Estado: <?php echo ucfirst($estado); ?></span>
            <?php endif; ?>
            <?php if ($tipo_contacto): ?>
                <span class="badge bg-secondary me-1">Contacto: <?php echo ucfirst($tipo_contacto); ?></span>
            <?php endif; ?>
            <?php if ($fecha_desde): ?>
                <span class="badge bg-warning me-1">Desde: <?php echo $fecha_desde; ?></span>
            <?php endif; ?>
            <?php if ($fecha_hasta): ?>
                <span class="badge bg-warning me-1">Hasta: <?php echo $fecha_hasta; ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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

        // Función para establecer filtros rápidos
        function setFilter(field, value) {
            document.querySelector(`[name="${field}"]`).value = value;
            document.getElementById('mainSearchForm').submit();
        }

        // Exportar reportes (función de ejemplo)
        function exportarReportes() {
            alert('Función de exportación - Puedes implementar la descarga de Excel aquí');
        }

        // Auto-submit al cambiar algunos filtros
        document.querySelectorAll('select[name="orden"]').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('mainSearchForm').submit();
            });
        });
    </script>
</body>
</html>