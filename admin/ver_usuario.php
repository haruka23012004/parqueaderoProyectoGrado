<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /parqueaderoProyectoGrado/paneles/administrador.php');
    exit();
}

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID de usuario no válido");
}

$usuario_id = (int)$_GET['id'];

// Obtener datos del usuario aprobado (con información de aprobación)
$query = "SELECT 
            u.*, 
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo, 
            v.foto_vehiculo,
            e.nombre_completo as nombre_aprobador,
            u.fecha_aprobacion,
            u.fecha_registro
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          LEFT JOIN empleados e ON u.aprobado_por = e.id
          WHERE u.id = ? AND u.estado = 'aprobado'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Error: Usuario no encontrado o no está aprobado");
}

$usuario = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Usuario - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-header { 
            font-weight: bold; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .img-usuario { 
            max-height: 300px; 
            object-fit: cover;
            border: 3px solid #dee2e6;
            border-radius: 10px;
        }
        .img-vehiculo { 
            max-height: 300px; 
            object-fit: cover;
            border: 3px solid #dee2e6;
            border-radius: 10px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .badge-estado {
            font-size: 0.9rem;
            padding: 8px 12px;
        }
        .qr-code {
            max-width: 200px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #3498db;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3498db;
        }

        /* ESTILOS PARA IMPRESIÓN DEL CARNET */
        @media print {
            body * {
                visibility: hidden;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            #carnetImprimir,
            #carnetImprimir * {
                visibility: visible !important;
            }
            
            #carnetImprimir {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: white !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .no-print,
            .modal,
            .navbar,
            .container,
            .btn {
                display: none !important;
            }
        }

        .carnet-container {
            display: none;
            max-width: 400px;
            margin: 20px auto;
            border: 2px solid #333;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .carnet-header {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            text-align: center;
            border-radius: 13px 13px 0 0;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }

        .carnet-body {
            padding: 25px;
            background: white;
            color: #333;
            min-height: 450px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .carnet-photo {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            border: 3px solid #667eea;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
        }

        .carnet-qr {
            width: 120px;
            height: 120px;
            margin: 15px auto;
            display: block;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            background: white;
        }

        .carnet-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .carnet-field {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .carnet-field strong {
            color: #667eea;
            font-weight: 600;
        }

        .carnet-footer {
            background: rgba(0,0,0,0.2);
            padding: 12px;
            text-align: center;
            border-radius: 0 0 13px 13px;
            font-size: 0.8rem;
            border-top: 2px solid rgba(255,255,255,0.3);
        }

        .carnet-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .carnet-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .user-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .user-type {
            background: #667eea;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-user-check me-2"></i>
                Usuario Aprobado: <?= htmlspecialchars($usuario['nombre_completo']) ?>
            </h2>
            <a href="usuarios_aprobados.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Usuarios Aprobados
            </a>
        </div>

        <!-- Contenido principal (tu código existente) -->
        <div class="row">
            <!-- Columna Izquierda: Información Personal y Universitaria -->
            <div class="col-md-6">
                <!-- Información Personal -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-id-card"></i>
                        <h5 class="mb-0">Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-section">
                            <p><strong><i class="fas fa-user me-2"></i>Nombre:</strong> <?= htmlspecialchars($usuario['nombre_completo']) ?></p>
                            <p><strong><i class="fas fa-id-card me-2"></i>Cédula:</strong> <?= htmlspecialchars($usuario['cedula']) ?></p>
                            <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                            <p><strong><i class="fas fa-tag me-2"></i>Tipo de Usuario:</strong> 
                                <span class="badge bg-info"><?= htmlspecialchars(ucfirst($usuario['tipo'])) ?></span>
                            </p>
                        </div>

                        <!-- Información Universitaria -->
                        <?php if (in_array($usuario['tipo'], ['estudiante', 'profesor', 'administrativo'])): ?>
                        <div class="info-section">
                            <h6 class="text-primary border-bottom pb-2">
                                <i class="fas fa-university me-2"></i>Información Universitaria
                            </h6>
                            <?php if (!empty($usuario['codigo_universitario'])): ?>
                            <p><strong>Código Universitario:</strong> <?= htmlspecialchars($usuario['codigo_universitario']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($usuario['facultad'])): ?>
                            <p><strong>Facultad:</strong> <?= htmlspecialchars($usuario['facultad']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($usuario['programa_academico'])): ?>
                            <p><strong>Programa Académico:</strong> <?= htmlspecialchars($usuario['programa_academico']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($usuario['semestre'])): ?>
                            <p><strong>Semestre:</strong> <?= htmlspecialchars($usuario['semestre']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información de Aprobación -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calendar-check"></i>
                        <h5 class="mb-0">Información de Aprobación</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <strong>Registro:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>
                            </div>
                            <div class="timeline-item">
                                <strong>Aprobación:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($usuario['fecha_aprobacion'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($usuario['nombre_aprobador'])): ?>
                        <p class="mt-3"><strong>Aprobado por:</strong> <?= htmlspecialchars($usuario['nombre_aprobador']) ?></p>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <span class="badge bg-success badge-estado">
                                <i class="fas fa-check-circle me-1"></i>APROBADO
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Vehículo y Fotos -->
            <div class="col-md-6">
                <!-- Información del Vehículo -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-car"></i>
                        <h5 class="mb-0">Información del Vehículo</h5>
                    </div>
                    <div class="card-body">
                        <p><strong><i class="fas fa-car-side me-2"></i>Tipo:</strong> 
                            <?= htmlspecialchars(ucfirst($usuario['tipo_vehiculo'] ?? 'No especificado')) ?>
                        </p>
                        
                        <?php if (!empty($usuario['detalle_tipo'])): ?>
                        <p><strong><i class="fas fa-info-circle me-2"></i>Detalle:</strong> <?= htmlspecialchars($usuario['detalle_tipo']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($usuario['placa'])): ?>
                        <p><strong><i class="fas fa-list-alt me-2"></i>Placa:</strong> 
                            <span class="badge bg-secondary"><?= htmlspecialchars($usuario['placa']) ?></span>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($usuario['marca'])): ?>
                        <p><strong><i class="fas fa-industry me-2"></i>Marca:</strong> <?= htmlspecialchars($usuario['marca']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($usuario['color'])): ?>
                        <p><strong><i class="fas fa-palette me-2"></i>Color:</strong> <?= htmlspecialchars($usuario['color']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fotos -->
                <div class="row">
                    <!-- Foto del Usuario -->
                    <?php if (!empty($usuario['foto_usuario'])): ?>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-camera"></i>
                                <h6 class="mb-0">Foto del Usuario</h6>
                            </div>
                            <div class="card-body text-center">
                                <img src="../<?= htmlspecialchars($usuario['foto_usuario']) ?>" 
                                     alt="Foto del usuario" 
                                     class="img-fluid img-usuario">
                                <p class="mt-2 text-muted small">Foto de perfil</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Foto del Vehículo -->
                    <?php if (!empty($usuario['foto_vehiculo'])): ?>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-car-side"></i>
                                <h6 class="mb-0">Foto del Vehículo</h6>
                            </div>
                            <div class="card-body text-center">
                                <img src="../<?= htmlspecialchars($usuario['foto_vehiculo']) ?>" 
                                     alt="Foto del vehículo" 
                                     class="img-fluid img-vehiculo">
                                <p class="mt-2 text-muted small">Placa visible</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Código QR -->
                <?php if (!empty($usuario['qr_code'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-qrcode"></i>
                        <h6 class="mb-0">Código QR de Acceso</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="../<?= htmlspecialchars($usuario['qr_code']) ?>" 
                             alt="Código QR" 
                             class="qr-code">
                        <p class="mt-2 text-muted small">Escanea para verificar acceso</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="mt-4 d-flex gap-2 flex-wrap">
            <a href="usuarios_aprobados.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i> Editar Usuario
            </a>
            <button type="button" onclick="mostrarVistaPrevia()" class="btn btn-success">
                <i class="fas fa-id-card me-1"></i> Imprimir Carnet
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                <i class="fas fa-trash me-1"></i> Eliminar Usuario
            </button>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer y eliminará todos los datos asociados.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="usuarios_aprobados.php?eliminar=<?= $usuario['id'] ?>" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- CARNET PARA IMPRIMIR (siempre en el DOM pero oculto) -->
    <div class="carnet-container" id="carnetImprimir">
        <!-- Encabezado del Carnet -->
        <div class="carnet-header">
            <div class="carnet-title">UNIVERSIDAD DE LA GUAJIRA</div>
            <div class="carnet-subtitle">Sistema de Parqueadero</div>
        </div>
        
        <!-- Cuerpo del Carnet -->
        <div class="carnet-body">
            <!-- Foto del Usuario -->
            <div>
                <?php if (!empty($usuario['foto_usuario'])): ?>
                <img src="../<?= htmlspecialchars($usuario['foto_usuario']) ?>" 
                     alt="Foto" class="carnet-photo">
                <?php else: ?>
                <div class="carnet-photo bg-light d-flex align-items-center justify-content-center">
                    <i class="fas fa-user fa-3x text-muted"></i>
                </div>
                <?php endif; ?>
                
                <!-- Información Principal -->
                <div class="carnet-info">
                    <div class="user-name"><?= htmlspecialchars($usuario['nombre_completo']) ?></div>
                    <div class="user-type"><?= htmlspecialchars(ucfirst($usuario['tipo'])) ?></div>
                    <div class="carnet-field">
                        <span>Cédula:</span>
                        <strong><?= htmlspecialchars($usuario['cedula']) ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Información del Vehículo -->
            <div>
                <div class="carnet-info">
                    <h6 style="color: #667eea; border-bottom: 1px solid #667eea; padding-bottom: 5px; margin-bottom: 15px;">Vehículo</h6>
                    <div class="carnet-field">
                        <span>Placa:</span>
                        <strong><?= htmlspecialchars($usuario['placa'] ?? 'N/A') ?></strong>
                    </div>
                    <div class="carnet-field">
                        <span>Tipo:</span>
                        <strong><?= htmlspecialchars(ucfirst($usuario['tipo_vehiculo'] ?? 'N/A')) ?></strong>
                    </div>
                    <div class="carnet-field">
                        <span>Color:</span>
                        <strong><?= htmlspecialchars($usuario['color'] ?? 'N/A') ?></strong>
                    </div>
                </div>
                
                <!-- Código QR -->
                <?php if (!empty($usuario['qr_code'])): ?>
                <div class="carnet-info">
                    <img src="../<?= htmlspecialchars($usuario['qr_code']) ?>" 
                         alt="QR Code" class="carnet-qr">
                    <p class="small text-muted mt-1">Código de acceso al parqueadero</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pie del Carnet -->
        <div class="carnet-footer">
            <p class="mb-0">Válido hasta: <?= date('d/m/Y', strtotime('+1 year')) ?> | Carnet No: <?= $usuario['id'] ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function mostrarVistaPrevia() {
        // Mostrar el carnet para vista previa
        var carnet = document.getElementById('carnetImprimir');
        carnet.style.display = 'block';
        
        // Pequeña pausa para que se renderice
        setTimeout(function() {
            window.print();
        }, 500);
    }

    // Ocultar el carnet después de imprimir
    window.addEventListener('afterprint', function() {
        var carnet = document.getElementById('carnetImprimir');
        if (carnet) {
            carnet.style.display = 'none';
        }
    });

    // También ocultar si se cancela la impresión
    window.addEventListener('beforeprint', function() {
        console.log('Preparando para imprimir carnet...');
    });

    // Asegurarse de que el carnet esté oculto al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        var carnet = document.getElementById('carnetImprimir');
        if (carnet) {
            carnet.style.display = 'none';
        }
    });
    </script>
</body>
</html>