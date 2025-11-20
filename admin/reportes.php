<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: ../acceso/login.php');
    exit();
}

// Obtener estadísticas para el dashboard de reportes
$conexion = $conn;

// 1. ESTADÍSTICAS GENERALES
$query_usuarios_totales = "SELECT COUNT(*) as total FROM usuarios_parqueadero";
$usuarios_totales = $conexion->query($query_usuarios_totales)->fetch_assoc()['total'];

$query_vehiculos_totales = "SELECT COUNT(*) as total FROM vehiculos";
$vehiculos_totales = $conexion->query($query_vehiculos_totales)->fetch_assoc()['total'];

$query_solicitudes_pendientes = "SELECT COUNT(*) as total FROM usuarios_parqueadero WHERE estado = 'pendiente'";
$solicitudes_pendientes = $conexion->query($query_solicitudes_pendientes)->fetch_assoc()['total'];

$query_reportes_pendientes = "SELECT COUNT(*) as total FROM reportes_carnet_perdido WHERE estado = 'pendiente'";
$reportes_pendientes = $conexion->query($query_reportes_pendientes)->fetch_assoc()['total'];

// 2. ESTADÍSTICAS POR TIPO DE USUARIO
$query_tipos_usuario = "SELECT tipo, COUNT(*) as cantidad FROM usuarios_parqueadero WHERE estado = 'aprobado' GROUP BY tipo";
$result_tipos_usuario = $conexion->query($query_tipos_usuario);
$tipos_usuario = [];
while ($row = $result_tipos_usuario->fetch_assoc()) {
    $tipos_usuario[$row['tipo']] = $row['cantidad'];
}

// 3. ESTADÍSTICAS POR TIPO DE VEHÍCULO
$query_tipos_vehiculo = "SELECT tipo, COUNT(*) as cantidad FROM vehiculos GROUP BY tipo";
$result_tipos_vehiculo = $conexion->query($query_tipos_vehiculo);
$tipos_vehiculo = [];
while ($row = $result_tipos_vehiculo->fetch_assoc()) {
    $tipos_vehiculo[$row['tipo']] = $row['cantidad'];
}

// 4. REGISTROS DE ACCESO RECIENTES (ÚLTIMOS 7 DÍAS)
$query_accesos_recientes = "SELECT DATE(fecha_hora) as fecha, COUNT(*) as cantidad 
                           FROM registros_acceso 
                           WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                           GROUP BY DATE(fecha_hora) 
                           ORDER BY fecha DESC";
$result_accesos_recientes = $conexion->query($query_accesos_recientes);
$accesos_por_dia = [];
while ($row = $result_accesos_recientes->fetch_assoc()) {
    $accesos_por_dia[$row['fecha']] = $row['cantidad'];
}

// 5. ESTADÍSTICAS MENSUALES
$mes_actual = date('Y-m');
$query_mensual = "SELECT 
    COUNT(DISTINCT u.id) as usuarios_nuevos,
    COUNT(DISTINCT v.id) as vehiculos_nuevos,
    COUNT(DISTINCT r.id) as reportes_nuevos
    FROM usuarios_parqueadero u
    LEFT JOIN vehiculos v ON MONTH(v.fecha_registro) = MONTH(CURRENT_DATE)
    LEFT JOIN reportes_carnet_perdido r ON MONTH(r.fecha_reporte) = MONTH(CURRENT_DATE)
    WHERE MONTH(u.fecha_registro) = MONTH(CURRENT_DATE)";

$estadisticas_mensuales = $conexion->query($query_mensual)->fetch_assoc();

// 6. TOP FACULTADES/PROGRAMAS
$query_facultades = "SELECT facultad, COUNT(*) as cantidad 
                    FROM usuarios_parqueadero 
                    WHERE facultad IS NOT NULL AND facultad != '' 
                    GROUP BY facultad 
                    ORDER BY cantidad DESC 
                    LIMIT 5";
$result_facultades = $conexion->query($query_facultades);
$top_facultades = [];
while ($row = $result_facultades->fetch_assoc()) {
    $top_facultades[] = $row;
}

// 7. ESTADO DE REPORTES DE CARNET
$query_estado_reportes = "SELECT estado, COUNT(*) as cantidad 
                         FROM reportes_carnet_perdido 
                         GROUP BY estado";
