<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador - CORREGIDO
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

$usuario_id = $_GET['id'];
$query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.marca, v.color, v.detalle_tipo 
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Solicitud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Detalles de Solicitud #<?= $solicitud['id'] ?></h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($solicitud['nombre_completo']) ?></p>
                        <p><strong>Cédula:</strong> <?= htmlspecialchars($solicitud['cedula']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($solicitud['email']) ?></p>
                        <p><strong>Tipo:</strong> <?= ucfirst($solicitud['tipo']) ?></p>
                        
                        <?php if ($solicitud['codigo_universitario']): ?>
                        <p><strong>Código Universitario:</strong> <?= $solicitud['codigo_universitario'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['facultad']): ?>
                        <p><strong>Facultad:</strong> <?= $solicitud['facultad'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['semestre']): ?>
                        <p><strong>Semestre:</strong> <?= $solicitud['semestre'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['programa_academico']): ?>
                        <p><strong>Programa:</strong> <?= $solicitud['programa_academico'] ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Fecha Registro:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Información del Vehículo</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Tipo:</strong> <?= ucfirst($solicitud['tipo_vehiculo']) ?></p>
                        
                        <?php if ($solicitud['detalle_tipo']): ?>
                        <p><strong>Detalle:</strong> <?= $solicitud['detalle_tipo'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['placa']): ?>
                        <p><strong>Placa:</strong> <?= $solicitud['placa'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['marca']): ?>
                        <p><strong>Marca:</strong> <?= $solicitud['marca'] ?></p>
                        <?php endif; ?>
                        
                        <?php if ($solicitud['color']): ?>
                        <p><strong>Color:</strong> <?= $solicitud['color'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($solicitud['foto_usuario']): ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Foto del Usuario</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="../<?= $solicitud['foto_usuario'] ?>" alt="Foto del usuario" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="solicitudes_pendientes.php" class="btn btn-secondary">Volver</a>
            <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-success">Aprobar Usuario</a>
            <a href="rechazar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-danger">Rechazar Usuario</a>
        </div>
    </div>
</body>
</html>