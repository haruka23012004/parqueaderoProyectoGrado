<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/conexion.php';

// Verificar autenticación y rol
if (!estaAutenticado() || $_SESSION['rol_nombre'] !== 'administrador_principal') {
    header('Location: ../acceso/login.php');
    exit();
}

// Obtener estadísticas para el dashboard
$conexion = $conn;

// Contar solicitudes pendientes
$query_pendientes = "SELECT COUNT(*) as total FROM usuarios_parqueadero WHERE estado = 'pendiente'";
$result_pendientes = $conexion->query($query_pendientes);
$solicitudes_pendientes = $result_pendientes->fetch_assoc()['total'];

// Contar usuarios totales
$query_usuarios = "SELECT COUNT(*) as total FROM usuarios_parqueadero";
$result_usuarios = $conexion->query($query_usuarios);
$total_usuarios = $result_usuarios->fetch_assoc()['total'];

// Contar vehículos de hoy
$hoy = date('Y-m-d');
$query_vehiculos_hoy = "SELECT COUNT(*) as total FROM registros_acceso WHERE DATE(fecha_hora) = '$hoy'";
$result_vehiculos_hoy = $conexion->query($query_vehiculos_hoy);
$vehiculos_hoy = $result_vehiculos_hoy->fetch_assoc()['total'];

// CONTAR TOTAL DE VEHÍCULOS REGISTRADOS EN EL SISTEMA
$query_total_vehiculos = "SELECT COUNT(*) as total FROM vehiculos";
$result_total_vehiculos = $conexion->query($query_total_vehiculos);
$total_vehiculos = $result_total_vehiculos->fetch_assoc()['total'];

// Opcional: Contar vehículos por tipo
$query_vehiculos_tipo = "SELECT tipo, COUNT(*) as cantidad FROM vehiculos GROUP BY tipo";
$result_vehiculos_tipo = $conexion->query($query_vehiculos_tipo);
$vehiculos_por_tipo = [];
while ($row = $result_vehiculos_tipo->fetch_assoc()) {
    $vehiculos_por_tipo[$row['tipo']] = $row['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-dashboard {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            background-color:rgb(16, 17, 17);
            border-radius: 5px;
            padding: 15px;
        }
        .sidebar {
            background-color: #212529;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .badge-notification {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .vehicle-type-badge {
            font-size: 0.75rem;
            margin: 2px;
        }
        .stats-small {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar vh-100 position-fixed">
                <div class="p-4">
                    <h4 class="text-center mb-4">Sistema Parqueadero</h4>
                    <div class="user-info mb-4 text-center">
                        <p class="mb-1"><strong><?= htmlspecialchars($_SESSION['usuario_login']) ?></strong></p>
                        <span class="badge bg-primary">Administrador</span>
                    </div>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="administrador.php" class="nav-link active">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/solicitudes_pendientes.php" class="nav-link position-relative">
                                <i class="bi bi-clock-history"></i> Solicitudes
                                <?php if ($solicitudes_pendientes > 0): ?>
                                <span class="badge bg-danger badge-notification"><?= $solicitudes_pendientes ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/usuarios_aprobados.php" class="nav-link">
                                <i class="bi bi-people"></i> Usuarios Aprobados
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/usuarios_rechazados.php" class="nav-link">
                                <i class="bi bi-person-x"></i> Usuarios Rechazados
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="bi bi-car-front"></i> Vehículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="bi bi-graph-up"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="bi bi-gear"></i> Configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">
                        <span class="navbar-brand">Panel de Administración</span>
                        <div class="d-flex">
                            <a href="../acceso/logout.php" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid p-4">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_login']) ?></h2>
                            <p class="text-muted">Panel principal de administración del sistema</p>
                        </div>
                    </div>

                    <!-- Cards de Resumen -->
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="card card-dashboard bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Usuarios Registrados</h5>
                                    <p class="card-text display-5"><?= $total_usuarios ?></p>
                                    <a href="../admin/usuarios_aprobados.php" class="text-white">Ver todos</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-dashboard bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Solicitudes Pendientes</h5>
                                    <p class="card-text display-5"><?= $solicitudes_pendientes ?></p>
                                    <a href="../admin/solicitudes_pendientes.php" class="text-dark">Revisar</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-dashboard bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Vehículos</h5>
                                    <p class="card-text display-5"><?= $total_vehiculos ?></p>
                                    <div class="stats-small">
                                        
                                    </div>
                                    <a href="../admin/vehiculos.php" class="text-white">Ver vehículos</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección adicional -->
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Acciones Rápidas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex gap-3">
                                        <a href="../registro_empleados.php" class="btn btn-primary">
                                            <i class="bi bi-person-plus"></i> Registrar Nuevo Empleado
                                        </a>
                                        <a href="../admin/solicitudes_pendientes.php" class="btn btn-warning">
                                            <i class="bi bi-clock-history"></i> Revisar Solicitudes
                                        </a>
                                        <a href="#" class="btn btn-outline-info">
                                            <i class="bi bi-graph-up"></i> Ver Estadísticas
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Estadísticas de Vehículos -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-car-front"></i> Estadísticas de Vehículos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Distribución por Tipo</h6>
                                            <?php if (!empty($vehiculos_por_tipo)): ?>
                                                <ul class="list-group">
                                                    <?php foreach ($vehiculos_por_tipo as $tipo => $cantidad): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?= ucfirst($tipo) ?>
                                                            <span class="badge bg-primary rounded-pill"><?= $cantidad ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted">No hay vehículos registrados</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Resumen General</h6>
                                            <div class="alert alert-info">
                                                <p class="mb-1"><strong>Total de vehículos registrados:</strong> <?= $total_vehiculos ?></p>
                                                <p class="mb-1"><strong>Vehículos hoy:</strong> <?= $vehiculos_hoy ?></p>
                                                <p class="mb-0"><strong>Solicitudes pendientes:</strong> <?= $solicitudes_pendientes ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>