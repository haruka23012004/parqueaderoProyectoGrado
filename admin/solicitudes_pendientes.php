<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador - CORREGIDO
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /../paneles/administrador.php');
    exit();
}

// Obtener solicitudes pendientes
$query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.marca, v.color 
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          WHERE u.estado = 'pendiente' 
          ORDER BY u.fecha_registro DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Pendientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        <div class="table-responsive">
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
                    <?php while ($solicitud = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $solicitud['id'] ?></td>
                        <td><?= htmlspecialchars($solicitud['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($solicitud['cedula']) ?></td>
                        <td><?= htmlspecialchars($solicitud['email']) ?></td>
                        <td><span class="badge bg-info"><?= $solicitud['tipo'] ?></span></td>
                        <td><?= $solicitud['tipo_vehiculo'] ?> (<?= $solicitud['placa'] ?>)</td>
                        <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])) ?></td>
                        <td>
                            <a href="ver_solicitud.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Aprobar
                            </a>
                            <a href="rechazar_usuario.php?id=<?= $solicitud['id'] ?>" class="btn btn-sm btn-danger">
                                <i class="fas fa-times"></i> Rechazar
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($result->num_rows == 0): ?>
            <div class="alert alert-info text-center">
                No hay solicitudes pendientes en este momento.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>