<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Obtener fecha para el reporte (hoy por defecto)
$fecha_reporte = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fecha_anterior = date('Y-m-d', strtotime($fecha_reporte . ' -1 day'));

// Consultas para estadísticas
$estadisticas = [];

try {
    // 1. ESTADÍSTICAS GENERALES DEL DÍA
    $query_general = "SELECT 
                        COUNT(*) as total_movimientos,
                        SUM(CASE WHEN tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as total_entradas,
                        SUM(CASE WHEN tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as total_salidas,
                        COUNT(DISTINCT usuario_id) as usuarios_unicos,
                        COUNT(DISTINCT parqueadero_id) as parqueaderos_utilizados
                      FROM registros_acceso 
                      WHERE DATE(fecha_hora) = ?";
    
    $stmt_general = $conn->prepare($query_general);
    $stmt_general->bind_param("s", $fecha_reporte);
    $stmt_general->execute();
    $estadisticas['general'] = $stmt_general->get_result()->fetch_assoc();

    // 2. ESTADÍSTICAS POR TIPO DE USUARIO
    $query_tipo_usuario = "SELECT 
                            u.tipo,
                            COUNT(*) as total,
                            SUM(CASE WHEN ra.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                            SUM(CASE WHEN ra.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
                           FROM registros_acceso ra
                           INNER JOIN usuarios_parqueadero u ON ra.usuario_id = u.id
                           WHERE DATE(ra.fecha_hora) = ?
                           GROUP BY u.tipo
                           ORDER BY total DESC";
    
    $stmt_tipo = $conn->prepare($query_tipo_usuario);
    $stmt_tipo->bind_param("s", $fecha_reporte);
    $stmt_tipo->execute();
    $estadisticas['por_tipo_usuario'] = $stmt_tipo->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. ESTADÍSTICAS POR PARQUEADERO
    $query_parqueadero = "SELECT 
                            p.nombre,
                            p.id,
                            COUNT(*) as total_movimientos,
                            SUM(CASE WHEN ra.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                            SUM(CASE WHEN ra.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas,
                            MAX(p.capacidad_total) as capacidad_total,
                            MAX(p.capacidad_actual) as capacidad_actual
                          FROM registros_acceso ra
                          INNER JOIN parqueaderos p ON ra.parqueadero_id = p.id
                          WHERE DATE(ra.fecha_hora) = ?
                          GROUP BY p.id, p.nombre
                          ORDER BY total_movimientos DESC";
    
    $stmt_parq = $conn->prepare($query_parqueadero);
    $stmt_parq->bind_param("s", $fecha_reporte);
    $stmt_parq->execute();
    $estadisticas['por_parqueadero'] = $stmt_parq->get_result()->fetch_all(MYSQLI_ASSOC);

    // 4. ESTADÍSTICAS POR MÉTODO DE ACCESO
    $query_metodo = "SELECT 
                      metodo_acceso,
                      COUNT(*) as total,
                      SUM(CASE WHEN tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                      SUM(CASE WHEN tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
                     FROM registros_acceso 
                     WHERE DATE(fecha_hora) = ?
                     GROUP BY metodo_acceso
                     ORDER BY total DESC";
    
    $stmt_metodo = $conn->prepare($query_metodo);
    $stmt_metodo->bind_param("s", $fecha_reporte);
    $stmt_metodo->execute();
    $estadisticas['por_metodo'] = $stmt_metodo->get_result()->fetch_all(MYSQLI_ASSOC);

    // 5. HORAS PICO DE ENTRADAS
    $query_horas_pico = "SELECT 
                          HOUR(fecha_hora) as hora,
                          COUNT(*) as total_entradas
                         FROM registros_acceso 
                         WHERE DATE(fecha_hora) = ? 
                         AND tipo_movimiento = 'entrada'
                         GROUP BY HOUR(fecha_hora)
                         ORDER BY total_entradas DESC
                         LIMIT 5";
    
    $stmt_horas = $conn->prepare($query_horas_pico);
    $stmt_horas->bind_param("s", $fecha_reporte);
    $stmt_horas->execute();
    $estadisticas['horas_pico'] = $stmt_horas->get_result()->fetch_all(MYSQLI_ASSOC);

    // 6. USUARIOS MÁS ACTIVOS
    $query_usuarios_activos = "SELECT 
                                u.nombre_completo,
                                u.cedula,
                                u.tipo,
                                COUNT(*) as total_movimientos,
                                SUM(CASE WHEN ra.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                                SUM(CASE WHEN ra.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
                               FROM registros_acceso ra
                               INNER JOIN usuarios_parqueadero u ON ra.usuario_id = u.id
                               WHERE DATE(ra.fecha_hora) = ?
                               GROUP BY u.id, u.nombre_completo, u.cedula, u.tipo
                               ORDER BY total_movimientos DESC
                               LIMIT 10";
    
    $stmt_activos = $conn->prepare($query_usuarios_activos);
    $stmt_activos->bind_param("s", $fecha_reporte);
    $stmt_activos->execute();
    $estadisticas['usuarios_activos'] = $stmt_activos->get_result()->fetch_all(MYSQLI_ASSOC);

    // 7. VIGILANTES MÁS ACTIVOS
    $query_vigilantes = "SELECT 
                          e.nombre as vigilante_nombre,
                          COUNT(*) as registros_realizados,
                          SUM(CASE WHEN ra.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas_registradas,
                          SUM(CASE WHEN ra.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas_registradas
                         FROM registros_acceso ra
                         INNER JOIN empleados e ON ra.empleado_id = e.id
                         WHERE DATE(ra.fecha_hora) = ?
                         AND ra.metodo_acceso = 'manual'
                         GROUP BY e.id, e.nombre
                         ORDER BY registros_realizados DESC";
    
    $stmt_vigilantes = $conn->prepare($query_vigilantes);
    $stmt_vigilantes->bind_param("s", $fecha_reporte);
    $stmt_vigilantes->execute();
    $estadisticas['vigilantes'] = $stmt_vigilantes->get_result()->fetch_all(MYSQLI_ASSOC);

    // 8. COMPARATIVA CON EL DÍA ANTERIOR
    $query_comparativa = "SELECT 
                            COUNT(*) as total_hoy,
                            (SELECT COUNT(*) FROM registros_acceso WHERE DATE(fecha_hora) = ?) as total_ayer
                          FROM registros_acceso 
                          WHERE DATE(fecha_hora) = ?";
    
    $stmt_comp = $conn->prepare($query_comparativa);
    $stmt_comp->bind_param("ss", $fecha_anterior, $fecha_reporte);
    $stmt_comp->execute();
    $estadisticas['comparativa'] = $stmt_comp->get_result()->fetch_assoc();

    // 9. VEHÍCULOS MÁS FRECUENTES
    $query_vehiculos = "SELECT 
                          v.placa,
                          v.tipo as tipo_vehiculo,
                          v.marca,
                          v.color,
                          COUNT(*) as total_visitas,
                          u.nombre_completo as propietario,
                          u.tipo as tipo_usuario
                         FROM registros_acceso ra
                         INNER JOIN vehiculos v ON ra.vehiculo_id = v.id
                         INNER JOIN usuarios_parqueadero u ON v.usuario_id = u.id
                         WHERE DATE(ra.fecha_hora) = ?
                         GROUP BY v.id, v.placa, v.tipo, v.marca, v.color, u.nombre_completo, u.tipo
                         ORDER BY total_visitas DESC
                         LIMIT 15";
    
    $stmt_vehiculos = $conn->prepare($query_vehiculos);
    $stmt_vehiculos->bind_param("s", $fecha_reporte);
    $stmt_vehiculos->execute();
    $estadisticas['vehiculos_frecuentes'] = $stmt_vehiculos->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error en reportes: " . $e->getMessage());
    $error = "Error al generar reportes: " . $e->getMessage();
}

// Función para calcular porcentaje de cambio
function calcularPorcentajeCambio($actual, $anterior) {
    if ($anterior == 0) return $actual > 0 ? 100 : 0;
    return round((($actual - $anterior) / $anterior) * 100, 1);
}

// Función para formatear hora
function formatearHora($hora) {
    return str_pad($hora, 2, '0', STR_PAD_LEFT) . ':00';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Diarios - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .header-reporte {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .card-stat {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .stat-trend {
            font-size: 0.9rem;
            font-weight: bold;
        }
        .trend-up {
            color: #28a745;
        }
        .trend-down {
            color: #dc3545;
        }
        .trend-neutral {
            color: #6c757d;
        }
        .section-title {
            border-left: 4px solid #667eea;
            padding-left: 15px;
            margin: 30px 0 20px 0;
            color: #495057;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 300px;
        }
        .badge-estudiante { background: linear-gradient(135deg, #28a745, #20c997); }
        .badge-profesor { background: linear-gradient(135deg, #007bff, #0056b3); }
        .badge-administrativo { background: linear-gradient(135deg, #6f42c1, #5a2d9c); }
        .badge-externo { background: linear-gradient(135deg, #fd7e14, #e55a00); }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        .fecha-selector {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Encabezado del Reporte -->
        <div class="row">
            <div class="col-12">
                <div class="header-reporte">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1><i class="fas fa-chart-line me-2"></i>Reportes Diarios del Sistema</h1>
                            <p class="mb-0">Estadísticas completas y análisis de movimientos del parqueadero</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h3><?= date('d/m/Y', strtotime($fecha_reporte)) ?></h3>
                            <small>Reporte generado: <?= date('H:i:s') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de Fecha -->
        <div class="row">
            <div class="col-12">
                <div class="fecha-selector">
                    <form method="GET" class="row align-items-center">
                        <div class="col-md-4">
                            <label for="fecha" class="form-label fw-bold">Seleccionar Fecha del Reporte:</label>
                            <input type="date" class="form-control form-control-lg" id="fecha" name="fecha" 
                                   value="<?= $fecha_reporte ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-lg mt-3">
                                <i class="fas fa-sync-alt me-2"></i>Generar Reporte
                            </button>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group mt-3">
                                <a href="?fecha=<?= date('Y-m-d', strtotime($fecha_reporte . ' -1 day')) ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-chevron-left me-1"></i>Día Anterior
                                </a>
                                <a href="?fecha=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">
                                    Hoy
                                </a>
                                <?php if ($fecha_reporte < date('Y-m-d')): ?>
                                <a href="?fecha=<?= date('Y-m-d', strtotime($fecha_reporte . ' +1 day')) ?>" 
                                   class="btn btn-outline-primary">
                                    Día Siguiente<i class="fas fa-chevron-right ms-1"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <!-- ESTADÍSTICAS PRINCIPALES -->
        <div class="row">
            <!-- Total Movimientos -->
            <div class="col-md-3 mb-4">
                <div class="card card-stat border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-3x text-primary mb-3"></i>
                        <h3 class="stat-number text-primary">
                            <?= $estadisticas['general']['total_movimientos'] ?? 0 ?>
                        </h3>
                        <h6 class="text-muted">Total Movimientos</h6>
                        <?php if ($estadisticas['comparativa']): ?>
                            <?php 
                            $cambio = calcularPorcentajeCambio(
                                $estadisticas['general']['total_movimientos'] ?? 0,
                                $estadisticas['comparativa']['total_ayer'] ?? 0
                            );
                            ?>
                            <div class="stat-trend <?= $cambio > 0 ? 'trend-up' : ($cambio < 0 ? 'trend-down' : 'trend-neutral') ?>">
                                <i class="fas fa-arrow-<?= $cambio > 0 ? 'up' : ($cambio < 0 ? 'down' : 'right') ?> me-1"></i>
                                <?= abs($cambio) ?>% vs ayer
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Entradas -->
            <div class="col-md-3 mb-4">
                <div class="card card-stat border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-sign-in-alt fa-3x text-success mb-3"></i>
                        <h3 class="stat-number text-success">
                            <?= $estadisticas['general']['total_entradas'] ?? 0 ?>
                        </h3>
                        <h6 class="text-muted">Entradas Registradas</h6>
                    </div>
                </div>
            </div>

            <!-- Salidas -->
            <div class="col-md-3 mb-4">
                <div class="card card-stat border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                        <h3 class="stat-number text-danger">
                            <?= $estadisticas['general']['total_salidas'] ?? 0 ?>
                        </h3>
                        <h6 class="text-muted">Salidas Registradas</h6>
                    </div>
                </div>
            </div>

            <!-- Usuarios Únicos -->
            <div class="col-md-3 mb-4">
                <div class="card card-stat border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h3 class="stat-number text-warning">
                            <?= $estadisticas['general']['usuarios_unicos'] ?? 0 ?>
                        </h3>
                        <h6 class="text-muted">Usuarios Únicos</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRÁFICOS Y ESTADÍSTICAS DETALLADAS -->
        <div class="row">
            <!-- Distribución por Tipo de Usuario -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="section-title">Distribución por Tipo de Usuario</h5>
                    <canvas id="chartTipoUsuario"></canvas>
                </div>
            </div>

            <!-- Métodos de Acceso -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="section-title">Métodos de Acceso</h5>
                    <canvas id="chartMetodoAcceso"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLAS DETALLADAS -->
        
        <!-- Por Tipo de Usuario -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Estadísticas por Tipo de Usuario</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo de Usuario</th>
                                        <th>Total Movimientos</th>
                                        <th>Entradas</th>
                                        <th>Salidas</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['por_tipo_usuario'] as $tipo): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-<?= strtolower($tipo['tipo']) ?> text-white">
                                                    <?= ucfirst($tipo['tipo']) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= $tipo['total'] ?></strong></td>
                                            <td class="text-success"><?= $tipo['entradas'] ?></td>
                                            <td class="text-danger"><?= $tipo['salidas'] ?></td>
                                            <td>
                                                <?= round(($tipo['total'] / $estadisticas['general']['total_movimientos']) * 100, 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Por Parqueadero -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-parking me-2"></i>Actividad por Parqueadero</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Parqueadero</th>
                                        <th>Total Movimientos</th>
                                        <th>Entradas</th>
                                        <th>Salidas</th>
                                        <th>Capacidad</th>
                                        <th>Ocupación Máxima</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['por_parqueadero'] as $parq): ?>
                                        <tr>
                                            <td><strong><?= $parq['nombre'] ?></strong></td>
                                            <td><?= $parq['total_movimientos'] ?></td>
                                            <td class="text-success"><?= $parq['entradas'] ?></td>
                                            <td class="text-danger"><?= $parq['salidas'] ?></td>
                                            <td>
                                                <small><?= $parq['capacidad_actual'] ?> / <?= $parq['capacidad_total'] ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $ocupacion_max = $parq['entradas'] - $parq['salidas'];
                                                $porcentaje = round(($ocupacion_max / $parq['capacidad_total']) * 100, 1);
                                                ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $porcentaje > 80 ? 'bg-danger' : ($porcentaje > 60 ? 'bg-warning' : 'bg-success') ?>" 
                                                         role="progressbar" style="width: <?= $porcentaje ?>%;">
                                                        <?= $porcentaje ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios Más Activos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 10 Usuarios Más Activos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Usuario</th>
                                        <th>Cédula</th>
                                        <th>Tipo</th>
                                        <th>Total Movimientos</th>
                                        <th>Entradas</th>
                                        <th>Salidas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['usuarios_activos'] as $index => $usuario): ?>
                                        <tr>
                                            <td><strong><?= $index + 1 ?></strong></td>
                                            <td><?= $usuario['nombre_completo'] ?></td>
                                            <td><code><?= $usuario['cedula'] ?></code></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($usuario['tipo']) ?> text-white">
                                                    <?= ucfirst($usuario['tipo']) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= $usuario['total_movimientos'] ?></strong></td>
                                            <td class="text-success"><?= $usuario['entradas'] ?></td>
                                            <td class="text-danger"><?= $usuario['salidas'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehículos Más Frecuentes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-car me-2"></i>Vehículos Más Frecuentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Vehículo</th>
                                        <th>Propietario</th>
                                        <th>Tipo Usuario</th>
                                        <th>Total Visitas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['vehiculos_frecuentes'] as $vehiculo): ?>
                                        <tr>
                                            <td><strong><?= $vehiculo['placa'] ?></strong></td>
                                            <td>
                                                <?= $vehiculo['tipo_vehiculo'] ?>
                                                <?php if ($vehiculo['marca']): ?> • <?= $vehiculo['marca'] ?><?php endif; ?>
                                                <?php if ($vehiculo['color']): ?> • <?= $vehiculo['color'] ?><?php endif; ?>
                                            </td>
                                            <td><?= $vehiculo['propietario'] ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($vehiculo['tipo_usuario']) ?> text-white">
                                                    <?= ucfirst($vehiculo['tipo_usuario']) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= $vehiculo['total_visitas'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vigilantes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Actividad de Vigilantes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vigilante</th>
                                        <th>Registros Manuales</th>
                                        <th>Entradas Registradas</th>
                                        <th>Salidas Registradas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['vigilantes'] as $vigilante): ?>
                                        <tr>
                                            <td><strong><?= $vigilante['vigilante_nombre'] ?></strong></td>
                                            <td><?= $vigilante['registros_realizados'] ?></td>
                                            <td class="text-success"><?= $vigilante['entradas_registradas'] ?></td>
                                            <td class="text-danger"><?= $vigilante['salidas_registradas'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($estadisticas['vigilantes'])): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No hubo registros manuales este día
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Horas Pico -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Horas Pico de Entradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Total Entradas</th>
                                        <th>Porcentaje del Día</th>
                                        <th>Intensidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['horas_pico'] as $hora): ?>
                                        <tr>
                                            <td><strong><?= formatearHora($hora['hora']) ?></strong></td>
                                            <td><?= $hora['total_entradas'] ?></td>
                                            <td>
                                                <?= round(($hora['total_entradas'] / $estadisticas['general']['total_entradas']) * 100, 1) ?>%
                                            </td>
                                            <td>
                                                <?php 
                                                $intensidad = ($hora['total_entradas'] / $estadisticas['general']['total_entradas']) * 100;
                                                if ($intensidad > 15): ?>
                                                    <span class="badge bg-danger">ALTA</span>
                                                <?php elseif ($intensidad > 8): ?>
                                                    <span class="badge bg-warning">MEDIA</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">BAJA</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Botón de Impresión -->
    <button onclick="window.print()" class="btn btn-primary print-btn">
        <i class="fas fa-print me-2"></i>Imprimir Reporte
    </button>

    <script>
        // Gráfico de Tipo de Usuario
        const ctxTipoUsuario = document.getElementById('chartTipoUsuario').getContext('2d');
        new Chart(ctxTipoUsuario, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($estadisticas['por_tipo_usuario'] as $tipo): ?>
                        '<?= ucfirst($tipo['tipo']) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($estadisticas['por_tipo_usuario'] as $tipo): ?>
                            <?= $tipo['total'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#28a745', '#007bff', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Métodos de Acceso
        const ctxMetodoAcceso = document.getElementById('chartMetodoAcceso').getContext('2d');
        new Chart(ctxMetodoAcceso, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($estadisticas['por_metodo'] as $metodo): ?>
                        '<?= ucfirst($metodo['metodo_acceso']) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($estadisticas['por_metodo'] as $metodo): ?>
                            <?= $metodo['total'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#f5576c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>