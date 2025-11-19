<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
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

if (isset($_GET['tipo_vehiculo']) && !empty($_GET['tipo_vehiculo'])) {
    $where_conditions[] = "v.tipo = ?";
    $params[] = $_GET['tipo_vehiculo'];
    $types .= "s";
}

// Construir consulta
$query = "SELECT 
            u.id as usuario_id,
            u.nombre_completo,
            u.cedula,
            u.email,
            u.tipo as tipo_usuario,
            u.fecha_registro,
            u.fecha_aprobacion,
            v.id as vehiculo_id,
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo,
            v.foto_vehiculo
          FROM vehiculos v
          INNER JOIN usuarios_parqueadero u ON v.usuario_id = u.id
          WHERE " . implode(" AND ", $where_conditions);

// Añadir ordenamiento
if (isset($_GET['orden'])) {
    switch ($_GET['orden']) {
        case 'placa_asc':
            $query .= " ORDER BY v.placa ASC";
            break;
        case 'placa_desc':
            $query .= " ORDER BY v.placa DESC";
            break;
        case 'fecha_reciente':
            $query .= " ORDER BY u.fecha_registro DESC";
            break;
        case 'fecha_antigua':
            $query .= " ORDER BY u.fecha_registro ASC";
            break;
        case 'nombre_asc':
            $query .= " ORDER BY u.nombre_completo ASC";
            break;
        case 'nombre_desc':
            $query .= " ORDER BY u.nombre_completo DESC";
            break;
        default:
            $query .= " ORDER BY u.fecha_registro DESC";
            break;
    }
} else {
    $query .= " ORDER BY u.fecha_registro DESC";
}

// Preparar y ejecutar consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vehiculos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}

// Obtener tipos de vehículos únicos para el filtro
$query_tipos = "SELECT DISTINCT tipo FROM vehiculos ORDER BY tipo";
$result_tipos = $conn->query($query_tipos);
$tipos_vehiculos = [];
while ($row = $result_tipos->fetch_assoc()) {
    $tipos_vehiculos[] = $row['tipo'];
}