$result_estado_reportes = $conexion->query($query_estado_reportes);
$estado_reportes = [];
while ($row = $result_estado_reportes->fetch_assoc()) {
    $estado_reportes[$row['estado']] = $row['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .report-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        
        .quick-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .bg-custom-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-custom-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-custom-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .bg-custom-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .bg-custom-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .bg-custom-6 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="report-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-chart-bar me-2"></i>Reportes y Estadísticas</h2>
                            <p class="mb-0">Dashboard completo del sistema de parqueadero</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light me-2" onclick="exportarReporteCompleto()">
                                <i class="fas fa-download me-1"></i> Exportar Reporte
                            </button>
                            <button class="btn btn-warning" onclick="imprimirDashboard()">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card bg-custom-1 text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h3 class="stat-number"><?= $usuarios_totales ?></h3>
                        <p class="stat-label">Usuarios Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card bg-custom-2 text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-car fa-3x mb-3"></i>
                        <h3 class="stat-number"><?= $vehiculos_totales ?></h3>
                        <p class="stat-label">Vehículos Registrados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card bg-custom-3 text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3"></i>
                        <h3 class="stat-number"><?= $solicitudes_pendientes ?></h3>
                        <p class="stat-label">Solicitudes Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card bg-custom-4 text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-id-card fa-3x mb-3"></i>
                        <h3 class="stat-number"><?= $reportes_pendientes ?></h3>
                        <p class="stat-label">Reportes Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos y Estadísticas Detalladas -->
        <div class="row">
            <!-- Distribución por Tipo de Usuario -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie me-2"></i>Distribución por Tipo de Usuario</h5>
                    <canvas id="chartTiposUsuario" height="250"></canvas>
                </div>
            </div>

            <!-- Distribución por Tipo de Vehículo -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-car-side me-2"></i>Tipos de Vehículo</h5>
                    <canvas id="chartTiposVehiculo" height="250"></canvas>
                </div>
            </div>

            <!-- Estado de Reportes de Carnet -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-id-card me-2"></i>Estado de Reportes de Carnet</h5>
                    <canvas id="chartEstadoReportes" height="250"></canvas>
                </div>
            </div>

            <!-- Top Facultades -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-university me-2"></i>Top 5 Facultades</h5>
                    <canvas id="chartTopFacultades" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Tablas de Datos -->
        <div class="row">
            <!-- Accesos Recientes -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-sign-in-alt me-2"></i>Accesos Últimos 7 Días</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cantidad de Accesos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($accesos_por_dia)): ?>
                                    <?php foreach ($accesos_por_dia as $fecha => $cantidad): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($fecha)) ?></td>
                                            <td><span class="badge bg-primary"><?= $cantidad ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No hay datos de accesos recientes</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Estadísticas del Mes -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5><i class="fas fa-calendar-alt me-2"></i>Resumen del Mes Actual</h5>
                    <div class="quick-stats">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-number text-primary"><?= $estadisticas_mensuales['usuarios_nuevos'] ?? 0 ?></div>
                                <div class="stat-label">Usuarios Nuevos</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-success"><?= $estadisticas_mensuales['vehiculos_nuevos'] ?? 0 ?></div>
                                <div class="stat-label">Vehículos Nuevos</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-warning"><?= $estadisticas_mensuales['reportes_nuevos'] ?? 0 ?></div>
                                <div class="stat-label">Reportes Nuevos</div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4">Acciones Rápidas</h6>
                    <div class="d-grid gap-2">
                        <a href="solicitudes_pendientes.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-clock me-1"></i> Revisar Solicitudes Pendientes
                        </a>
                        <a href="reportes_carnet_perdido.php" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-id-card me-1"></i> Gestionar Reportes de Carnet
                        </a>
                        <a href="usuarios_aprobados.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-users me-1"></i> Ver Usuarios Aprobados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Tipos de Usuario
        const chartTiposUsuario = new Chart(document.getElementById('chartTiposUsuario'), {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($tipos_usuario as $tipo => $cantidad) {
                        $labels[] = "'" . ucfirst($tipo) . "'";
                    }
                    echo implode(', ', $labels);
                ?>],
                datasets: [{
                    data: [<?php echo implode(', ', $tipos_usuario); ?>],
                    backgroundColor: [
                        '#FF6B35', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Tipos de Vehículo
        const chartTiposVehiculo = new Chart(document.getElementById('chartTiposVehiculo'), {
            type: 'bar',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($tipos_vehiculo as $tipo => $cantidad) {
                        $labels[] = "'" . ucfirst($tipo) . "'";
                    }
                    echo implode(', ', $labels);
                ?>],
                datasets: [{
                    label: 'Cantidad',
                    data: [<?php echo implode(', ', $tipos_vehiculo); ?>],
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Estado de Reportes
        const chartEstadoReportes = new Chart(document.getElementById('chartEstadoReportes'), {
            type: 'pie',
            data: {
                labels: ['Pendiente', 'En Proceso', 'Resuelto'],
                datasets: [{
                    data: [
                        <?= $estado_reportes['pendiente'] ?? 0 ?>,
                        <?= $estado_reportes['en_proceso'] ?? 0 ?>,
                        <?= $estado_reportes['resuelto'] ?? 0 ?>
                    ],
                    backgroundColor: ['#dc3545', '#ffc107', '#198754']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Top Facultades
        const chartTopFacultades = new Chart(document.getElementById('chartTopFacultades'), {
            type: 'horizontalBar',
            data: {
                labels: [<?php 
                    $labels = [];
                    $data = [];
                    foreach ($top_facultades as $facultad) {
                        $labels[] = "'" . $facultad['facultad'] . "'";
                        $data[] = $facultad['cantidad'];
                    }
                    echo implode(', ', $labels);
                ?>],
                datasets: [{
                    label: 'Estudiantes',
                    data: [<?php echo implode(', ', $data); ?>],
                    backgroundColor: '#9b59b6'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Funciones de utilidad
        function exportarReporteCompleto() {
            alert('Función de exportación completa - Se puede implementar exportación a PDF/Excel');
            // window.location.href = 'exportar_reporte_completo.php';
        }

        function imprimirDashboard() {
            window.print();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>