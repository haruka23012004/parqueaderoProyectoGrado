<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

if (!estaAutenticado() || $_SESSION['rol_nombre'] !== 'administrador_principal') {
    header('Location: ../acceso/login.php');
    exit();
}

// Procesar respuesta del administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $respuesta = trim($_POST['respuesta']);
    $nuevo_estado = $_POST['nuevo_estado'];
    
    try {
        $query = "UPDATE pedidos_ayuda 
                  SET respuesta_admin = ?, estado = ?, fecha_actualizacion = NOW() 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $respuesta, $nuevo_estado, $pedido_id);
        
        if ($stmt->execute()) {
            $mensaje = '✅ Respuesta enviada correctamente';
            $tipo_mensaje = 'success';
        } else {
            throw new Exception('Error al actualizar: ' . $stmt->error);
        }
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener todos los pedidos de ayuda
$query_pedidos = "SELECT pa.*, u.nombre_completo, u.cedula 
                  FROM pedidos_ayuda pa
                  INNER JOIN usuarios_parqueadero u ON pa.usuario_id = u.id
                  ORDER BY 
                    CASE pa.urgencia 
                        WHEN 'alta' THEN 1
                        WHEN 'media' THEN 2
                        WHEN 'baja' THEN 3
                    END,
                    pa.fecha_creacion DESC";
$result_pedidos = $conn->query($query_pedidos);
$pedidos = $result_pedidos->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$total_pendientes = array_filter($pedidos, function($p) { return $p['estado'] === 'pendiente'; });
$total_altos = array_filter($pedidos, function($p) { return $p['urgencia'] === 'alta'; });
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de Ayuda - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-life-ring me-2"></i>Pedidos de Ayuda - Panel de Administración</h4>
                    </div>
                    <div class="card-body">
                        <!-- Estadísticas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h5>Pendientes</h5>
                                        <h2><?= count($total_pendientes) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5>Alta Urgencia</h5>
                                        <h2><?= count($total_altos) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h5>Total</h5>
                                        <h2><?= count($pedidos) ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Pedidos -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Urgencia</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($pedido['nombre_completo']) ?></strong><br>
                                            <small class="text-muted"><?= $pedido['cedula'] ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= ucfirst($pedido['tipo_problema']) ?></span><br>
                                            <small><?= $pedido['subcategoria'] ?></small>
                                        </td>
                                        <td>
                                            <div style="max-width: 300px;">
                                                <small><?= htmlspecialchars($pedido['descripcion']) ?></small>
                                                <?php if ($pedido['respuesta_admin']): ?>
                                                    <div class="mt-2 p-2 bg-light rounded">
                                                        <strong>Respuesta:</strong><br>
                                                        <?= htmlspecialchars($pedido['respuesta_admin']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $urgencia_class = [
                                                'alta' => 'danger',
                                                'media' => 'warning', 
                                                'baja' => 'success'
                                            ][$pedido['urgencia']];
                                            ?>
                                            <span class="badge bg-<?= $urgencia_class ?>">
                                                <?= ucfirst($pedido['urgencia']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $estado_class = [
                                                'pendiente' => 'warning',
                                                'en_proceso' => 'info',
                                                'resuelto' => 'success',
                                                'cerrado' => 'secondary'
                                            ][$pedido['estado']];
                                            ?>
                                            <span class="badge bg-<?= $estado_class ?>">
                                                <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?></small>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#responderModal"
                                                    data-pedido-id="<?= $pedido['id'] ?>"
                                                    data-descripcion="<?= htmlspecialchars($pedido['descripcion']) ?>"
                                                    data-estado-actual="<?= $pedido['estado'] ?>">
                                                <i class="fas fa-reply"></i> Responder
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Responder -->
    <div class="modal fade" id="responderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Responder Pedido de Ayuda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pedido_id" id="modal-pedido-id">
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción del Problema:</label>
                            <textarea class="form-control" id="modal-descripcion" rows="3" readonly></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nuevo Estado:</label>
                            <select class="form-select" name="nuevo_estado" id="modal-estado">
                                <option value="en_proceso">En Proceso</option>
                                <option value="resuelto">Resuelto</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Respuesta/Mensaje:</label>
                            <textarea class="form-control" name="respuesta" rows="4" 
                                      placeholder="Escribe tu respuesta, instrucciones o comentarios..."></textarea>
                            <div class="form-text">
                                Puedes usar respuestas rápidas: "Estamos trabajando en ello", "Problema resuelto", "Necesitamos más información", etc.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="responder" class="btn btn-primary">Enviar Respuesta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal
        const responderModal = document.getElementById('responderModal');
        responderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const pedidoId = button.getAttribute('data-pedido-id');
            const descripcion = button.getAttribute('data-descripcion');
            const estadoActual = button.getAttribute('data-estado-actual');
            
            document.getElementById('modal-pedido-id').value = pedidoId;
            document.getElementById('modal-descripcion').value = descripcion;
            document.getElementById('modal-estado').value = estadoActual;
        });
    </script>
</body>
</html>