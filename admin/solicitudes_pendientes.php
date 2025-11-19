<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador - CORREGIDO
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: ' . BASE_URL . 'paneles/administrador.php');
    exit();
}

// Obtener solicitudes pendientes
$query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.marca, v.color 
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          WHERE u.estado = 'pendiente' 
          ORDER BY u.fecha_registro DESC";
$result = $conn->query($query);

// Guardar los resultados en un array para usarlos múltiples veces
$solicitudes = [];
while ($solicitud = $result->fetch_assoc()) {
    $solicitudes[] = $solicitud;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Pendientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .solicitud-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .solicitud-header {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        .solicitud-body {
            padding: 15px;
        }
        .solicitud-info {
            margin-bottom: 8px;
        }
        .solicitud-info strong {
            color: #495057;
            min-width: 100px;
            display: inline-block;
        }
        .btn-group-mobile {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-group-mobile .btn {
            flex: 1;
            min-width: 80px;
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
            .table-desktop {
                display: none;
            }
            .cards-mobile {
                display: block;
            }
        }
        @media (min-width: 769px) {
            .cards-mobile {
                display: none;
            }
            .table-desktop {
                display: block;
            }
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-clock"></i> Solicitudes Pendientes de Aprobación</h2>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <!-- Vista de tabla para escritorio -->
        <div class="table-responsive table-desktop">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Vehículo</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $solicitud): ?>
                    <tr>
                        <td><?= $solicitud['id'] ?></td>
                        <td><?= htmlspecialchars($solicitud['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($solicitud['cedula']) ?></td>
                        <td><?= htmlspecialchars($solicitud['email']) ?></td>
                        <td><span class="badge bg-info"><?= $solicitud['tipo'] ?></span></td>
                        <td>
                            <?php if ($solicitud['tipo_vehiculo']): ?>
                                <?= $solicitud['tipo_vehiculo'] ?> (<?= $solicitud['placa'] ?>)
                            <?php else: ?>
                                <span class="text-muted">Sin vehículo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])) ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="ver_solicitud.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Aprobar
                                </a>
                                <a href="rechazar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i> Rechazar
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vista de tarjetas para móviles -->
        <div class="cards-mobile">
            <?php foreach ($solicitudes as $solicitud): ?>
            <div class="solicitud-card">
                <div class="solicitud-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Solicitud #<?= $solicitud['id'] ?></h6>
                        <span class="badge bg-info"><?= $solicitud['tipo'] ?></span>
                    </div>
                </div>
                <div class="solicitud-body">
                    <div class="solicitud-info">
                        <strong>Nombre:</strong> <?= htmlspecialchars($solicitud['nombre_completo']) ?>
                    </div>
                    <div class="solicitud-info">
                        <strong>Cédula:</strong> <?= htmlspecialchars($solicitud['cedula']) ?>
                    </div>
                    <div class="solicitud-info">
                        <strong>Email:</strong> <?= htmlspecialchars($solicitud['email']) ?>
                    </div>
                    <div class="solicitud-info">
                        <strong>Vehículo:</strong> 
                        <?php if ($solicitud['tipo_vehiculo']): ?>
                            <?= $solicitud['tipo_vehiculo'] ?> (<?= $solicitud['placa'] ?>)
                        <?php else: ?>
                            <span class="text-muted">Sin vehículo</span>
                        <?php endif; ?>
                    </div>
                    <div class="solicitud-info">
                        <strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])) ?>
                    </div>
                    <div class="mt-3">
                        <div class="btn-group-mobile">
                            <a href="ver_solicitud.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Aprobar
                            </a>
                            <a href="rechazar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-danger">
                                <i class="fas fa-times"></i> Rechazar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($solicitudes)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> No hay solicitudes pendientes en este momento.
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>