// Obtener parámetros actuales para mantenerlos en formulario
$busqueda_actual = isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '';
$tipo_vehiculo_actual = isset($_GET['tipo_vehiculo']) ? $_GET['tipo_vehiculo'] : '';
$orden_actual = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_reciente';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos Registrados - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .vehicle-img {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }
        .vehicle-img-mobile {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        .badge-vehiculo {
            font-size: 0.8rem;
        }
        .user-img {
            width: 50px;
            height: 50px;
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
        .clear-filters {
            margin-top: 10px;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .placa-badge {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .table-desktop {
                display: none;
            }
            .cards-mobile {
                display: block;
            }
            .vehicle-img {
                width: 100px;
                height: 75px;
            }
            .header-mobile {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start !important;
            }
            .filter-row-mobile {
                flex-direction: column;
            }
            .filter-row-mobile .col-md-4,
            .filter-row-mobile .col-md-3,
            .filter-row-mobile .col-md-2 {
                width: 100%;
                margin-bottom: 15px;
            }
            .stats-row-mobile {
                flex-direction: column;
            }
            .stats-row-mobile .col-md-4 {
                width: 100%;
                margin-bottom: 15px;
            }
        }
        
        @media (min-width: 769px) {
            .cards-mobile {
                display: none;
            }
            .table-desktop {
                display: table;
            }
        }
        
        .vehicle-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .vehicle-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .vehicle-card-header {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 10px 10px 0 0;
        }
        .vehicle-card-body {
            padding: 15px;
        }
        .vehicle-info {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .vehicle-info strong {
            color: #495057;
            min-width: 100px;
        }
        .btn-group-mobile {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 10px;
        }
        .btn-group-mobile .btn {
            flex: 1;
            min-width: 100px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-3 mt-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 header-mobile">
            <h2 class="mb-0"><i class="fas fa-car me-2"></i>Vehículos Registrados</h2>
            <a href="../paneles/administrador.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 filter-row-mobile" id="filterForm">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   placeholder="Placa, cédula o nombre" value="<?= $busqueda_actual ?>">
                            <?php if (!empty($busqueda_actual)): ?>
                                <button type="button" class="btn btn-outline-secondary" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                        <select class="form-select" id="tipo_vehiculo" name="tipo_vehiculo">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($tipos_vehiculos as $tipo): ?>
                                <option value="<?= $tipo ?>" <?= $tipo_vehiculo_actual == $tipo ? 'selected' : '' ?>>
                                    <?= ucfirst($tipo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="orden" class="form-label">Ordenar por</label>
                        <select class="form-select" id="orden" name="orden">
                            <option value="fecha_reciente" <?= $orden_actual == 'fecha_reciente' ? 'selected' : '' ?>>Fecha más reciente</option>
                            <option value="fecha_antigua" <?= $orden_actual == 'fecha_antigua' ? 'selected' : '' ?>>Fecha más antigua</option>
                            <option value="placa_asc" <?= $orden_actual == 'placa_asc' ? 'selected' : '' ?>>Placa A-Z</option>
                            <option value="placa_desc" <?= $orden_actual == 'placa_desc' ? 'selected' : '' ?>>Placa Z-A</option>
                            <option value="nombre_asc" <?= $orden_actual == 'nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
                            <option value="nombre_desc" <?= $orden_actual == 'nombre_desc' ? 'selected' : '' ?>>Nombre Z-A</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                    </div>
                </form>
                
                <?php if (!empty($busqueda_actual) || !empty($tipo_vehiculo_actual) || $orden_actual != 'fecha_reciente'): ?>
                    <div class="clear-filters">
                        <a href="vehiculos.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                        <span class="ms-2 text-muted">
                            <?php
                            $filtros_activos = [];
                            if (!empty($busqueda_actual)) $filtros_activos[] = "Búsqueda: \"$busqueda_actual\"";
                            if (!empty($tipo_vehiculo_actual)) $filtros_activos[] = "Tipo: " . ucfirst($tipo_vehiculo_actual);
                            if ($orden_actual != 'fecha_reciente') {
                                $orden_texto = [
                                    'fecha_antigua' => 'Fecha más antigua',
                                    'placa_asc' => 'Placa A-Z',
                                    'placa_desc' => 'Placa Z-A',
                                    'nombre_asc' => 'Nombre A-Z',
                                    'nombre_desc' => 'Nombre Z-A'
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

        <?php if (count($vehiculos) > 0): ?>
            <!-- Vista de Tabla para Escritorio -->
            <div class="card table-desktop">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Lista de Vehículos
                        <span class="badge bg-primary ms-2"><?= count($vehiculos) ?> registros</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Foto</th>
                                    <th>Placa</th>
                                    <th>Vehículo</th>
                                    <th>Propietario</th>
                                    <th>Cédula</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($vehiculo['foto_vehiculo'])): ?>
                                                <img src="../<?= $vehiculo['foto_vehiculo'] ?>" 
                                                     alt="Foto vehículo" 
                                                     class="vehicle-img"
                                                     title="Foto del vehículo">
                                            <?php else: ?>
                                                <div class="vehicle-img bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-car text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark placa-badge"><?= $vehiculo['placa'] ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= ucfirst($vehiculo['tipo_vehiculo']) ?></strong>
                                                <?php if (!empty($vehiculo['detalle_tipo'])): ?>
                                                    <br><small class="text-muted"><?= $vehiculo['detalle_tipo'] ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($vehiculo['marca'])): ?>
                                                    <br><small><?= $vehiculo['marca'] ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($vehiculo['color'])): ?>
                                                    <span class="badge bg-light text-dark"><?= $vehiculo['color'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($vehiculo['nombre_completo']) ?></strong>
                                                    <br>
                                                    <span class="badge bg-info badge-vehiculo"><?= ucfirst($vehiculo['tipo_usuario']) ?></span>
                                                    <br>
                                                    <small class="text-muted"><?= $vehiculo['email'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?= $vehiculo['cedula'] ?></code>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($vehiculo['fecha_registro'])) ?>
                                            <br>
                                            <small class="text-muted"><?= date('H:i', strtotime($vehiculo['fecha_registro'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons d-flex gap-1">
                                                <a href="ver_usuario.php?id=<?= $vehiculo['usuario_id'] ?>" 
                                                   class="btn btn-sm btn-primary btn-action" 
                                                   title="Ver propietario">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <a href="editar_usuario.php?id=<?= $vehiculo['usuario_id'] ?>" 
                                                   class="btn btn-sm btn-warning btn-action" 
                                                   title="Editar usuario">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Vista de Tarjetas para Móviles -->
            <div class="cards-mobile">
                <?php foreach ($vehiculos as $vehiculo): ?>
                <div class="vehicle-card">
                    <div class="vehicle-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <span class="badge bg-dark placa-badge"><?= $vehiculo['placa'] ?></span>
                            </h6>
                            <span class="badge bg-info"><?= ucfirst($vehiculo['tipo_usuario']) ?></span>
                        </div>
                    </div>
                    <div class="vehicle-card-body">
                        <div class="row">
                            <div class="col-4">
                                <?php if (!empty($vehiculo['foto_vehiculo'])): ?>
                                    <img src="../<?= $vehiculo['foto_vehiculo'] ?>" 
                                         alt="Foto vehículo" 
                                         class="vehicle-img-mobile w-100"
                                         title="Foto del vehículo">
                                <?php else: ?>
                                    <div class="vehicle-img-mobile bg-light d-flex align-items-center justify-content-center w-100">
                                        <i class="fas fa-car text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-8">
                                <div class="vehicle-info">
                                    <strong>Vehículo:</strong>
                                    <span><?= ucfirst($vehiculo['tipo_vehiculo']) ?></span>
                                </div>
                                <?php if (!empty($vehiculo['marca'])): ?>
                                <div class="vehicle-info">
                                    <strong>Marca:</strong>
                                    <span><?= $vehiculo['marca'] ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($vehiculo['color'])): ?>
                                <div class="vehicle-info">
                                    <strong>Color:</strong>
                                    <span class="badge bg-light text-dark"><?= $vehiculo['color'] ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="vehicle-info">
                            <strong>Propietario:</strong>
                            <span><?= htmlspecialchars($vehiculo['nombre_completo']) ?></span>
                        </div>
                        <div class="vehicle-info">
                            <strong>Cédula:</strong>
                            <span><code><?= $vehiculo['cedula'] ?></code></span>
                        </div>
                        <div class="vehicle-info">
                            <strong>Email:</strong>
                            <span class="text-muted"><?= $vehiculo['email'] ?></span>
                        </div>
                        <div class="vehicle-info">
                            <strong>Fecha:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($vehiculo['fecha_registro'])) ?></span>
                        </div>
                        
                        <div class="btn-group-mobile">
                            <a href="ver_usuario.php?id=<?= $vehiculo['usuario_id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-user me-1"></i> Ver
                            </a>
                            <a href="editar_usuario.php?id=<?= $vehiculo['usuario_id'] ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Información de resumen -->
            <div class="row mt-4 stats-row-mobile">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Vehículos</h5>
                            <p class="card-text display-6 text-primary"><?= count($vehiculos) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">Usuarios con Vehículo</h5>
                            <p class="card-text display-6 text-info"><?= count($vehiculos) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-car fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay vehículos registrados</h4>
                <p class="text-muted">
                    <?php if (!empty($busqueda_actual) || !empty($tipo_vehiculo_actual)): ?>
                        No se encontraron vehículos con los filtros aplicados.
                    <?php else: ?>
                        No hay vehículos registrados en el sistema.
                    <?php endif; ?>
                </p>
                <?php if (!empty($busqueda_actual) || !empty($tipo_vehiculo_actual)): ?>
                    <a href="vehiculos.php" class="btn btn-primary mt-2">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpiar búsqueda
        document.getElementById('clearSearch')?.addEventListener('click', function() {
            document.getElementById('busqueda').value = '';
            document.getElementById('filterForm').submit();
        });

        // Enviar formulario automáticamente cuando cambien algunos filtros
        document.getElementById('orden').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('tipo_vehiculo').addEventListener('change', function() {
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

        // Buscar al presionar Enter
        document.getElementById('busqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('filterForm').submit();
            }
        });
    </script>
</body>
</html>