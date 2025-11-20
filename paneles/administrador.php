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
            height: 100%;
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
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
            }
            .sidebar-overlay.show {
                display: block;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .quick-actions .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .stats-section .row > div {
                margin-bottom: 15px;
            }
        }
        
        @media (min-width: 769px) {
            .sidebar {
                position: fixed;
                height: 100vh;
                overflow-y: auto;
            }
            .main-content {
                margin-left: 25%;
            }
        }
        
        @media (min-width: 992px) {
            .main-content {
                margin-left: 16.666667%;
            }
        }
        
        .mobile-menu-btn {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay para móviles -->
    <div class="sidebar-overlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-xl-2 sidebar">
                <div class="p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 d-md-none">
                        <h4 class="text-white">Menú</h4>
                        <button type="button" class="btn-close btn-close-white" id="closeSidebar"></button>
                    </div>
                    <h4 class="text-center mb-4 d-none d-md-block">Sistema Parqueadero</h4>
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
                            <a href="../admin/vehiculos.php" class="nav-link">
                                <i class="bi bi-car-front"></i> Vehículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/empleados.php" class="nav-link">
                                <i class="bi bi-person-badge"></i> Empleados
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="bi bi-graph-up"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/contraseñas_perdidas.php" class="nav-link">
                                <i class="bi bi-gear"></i> Restablecimiento de contraseñas
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-xl-10 main-content">
                <!-- Navbar superior -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button class="navbar-toggler mobile-menu-btn" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <span class="navbar-brand">Panel de Administración</span>
                    <div class="d-flex align-items-center">

                    <!-- Botón Reportes Carnet Perdido -->
                    <a href="../admin/reportes_carnet_perdido.php" class="btn btn-outline-warning me-2">
                        <i class="fas fa-id-card"></i> 
                        <span class="d-none d-sm-inline">Reportes Carnet Perdido</span>
                    </a>

                    <!-- Botón Cambiar Contraseña -->
                    <a href="cambiar_password.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-key"></i> 
                        <span class="d-none d-sm-inline">Cambiar Contraseña</span>
                    </a>

                    <!-- Botón Cerrar Sesión -->
                    <a href="../acceso/logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> 
                        <span class="d-none d-sm-inline">Cerrar Sesión</span>
                    </a>
                            </div>
                </div>
            </nav>

                <div class="container-fluid p-3 p-md-4">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_login']) ?></h2>
                            <p class="text-muted">Panel principal de administración del sistema</p>
                        </div>
                    </div>

                    <!-- Cards de Resumen -->
                    <div class="row g-3 g-md-4 stats-section">
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card card-dashboard bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Usuarios Registrados</h5>
                                    <p class="card-text display-5"><?= $total_usuarios ?></p>
                                    <a href="../admin/usuarios_aprobados.php" class="text-white">Ver todos</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card card-dashboard bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Solicitudes Pendientes</h5>
                                    <p class="card-text display-5"><?= $solicitudes_pendientes ?></p>
                                    <a href="../admin/solicitudes_pendientes.php" class="text-dark">Revisar</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-3">
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
                        <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card card-dashboard" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title">Vehículos Hoy</h5>
                                <p class="card-text display-5"><?= $vehiculos_hoy ?></p>
                                <div class="stats-small" style="color: rgba(255,255,255,0.8);">
                                    Registrados hoy
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Acciones Rápidas - Versión Mejorada -->
<div class="row mt-4 mt-md-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning"></i> Acciones Rápidas</h5>
            </div>
            <div class="card-body quick-actions">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="../registro_empleados.php" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> Nuevo Empleado
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="../admin/solicitudes_pendientes.php" class="btn btn-warning w-100">
                            <i class="bi bi-clock-history"></i> Revisar Solicitudes
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="vigilante.php" class="btn btn-info w-100" target="_blank">
                            <i class="bi bi-eye"></i> Panel Vigilante
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="../admin/pedidos_ayuda.php" class="btn btn-danger w-100">
                            <i class="bi bi-question-circle"></i> Pedidos de Ayuda
                        </a>
                    </div>
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
                                        <div class="col-md-6 mb-3 mb-md-0">
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
    <script>
        // Control del sidebar en dispositivos móviles
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const closeBtn = document.getElementById('closeSidebar');
            
            if (menuBtn) {
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            // Ajustar altura del contenido principal
            function adjustMainContentHeight() {
                const navbar = document.querySelector('.navbar');
                const mainContent = document.querySelector('.main-content');
                
                if (navbar && mainContent) {
                    const navbarHeight = navbar.offsetHeight;
                    mainContent.style.minHeight = `calc(100vh - ${navbarHeight}px)`;
                }
            }
            
            // Ejecutar al cargar y al redimensionar
            adjustMainContentHeight();
            window.addEventListener('resize', adjustMainContentHeight);
        });
    </script>
</body>
</html>