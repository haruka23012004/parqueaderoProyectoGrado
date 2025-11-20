<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: ../acceso/login.php');
    exit();
}

// Procesar exportación a Excel
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_parqueadero_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "<html>";
    echo "<head>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; }";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "th { background-color: #f2f2f2; font-weight: bold; }";
    echo ".header { background: #2c3e50; color: white; padding: 15px; text-align: center; }";
    echo ".section { margin: 20px 0; }";
    echo ".section-title { background: #34495e; color: white; padding: 10px; font-weight: bold; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    echo "<div class='header'>";
    echo "<h2>REPORTE DEL SISTEMA DE PARQUEADERO</h2>";
    echo "<p>Generado el: " . date('d/m/Y H:i') . "</p>";
    echo "</div>";
    
    // Obtener datos para el Excel
    $conexion = $conn;
    
    // Estadísticas generales
    $usuarios_totales = $conexion->query("SELECT COUNT(*) as total FROM usuarios_parqueadero")->fetch_assoc()['total'];
    $vehiculos_totales = $conexion->query("SELECT COUNT(*) as total FROM vehiculos")->fetch_assoc()['total'];
    $solicitudes_pendientes = $conexion->query("SELECT COUNT(*) as total FROM usuarios_parqueadero WHERE estado = 'pendiente'")->fetch_assoc()['total'];
    $reportes_pendientes = $conexion->query("SELECT COUNT(*) as total FROM reportes_carnet_perdido WHERE estado = 'pendiente'")->fetch_assoc()['total'];
    
    echo "<div class='section'>";
    echo "<div class='section-title'>ESTADÍSTICAS GENERALES</div>";
    echo "<table>";
    echo "<tr><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Usuarios Totales</td><td>" . $usuarios_totales . "</td></tr>";
    echo "<tr><td>Vehículos Registrados</td><td>" . $vehiculos_totales . "</td></tr>";
    echo "<tr><td>Solicitudes Pendientes</td><td>" . $solicitudes_pendientes . "</td></tr>";
    echo "<tr><td>Reportes Pendientes</td><td>" . $reportes_pendientes . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Tipos de usuario
    $result_tipos = $conexion->query("SELECT tipo, COUNT(*) as cantidad FROM usuarios_parqueadero WHERE estado = 'aprobado' GROUP BY tipo");
    echo "<div class='section'>";
    echo "<div class='section-title'>DISTRIBUCIÓN POR TIPO DE USUARIO</div>";
    echo "<table>";
    echo "<tr><th>Tipo de Usuario</th><th>Cantidad</th></tr>";
    while ($row = $result_tipos->fetch_assoc()) {
        echo "<tr><td>" . ucfirst($row['tipo']) . "</td><td>" . $row['cantidad'] . "</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Tipos de vehículo
    $result_vehiculos = $conexion->query("SELECT tipo, COUNT(*) as cantidad FROM vehiculos GROUP BY tipo");
    echo "<div class='section'>";
    echo "<div class='section-title'>DISTRIBUCIÓN POR TIPO DE VEHÍCULO</div>";
    echo "<table>";
    echo "<tr><th>Tipo de Vehículo</th><th>Cantidad</th></tr>";
    while ($row = $result_vehiculos->fetch_assoc()) {
        echo "<tr><td>" . ucfirst($row['tipo']) . "</td><td>" . $row['cantidad'] . "</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Estado de reportes
    $result_reportes = $conexion->query("SELECT estado, COUNT(*) as cantidad FROM reportes_carnet_perdido GROUP BY estado");
    echo "<div class='section'>";
    echo "<div class='section-title'>ESTADO DE REPORTES DE CARNET</div>";
    echo "<table>";
    echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
    while ($row = $result_reportes->fetch_assoc()) {
        echo "<tr><td>" . ucfirst($row['estado']) . "</td><td>" . $row['cantidad'] . "</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "</body></html>";
    exit();
}

// Obtener estadísticas para el dashboard
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

// 4. ESTADO DE REPORTES DE CARNET
$query_estado_reportes = "SELECT estado, COUNT(*) as cantidad FROM reportes_carnet_perdido GROUP BY estado";
$result_estado_reportes = $conexion->query($query_estado_reportes);
$estado_reportes = [];
while ($row = $result_estado_reportes->fetch_assoc()) {
    $estado_reportes[$row['estado']] = $row['cantidad'];
}

// 5. ACCESOS RECIENTES (simplificado)
$query_accesos_recientes = "SELECT DATE(fecha_hora) as fecha, COUNT(*) as cantidad 
                           FROM registros_acceso 
                           WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                           GROUP BY DATE(fecha_hora) 
                           ORDER BY fecha DESC 
                           LIMIT 5";
$result_accesos_recientes = $conexion->query($query_accesos_recientes);
$accesos_por_dia = [];
while ($row = $result_accesos_recientes->fetch_assoc()) {
    $accesos_por_dia[$row['fecha']] = $row['cantidad'];
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
        .print-only { display: none; }
        
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .container { max-width: 100% !important; }
            .card { border: 1px solid #000 !important; box-shadow: none !important; }
            .chart-container { break-inside: avoid; }
            .stats-card { break-inside: avoid; }
        }
        
        .stats-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            border-left: 4px solid;
        }
        
        .stats-card-1 { border-left-color: #3498db; }
        .stats-card-2 { border-left-color: #2ecc71; }
        .stats-card-3 { border-left-color: #e74c3c; }
        .stats-card-4 { border-left-color: #f39c12; }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .chart-small {
            height: 250px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-description {
            font-size: 0.75rem;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Header para impresión -->
        <div class="print-only text-center mb-4">
            <h2>REPORTE DEL SISTEMA DE PARQUEADERO</h2>
            <p class="text-muted">Universidad de la Guajira - Generado el <?= date('d/m/Y H:i') ?></p>
            <hr>
        </div>

        <!-- Header normal -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-chart-bar me-2"></i>Reportes y Estadísticas</h2>
                        <p class="text-muted mb-0">Resumen ejecutivo del sistema</p>
                    </div>
                    <div>
                        <a href="?exportar=excel" class="btn btn-success me-2">
                            <i class="fas fa-file-excel me-1"></i> Exportar Excel
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Principales -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card stats-card-1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?= $usuarios_totales ?></div>
                                <div class="stat-label">USUARIOS TOTALES</div>
                                <div class="stat-description">Personas registradas en el sistema</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card stats-card-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?= $vehiculos_totales ?></div>
                                <div class="stat-label">VEHÍCULOS REGISTRADOS</div>
                                <div class="stat-description">Total de vehículos en el sistema</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card stats-card-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?= $solicitudes_pendientes ?></div>
                                <div class="stat-label">SOLICITUDES PENDIENTES</div>
                                <div class="stat-description">Esperando aprobación</div>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card stats-card-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?= $reportes_pendientes ?></div>
                                <div class="stat-label">REPORTES PENDIENTES</div>
                                <div class="stat-description">Carnets perdidos por atender</div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-id-card fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primera Fila de Gráficos -->
        <div class="row">
            <!-- Distribución de Usuarios -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5 class="section-title">
                        <i class="fas fa-users me-2"></i>Distribución de Usuarios por Tipo
                    </h5>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="chartTiposUsuario" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="mt-3">
                                <h6>Leyenda:</h6>
                                <?php foreach ($tipos_usuario as $tipo => $cantidad): ?>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: <?= getChartColor($tipo) ?>"></div>
                                    <span><?= ucfirst($tipo) ?>: <?= $cantidad ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <strong>Interpretación:</strong> Muestra la distribución de usuarios aprobados por categoría (estudiantes, profesores, administrativos).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tipos de Vehículo -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5 class="section-title">
                        <i class="fas fa-car me-2"></i>Tipos de Vehículo Registrados
                    </h5>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="chartTiposVehiculo" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="mt-3">
                                <h6>Resumen:</h6>
                                <?php foreach ($tipos_vehiculo as $tipo => $cantidad): ?>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #3498db"></div>
                                    <span><?= ucfirst($tipo) ?>: <?= $cantidad ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <strong>Interpretación:</strong> Representa los diferentes tipos de vehículos que tienen acceso al parqueadero.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda Fila de Gráficos -->
        <div class="row">
            <!-- Estado de Reportes -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5 class="section-title">
                        <i class="fas fa-id-card me-2"></i>Estado de Reportes de Carnet
                    </h5>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="chartEstadoReportes" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="mt-3">
                                <h6>Estados:</h6>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #dc3545"></div>
                                    <span>Pendiente: <?= $estado_reportes['pendiente'] ?? 0 ?></span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #ffc107"></div>
                                    <span>En Proceso: <?= $estado_reportes['en_proceso'] ?? 0 ?></span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #198754"></div>
                                    <span>Resuelto: <?= $estado_reportes['resuelto'] ?? 0 ?></span>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <strong>Interpretación:</strong> Muestra el estado actual de los reportes de carnet perdido y su progreso de atención.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos Recientes -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h5 class="section-title">
                        <i class="fas fa-sign-in-alt me-2"></i>Accesos Últimos 5 Días
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Accesos</th>
                                    <th>Tendencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($accesos_por_dia)): ?>
                                    <?php 
                                    $accesos_array = array_values($accesos_por_dia);
                                    $max_acceso = max($accesos_array);
                                    ?>
                                    <?php foreach ($accesos_por_dia as $fecha => $cantidad): ?>
                                        <tr>
                                            <td><?= date('d/m', strtotime($fecha)) ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= $cantidad ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar" style="width: <?= ($cantidad / $max_acceso) * 100 ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle me-1"></i> No hay datos de accesos recientes
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>Interpretación:</strong> Muestra la actividad diaria de acceso al parqueadero en los últimos días.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Ejecutivo (solo en impresión) -->
        <div class="print-only mt-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">RESUMEN EJECUTIVO</h5>
                </div>
                <div class="card-body">
                    <p><strong>Fecha de generación:</strong> <?= date('d/m/Y H:i') ?></p>
                    <p><strong>Total de usuarios activos:</strong> <?= $usuarios_totales ?> personas</p>
                    <p><strong>Vehículos registrados:</strong> <?= $vehiculos_totales ?> unidades</p>
                    <p><strong>Solicitudes pendientes:</strong> <?= $solicitudes_pendientes ?> por revisar</p>
                    <p><strong>Reportes activos:</strong> <?= $reportes_pendientes ?> por atender</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para colores consistentes
        function getChartColor(label) {
            const colors = {
                'estudiante': '#3498db',
                'profesor': '#2ecc71', 
                'administrativo': '#e74c3c',
                'otro': '#f39c12'
            };
            return colors[label.toLowerCase()] || '#95a5a6';
        }

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
                    backgroundColor: [<?php 
                        $colors = [];
                        foreach ($tipos_usuario as $tipo => $cantidad) {
                            $colors[] = "'" . getChartColor($tipo) . "'";
                        }
                        echo implode(', ', $colors);
                    ?>]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
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
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
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
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Función auxiliar para colores
function getChartColor($tipo) {
    $colors = [
        'estudiante' => '#3498db',
        'profesor' => '#2ecc71',
        'administrativo' => '#e74c3c', 
        'otro' => '#f39c12'
    ];
    return $colors[strtolower($tipo)] ?? '#95a5a6';
}
?>