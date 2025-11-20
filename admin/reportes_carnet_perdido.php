<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /parqueaderoProyectoGrado/paneles/administrador.php');
    exit();
}

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $reporte_id = (int)$_POST['reporte_id'];
    $nuevo_estado = $_POST['estado'];
    $respuesta = trim($_POST['respuesta']);
    $admin_id = $_SESSION['usuario_id'];
    
    $query = "UPDATE reportes_carnet_perdido 
              SET estado = ?, respuesta_admin = ?, fecha_respuesta = NOW(), administrador_id = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $nuevo_estado, $respuesta, $admin_id, $reporte_id);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Estado actualizado correctamente';
    } else {
        $_SESSION['error'] = 'Error al actualizar el estado';
    }
    
    header('Location: reportes_carnet_perdido.php');
    exit();
}

// Obtener reportes
$query = "SELECT r.*, e.nombre_completo as admin_nombre 
          FROM reportes_carnet_perdido r 
          LEFT JOIN empleados e ON r.administrador_id = e.id 
          ORDER BY r.fecha_reporte DESC";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Carnet Perdido - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-pendiente { background-color: #dc3545; }
        .badge-en_proceso { background-color: #ffc107; color: #000; }
        .badge-resuelto { background-color: #198754; }
        
        .reporte-card {
            border-left: 4px solid;
            margin-bottom: 15px;
        }
        .reporte-pendiente { border-left-color: #dc3545; }
        .reporte-en_proceso { border-left-color: #ffc107; }
        .reporte-resuelto { border-left-color: #198754; }
        
        .contacto-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-id-card me-2"></i>
                Reportes de Carnet Perdido
            </h2>
            <span class="badge bg-primary">
                Total: <?php echo $resultado->num_rows; ?>
            </span>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($reporte = $resultado->fetch_assoc()): ?>
                        <div class="card reporte-card reporte-<?php echo $reporte['estado']; ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($reporte['nombre_completo']); ?>
                                            <span class="badge badge-<?php echo $reporte['estado']; ?>">
                                                <?php echo ucfirst($reporte['estado']); ?>
                                            </span>
                                        </h5>
                                        
                                        <p class="card-text">
                                            <strong>Cédula:</strong> <?php echo htmlspecialchars($reporte['cedula']); ?><br>
                                            <strong>Fecha Reporte:</strong> <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?>
                                        </p>
                                        
                                        <div class="contacto-info">
                                            <strong>Contacto:</strong> 
                                            <?php echo htmlspecialchars($reporte['contacto']); ?>
                                            <small class="text-muted">
                                                (<?php echo $reporte['tipo_contacto'] == 'telefono' ? 'Teléfono' : 'Correo'; ?>)
                                            </small>
                                        </div>
                                        
                                        <p class="card-text">
                                            <strong>Descripción:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                                        </p>
                                        
                                        <?php if (!empty($reporte['respuesta_admin'])): ?>
                                            <div class="alert alert-info mt-2">
                                                <strong>Respuesta del Administrador:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($reporte['respuesta_admin'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    Respondido por: <?php echo htmlspecialchars($reporte['admin_nombre'] ?? 'Sistema'); ?>
                                                    el <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_respuesta'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <form method="POST" class="mb-3">
                                            <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Actualizar Estado</strong></label>
                                                <select name="estado" class="form-select" required>
                                                    <option value="pendiente" <?php echo $reporte['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="en_proceso" <?php echo $reporte['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                                    <option value="resuelto" <?php echo $reporte['estado'] == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Respuesta/Comentarios</label>
                                                <textarea name="respuesta" class="form-control" rows="3" 
                                                          placeholder="Ingrese comentarios o instrucciones para el usuario..."><?php echo htmlspecialchars($reporte['respuesta_admin'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <button type="submit" name="actualizar_estado" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-1"></i> Actualizar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No hay reportes de carnet perdido</h4>
                        <p class="mb-0">No se han encontrado reportes de carnet perdido en el sistema.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-expand textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>