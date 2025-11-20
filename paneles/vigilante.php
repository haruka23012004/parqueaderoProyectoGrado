<?php

require '../includes/auth.php';
require '../includes/conexion.php';

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Obtener estadísticas para el vigilante
$conexion = $conn;

// Accesos de hoy
$hoy = date('Y-m-d');
$query_accesos_hoy = "SELECT COUNT(*) as total FROM registros_acceso WHERE DATE(fecha_hora) = '$hoy'";
$result_accesos_hoy = $conexion->query($query_accesos_hoy);
$accesos_hoy = $result_accesos_hoy->fetch_assoc()['total'];

// Vehículos actualmente en el parqueadero (simulación)
$query_vehiculos_actuales = "SELECT COUNT(DISTINCT vehiculo_id) as total FROM registros_acceso 
                            WHERE DATE(fecha_hora) = '$hoy' 
                            AND tipo_movimiento = 'entrada'";
$result_vehiculos_actuales = $conexion->query($query_vehiculos_actuales);
$vehiculos_actuales = $result_vehiculos_actuales->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Vigilante - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-dashboard {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .btn-action {
            padding: 20px;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
        .quick-actions .col {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-section p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">¡Bienvenido, <?= htmlspecialchars($_SESSION['usuario_login']) ?>!</h2>
                            <p class="mb-0">Panel de control para gestión de accesos al parqueadero</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-light text-dark fs-6 p-2">
                                <i class="fas fa-shield-alt me-2"></i>Vigilante
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card card-dashboard bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-car fa-3x mb-3"></i>
                        <h5 class="card-title">Accesos Hoy</h5>
                        <p class="stats-number"><?= $accesos_hoy ?></p>
                        <small>Registros del día</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-dashboard bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-parking fa-3x mb-3"></i>
                        <h5 class="card-title">Vehículos Actuales</h5>
                        <p class="stats-number"><?= $vehiculos_actuales ?></p>
                        <small>En parqueadero</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-dashboard bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3"></i>
                        <h5 class="card-title">Turno Activo</h5>
                        <?php
                        // Crear objeto DateTime con zona horaria de Colombia
                        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
                        ?>
                        <p class="stats-number"><?= $fecha->format('H:i') ?></p>
                        <small>Hora actual Colombia</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h4>
                    </div>
                    <div class="card-body">
                        <div class="row quick-actions">
                            <!-- Acceso con QR -->
                            <div class="col-md-4">
                                <a href="../vigilante/lector_qr.php" class="btn btn-primary btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-qrcode fa-3x mb-3"></i>
                                    <span>Lector QR</span>
                                    <small class="mt-1">Escanear códigos de acceso</small>
                                </a>
                            </div>

                            <!-- Registrar Salidas -->
                            <div class="col-md-4">
                                <a href="../vigilante/salidas.php" class="btn btn-danger btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-sign-out-alt fa-3x mb-3"></i>
                                    <span>Registrar Salidas</span>
                                    <small class="mt-1">Escanear QR para salidas</small>
                                </a>
                            </div>
                            <!-- Registro Manual -->
                            <div class="col-md-4">
                                <a href="../vigilante/registro_manual.php" class="btn btn-warning btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-keyboard fa-3x mb-3"></i>
                                    <span>Registro Manual</span>
                                    <small class="mt-1">Ingreso sin QR</small>
                                </a>
                            </div>
                            
                            <!-- Consultar Vehículos -->
                            <div class="col-md-4">
                                <a href="../vigilante/consultar_vehiculos.php" class="btn btn-info btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-search fa-3x mb-3"></i>
                                    <span>Consultar Vehículos</span>
                                    <small class="mt-1">Buscar por placa</small>
                                </a>
                            </div>
                            
                            <!-- Reportes Diarios -->
                            <div class="col-md-4">
                                <a href="../vigilante/reportes_diarios.php" class="btn btn-success btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                    <span>Reportes</span>
                                    <small class="mt-1">Estadísticas del día</small>
                                </a>
                            </div>
                            
                            
                            
                            <!-- Ayuda -->
                            <div class="col-md-4">
                                <a href="../vigilante/ayuda.php" class="btn btn-secondary btn-action w-100 d-flex flex-column align-items-center">
                                    <i class="fas fa-question-circle fa-3x mb-3"></i>
                                    <span>Ayuda</span>
                                    <small class="mt-1">Manual y soporte</small>
                                </a>
                            </div>
                            

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Turno -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Turno</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Vigilante:</strong> <?= htmlspecialchars($_SESSION['usuario_login']) ?><br>
                                <strong>Fecha:</strong> <?= date('d/m/Y') ?><br>
                                <strong>Hora de ingreso:</strong> <?= date('H:i:s') ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Accesos registrados hoy:</strong> <?= $accesos_hoy ?><br>
                                <strong>Vehículos en parqueadero:</strong> <?= $vehiculos_actuales ?><br>
                                <strong>Estado del sistema:</strong> <span class="badge bg-success">Operativo</span>
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