<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID de solicitud no válido");
}

$usuario_id = (int)$_GET['id'];

// Obtener datos de la solicitud (AHORA INCLUYENDO foto_vehiculo)
$query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.marca, v.color, v.detalle_tipo, v.foto_vehiculo 
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Error: Solicitud no encontrada");
}

$solicitud = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Solicitud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header { font-weight: bold; }
        .img-usuario { max-height: 250px; object-fit: cover; }
        .img-vehiculo { max-height: 250px; object-fit: cover; }
        .photo-container { margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Detalles de Solicitud #<?= htmlspecialchars($solicitud['id']) ?></h2>
        
        <div class="row">
            <!-- Información Personal -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($solicitud['nombre_completo']) ?></p>
                        <p><strong>Cédula:</strong> <?= htmlspecialchars($solicitud['cedula']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($solicitud['email']) ?></p>
                        <p><strong>Tipo:</strong> <?= htmlspecialchars(ucfirst($solicitud['tipo'] ?? 'No especificado')) ?></p>
                        
                        <?php if (!empty($solicitud['codigo_universitario'])): ?>
                        <p><strong>Código Universitario:</strong> <?= htmlspecialchars($solicitud['codigo_universitario']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['facultad'])): ?>
                        <p><strong>Facultad:</strong> <?= htmlspecialchars($solicitud['facultad']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['semestre'])): ?>
                        <p><strong>Semestre:</strong> <?= htmlspecialchars($solicitud['semestre']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['programa_academico'])): ?>
                        <p><strong>Programa:</strong> <?= htmlspecialchars($solicitud['programa_academico']) ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Estado:</strong> 
                            <span class="badge bg-<?= $solicitud['estado'] === 'aprobado' ? 'success' : ($solicitud['estado'] === 'rechazado' ? 'danger' : 'warning') ?>">
                                <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>
                            </span>
                        </p>
                        
                        <p><strong>Fecha Registro:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Información del Vehículo -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Información del Vehículo</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Tipo:</strong> <?= htmlspecialchars(ucfirst($solicitud['tipo_vehiculo'] ?? 'No especificado')) ?></p>
                        
                        <?php if (!empty($solicitud['detalle_tipo'])): ?>
                        <p><strong>Detalle:</strong> <?= htmlspecialchars($solicitud['detalle_tipo']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['placa'])): ?>
                        <p><strong>Placa:</strong> <?= htmlspecialchars($solicitud['placa']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['marca'])): ?>
                        <p><strong>Marca:</strong> <?= htmlspecialchars($solicitud['marca']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($solicitud['color'])): ?>
                        <p><strong>Color:</strong> <?= htmlspecialchars($solicitud['color']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOTOS - Ahora en una sección separada -->
        <div class="row">
            <!-- Foto del Usuario -->
            <?php if (!empty($solicitud['foto_usuario'])): ?>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📷 Foto del Usuario</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="../<?= htmlspecialchars($solicitud['foto_usuario']) ?>" 
                             alt="Foto del usuario" 
                             class="img-fluid rounded img-usuario">
                        <p class="mt-2 text-muted">Foto de perfil del solicitante</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Foto del Vehículo -->
            <?php if (!empty($solicitud['foto_vehiculo'])): ?>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">🚗 Foto del Vehículo</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="../<?= htmlspecialchars($solicitud['foto_vehiculo']) ?>" 
                             alt="Foto del vehículo" 
                             class="img-fluid rounded img-vehiculo">
                        <p class="mt-2 text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Verifique que la placa sea visible y coincida con la información proporcionada
                        </p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">🚗 Foto del Vehículo</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>No se ha subido foto del vehículo</strong>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción -->
        <div class="mt-3">
            <a href="solicitudes_pendientes.php" class="btn btn-secondary">← Volver</a>
            
            <?php if ($solicitud['estado'] === 'pendiente'): ?>
            <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>" 
               class="btn btn-success" 
               onclick="return confirm('¿Estás seguro de aprobar esta solicitud?')">
               ✅ Aprobar Usuario
            </a>
            <a href="rechazar_usuario.php?id=<?= $solicitud['id'] ?>" 
               class="btn btn-danger"
               onclick="return confirm('¿Estás seguro de rechazar esta solicitud?')">
               ❌ Rechazar Usuario
            </a>
            <?php else: ?>
            <span class="badge bg-<?= $solicitud['estado'] === 'aprobado' ? 'success' : 'danger' ?> fs-6">
                Solicitud <?= ucfirst($solicitud['estado']) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>