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

    // 7. VIGILANTES MÁS ACTIVOS - CONSULTA CORREGIDA
        $query_vigilantes = "SELECT 
                            u.nombre_completo as vigilante_nombre,
                            COUNT(*) as registros_realizados,
                            SUM(CASE WHEN ra.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas_registradas,
                            SUM(CASE WHEN ra.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas_registradas
                            FROM registros_acceso ra
                            INNER JOIN usuarios_parqueadero u ON ra.empleado_id = u.id
                            WHERE DATE(ra.fecha_hora) = ?
                            AND ra.metodo_acceso = 'manual'
                            GROUP BY u.id, u.nombre_completo
                            ORDER BY registros_realizados DESC";

        $stmt_vigilantes = $conn->prepare($query_vigilantes);
        $stmt_vigilantes->bind_param("s", $fecha_reporte);
        $stmt_vigilantes->execute();
        $estadisticas['vigilantes'] = $stmt_vigilantes->get_result()->fetch_all(MYSQLI_ASSOC);

        // DEBUG: Verificar qué está pasando con los registros manuales
$query_debug = "SELECT 
                 ra.id, 
                 ra.empleado_id,
                 ra.tipo_movimiento,
                 ra.metodo_acceso,
                 u.nombre_completo
                FROM registros_acceso ra
                LEFT JOIN usuarios_parqueadero u ON ra.empleado_id = u.id
                WHERE DATE(ra.fecha_hora) = ?
                AND ra.metodo_acceso = 'manual'
                ORDER BY ra.fecha_hora";

$stmt_debug = $conn->prepare($query_debug);
$stmt_debug->bind_param("s", $fecha_reporte);
$stmt_debug->execute();
$debug_results = $stmt_debug->get_result()->fetch_all(MYSQLI_ASSOC);

error_log("DEBUG - Registros manuales encontrados: " . count($debug_results));
foreach ($debug_results as $debug) {
    error_log("ID: " . $debug['id'] . ", Empleado ID: " . $debug['empleado_id'] . ", Vigilante: " . $debug['nombre_completo']);
}

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

// Función para obtener nombre del día
function obtenerNombreDia($fecha) {
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return $dias[date('w', strtotime($fecha))];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .container-fluid { max-width: 100% !important; }
            .print-break { page-break-after: always; }
            .print-mt { margin-top: 20px; }
        }
        
        .header-reporte {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 10px;
            color: white;
            padding: 25px;
            margin-bottom: 25px;
        }
        .card-stat {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        .card-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .section-title {
            border-bottom: 2px solid #1e3c72;
            padding-bottom: 10px;
            margin: 25px 0 15px 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 280px;
        }
        .table-custom th {
            background-color: #1e3c72;
            color: white;
            border: none;
        }
        .badge-estudiante { background-color: #28a745; }
        .badge-profesor { background-color: #007bff; }
        .badge-administrativo { background-color: #6f42c1; }
        .badge-externo { background-color: #fd7e14; }
        .fecha-selector {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .print-header {
            display: none;
        }
        @media print {
            .print-header {
                display: block;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #1e3c72;
            }
            .header-reporte { display: none; }
            .fecha-selector { display: none; }
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #1e3c72;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-3">

        <!-- Encabezado para Impresión -->
        <div class="print-header">
            <h2>Reporte Diario - Sistema de Parqueadero</h2>
            <h4><?= date('d/m/Y', strtotime($fecha_reporte)) ?> - <?= obtenerNombreDia($fecha_reporte) ?></h4>
            <p>Generado el: <?= date('d/m/Y H:i:s') ?></p>
            <hr>
        </div>

        <!-- Encabezado del Reporte -->
        <div class="row">
            <div class="col-12">
                <div class="header-reporte">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-chart-bar me-2"></i>Reporte Diario del Sistema</h2>
                            <p class="mb-0">Resumen completo de actividad del parqueadero</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h4><?= date('d/m/Y', strtotime($fecha_reporte)) ?></h4>
                            <h5><?= obtenerNombreDia($fecha_reporte) ?></h5>
                            <small>Generado: <?= date('H:i:s') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de Fecha -->
        <div class="row no-print">
            <div class="col-12">
                <div class="fecha-selector">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label fw-bold">Seleccionar Fecha:</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" 
                                   value="<?= $fecha_reporte ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" onclick="cambiarFecha('<?= date('Y-m-d', strtotime($fecha_reporte . ' -1 day')) ?>')" 
                                        class="btn btn-outline-primary">
                                    <i class="fas fa-chevron-left me-1"></i>Ayer
                                </button>
                                <button type="button" onclick="cambiarFecha('<?= date('Y-m-d') ?>')" 
                                        class="btn btn-primary">
                                    Hoy
                                </button>
                                <?php if ($fecha_reporte < date('Y-m-d')): ?>
                                <button type="button" onclick="cambiarFecha('<?= date('Y-m-d', strtotime($fecha_reporte . ' +1 day')) ?>')" 
                                        class="btn btn-outline-primary">
                                    Mañana<i class="fas fa-chevron-right ms-1"></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="window.print()" class="btn btn-success">
                                    <i class="fas fa-print me-1"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <!-- RESUMEN EJECUTIVO -->
        <div class="row">
            <div class="col-12">
                <div class="info-box">
                    <h5><i class="fas fa-info-circle me-2"></i>Resumen Ejecutivo</h5>
                    <p class="mb-0">
                        En la fecha <?= date('d/m/Y', strtotime($fecha_reporte)) ?> se registraron 
                        <strong><?= $estadisticas['general']['total_movimientos'] ?? 0 ?></strong> movimientos en total, 
                        con <strong><?= $estadisticas['general']['total_entradas'] ?? 0 ?></strong> entradas y 
                        <strong><?= $estadisticas['general']['total_salidas'] ?? 0 ?></strong> salidas. 
                        <?= $estadisticas['general']['usuarios_unicos'] ?? 0 ?> usuarios únicos utilizaron el servicio.
                    </p>
                </div>
            </div>
        </div>

        <!-- ESTADÍSTICAS PRINCIPALES -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card card-stat border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-2x text-primary mb-2"></i>
                        <h3 class="stat-number text-primary"><?= $estadisticas['general']['total_movimientos'] ?? 0 ?></h3>
                        <h6 class="text-muted">Total Movimientos</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-stat border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-sign-in-alt fa-2x text-success mb-2"></i>
                        <h3 class="stat-number text-success"><?= $estadisticas['general']['total_entradas'] ?? 0 ?></h3>
                        <h6 class="text-muted">Entradas</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-stat border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-sign-out-alt fa-2x text-danger mb-2"></i>
                        <h3 class="stat-number text-danger"><?= $estadisticas['general']['total_salidas'] ?? 0 ?></h3>
                        <h6 class="text-muted">Salidas</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-stat border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h3 class="stat-number text-warning"><?= $estadisticas['general']['usuarios_unicos'] ?? 0 ?></h3>
                        <h6 class="text-muted">Usuarios Únicos</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRIMERA SECCIÓN: DISTRIBUCIÓN -->
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

        <!-- SEGUNDA SECCIÓN: DETALLES POR TIPO -->
        <div class="print-break"></div>
        <h4 class="section-title print-mt">Detalles por Tipo de Usuario</h4>
        <div class="row">
            <?php foreach ($estadisticas['por_tipo_usuario'] as $tipo): ?>
            <div class="col-md-3 mb-3">
                <div class="summary-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge badge-<?= strtolower($tipo['tipo']) ?> text-white">
                            <?= ucfirst($tipo['tipo']) ?>
                        </span>
                        <strong><?= $tipo['total'] ?></strong>
                    </div>
                    <div class="mt-2">
                        <small class="text-success">
                            <i class="fas fa-sign-in-alt"></i> <?= $tipo['entradas'] ?> entradas
                        </small><br>
                        <small class="text-danger">
                            <i class="fas fa-sign-out-alt"></i> <?= $tipo['salidas'] ?> salidas
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TERCERA SECCIÓN: PARQUEADEROS -->
        <h4 class="section-title">Actividad por Parqueadero</h4>
        <div class="row">
            <?php foreach ($estadisticas['por_parqueadero'] as $parq): ?>
            <div class="col-md-4 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <h6 class="card-title"><?= $parq['nombre'] ?></h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted">Total</small>
                                <div class="fw-bold"><?= $parq['total_movimientos'] ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Entradas</small>
                                <div class="fw-bold text-success"><?= $parq['entradas'] ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Salidas</small>
                                <div class="fw-bold text-danger"><?= $parq['salidas'] ?></div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Capacidad: <?= $parq['capacidad_actual'] ?>/<?= $parq['capacidad_total'] ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CUARTA SECCIÓN: USUARIOS ACTIVOS -->
        <div class="print-break"></div>
        <h4 class="section-title print-mt">Top 10 Usuarios Más Activos</h4>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-custom">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>Entradas</th>
                                <th>Salidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estadisticas['usuarios_activos'] as $index => $usuario): ?>
                            <tr>
                                <td class="fw-bold"><?= $index + 1 ?></td>
                                <td><?= $usuario['nombre_completo'] ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($usuario['tipo']) ?> text-white">
                                        <?= ucfirst($usuario['tipo']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= $usuario['total_movimientos'] ?></td>
                                <td class="text-success"><?= $usuario['entradas'] ?></td>
                                <td class="text-danger"><?= $usuario['salidas'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- QUINTA SECCIÓN: VIGILANTES Y HORAS PICO -->
<div class="row">
    <!-- Vigilantes -->
    <div class="col-md-6">
        <h5 class="section-title">Registros Manuales por Vigilante</h5>
        
        <!-- Información de debug extendida -->
        <div class="alert alert-info no-print mb-3">
            <small>
                <strong>Información de diagnóstico COMPLETA:</strong><br>
                • Total registros manuales en BD: <?= $estadisticas['debug_info']['total_manuales_bd'] ?><br>
                • Registros manuales en <?= $fecha_reporte ?>: <?= $estadisticas['debug_info']['manuales_fecha'] ?><br>
                • Registros con empleado_id: <?= $estadisticas['debug_info']['manuales_con_empleado'] ?><br>
                • Vigilantes encontrados: <?= $estadisticas['debug_info']['vigilantes_encontrados'] ?>
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vigilante</th>
                        <th>Total Registros</th>
                        <th>Entradas</th>
                        <th>Salidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estadisticas['vigilantes'] as $vigilante): ?>
                    <tr>
                        <td><?= htmlspecialchars($vigilante['vigilante_nombre']) ?></td>
                        <td class="fw-bold"><?= $vigilante['registros_realizados'] ?></td>
                        <td class="text-success"><?= $vigilante['entradas_registradas'] ?></td>
                        <td class="text-danger"><?= $vigilante['salidas_registradas'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($estadisticas['vigilantes'])): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-2">
                            No se encontraron registros manuales asignados a vigilantes
                            <?php if ($estadisticas['debug_info']['manuales_fecha'] > 0): ?>
                                <br><small class="text-warning">
                                    ¡Pero hay <?= $estadisticas['debug_info']['manuales_fecha'] ?> registros manuales en esta fecha!
                                    Revisa los logs del servidor para más detalles.
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Consulta directa para ver los registros -->
        <?php if ($estadisticas['debug_info']['manuales_fecha'] > 0): ?>
        <div class="alert alert-warning no-print mt-3">
            <h6>Registros manuales en <?= $fecha_reporte ?>:</h6>
            <?php
            $query_detalle_manuales = "SELECT 
                                        id, empleado_id, tipo_movimiento, metodo_acceso, fecha_hora
                                       FROM registros_acceso 
                                       WHERE DATE(fecha_hora) = ? 
                                       AND metodo_acceso = 'manual'
                                       ORDER BY fecha_hora";
            $stmt_detalle = $conn->prepare($query_detalle_manuales);
            $stmt_detalle->bind_param("s", $fecha_reporte);
            $stmt_detalle->execute();
            $detalle_manuales = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado ID</th>
                        <th>Movimiento</th>
                        <th>Método</th>
                        <th>Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle_manuales as $registro): ?>
                    <tr>
                        <td><?= $registro['id'] ?></td>
                        <td class="<?= $registro['empleado_id'] ? 'text-success' : 'text-danger' ?>">
                            <?= $registro['empleado_id'] ?: 'NULL' ?>
                        </td>
                        <td><?= $registro['tipo_movimiento'] ?></td>
                        <td><?= $registro['metodo_acceso'] ?></td>
                        <td><?= $registro['fecha_hora'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Horas Pico -->
    <div class="col-md-6">
        <h5 class="section-title">Horas con Más Entradas</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Entradas</th>
                        <th>% del Día</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estadisticas['horas_pico'] as $hora): ?>
                    <tr>
                        <td class="fw-bold"><?= formatearHora($hora['hora']) ?></td>
                        <td><?= $hora['total_entradas'] ?></td>
                        <td>
                            <?= round(($hora['total_entradas'] / $estadisticas['general']['total_entradas']) * 100, 1) ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
        <!-- SEXTA SECCIÓN: VEHÍCULOS FRECUENTES -->
        <div class="print-break"></div>
        <h4 class="section-title print-mt">Vehículos Más Frecuentes</h4>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-custom">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Propietario</th>
                                <th>Tipo</th>
                                <th>Visitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estadisticas['vehiculos_frecuentes'] as $vehiculo): ?>
                            <tr>
                                <td class="fw-bold"><?= $vehiculo['placa'] ?></td>
                                <td>
                                    <?= $vehiculo['tipo_vehiculo'] ?>
                                    <?php if ($vehiculo['marca']): ?> - <?= $vehiculo['marca'] ?><?php endif; ?>
                                    <?php if ($vehiculo['color']): ?> (<?= $vehiculo['color'] ?>)<?php endif; ?>
                                </td>
                                <td><?= $vehiculo['propietario'] ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($vehiculo['tipo_usuario']) ?> text-white">
                                        <?= ucfirst($vehiculo['tipo_usuario']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= $vehiculo['total_visitas'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    

    <script>
        // Función para cambiar fecha
        function cambiarFecha(fecha) {
            window.location.href = '?fecha=' + fecha;
        }

        // Event listener para cambio de fecha
        document.getElementById('fecha').addEventListener('change', function() {
            cambiarFecha(this.value);
        });

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
                    backgroundColor: ['#28a745', '#007bff', '#6f42c1', '#fd7e14', '#20c997']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
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
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Auto-print si se solicita
        <?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>
    </script>
</body>
</html>