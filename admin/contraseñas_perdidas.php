<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_nombre'] !== 'administrador_principal') {
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

$mensaje = '';
$error = '';
$solicitudes = [];
$filtro_estado = $_GET['estado'] ?? 'pendiente';

// Procesar actualización de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_solicitud') {
    try {
        $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
        $estado = $_POST['estado'] ?? 'pendiente';
        $notas_administrador = trim($_POST['notas_administrador'] ?? '');

        if ($solicitud_id === 0) {
            throw new Exception("ID de solicitud inválido");
        }

        $sql = "UPDATE solicitudes_recuperacion 
                SET estado = ?, 
                    notas_administrador = ?, 
                    administrador_asignado = ?,
                    fecha_resolucion = " . ($estado === 'resuelta' ? "NOW()" : "NULL") . "
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        $admin_nombre = $_SESSION['usuario_login'] ?? 'Administrador';
        mysqli_stmt_bind_param($stmt, "sssi", $estado, $notas_administrador, $admin_nombre, $solicitud_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Solicitud actualizada correctamente";
        } else {
            throw new Exception("Error al actualizar la solicitud");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener solicitudes según filtro
$sql_where = "";
if ($filtro_estado === 'pendiente') {
    $sql_where = "WHERE estado = 'pendiente'";
} elseif ($filtro_estado === 'todas') {
    $sql_where = "";
} else {
    $sql_where = "WHERE estado = '$filtro_estado'";
}

$sql = "SELECT * FROM solicitudes_recuperacion 
        $sql_where 
        ORDER BY fecha_solicitud DESC 
        LIMIT 50";
$result = mysqli_query($conn, $sql);
$solicitudes = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .solicitud-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .solicitud-pendiente {
            border-left-color: #dc3545;
        }
        .solicitud-proceso {
            border-left-color: #ffc107;
        }
        .solicitud-resuelta {
            border-left-color: #28a745;
        }
        .estado-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-key me-2"></i>Gestión de Solicitudes de Contraseña</h1>
                    <div class="btn-group">
                        <a href="?estado=pendiente" class="btn btn-<?= $filtro_estado === 'pendiente' ? 'primary' : 'outline-primary' ?>">
                            Pendientes
                        </a>
                        <a href="?estado=en_proceso" class="btn btn-<?= $filtro_estado === 'en_proceso' ? 'warning' : 'outline-warning' ?>">
                            En Proceso
                        </a>
                        <a href="?estado=resuelta" class="btn btn-<?= $filtro_estado === 'resuelta' ? 'success' : 'outline-success' ?>">
                            Resueltas
                        </a>
                        <a href="?estado=todas" class="btn btn-<?= $filtro_estado === 'todas' ? 'secondary' : 'outline-secondary' ?>">
                            Todas
                        </a>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php
                                    $count_pendientes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM solicitudes_recuperacion WHERE estado = 'pendiente'"))['total'];
                                    echo $count_pendientes;
                                    ?>
                                </h5>
                                <p class="card-text">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php
                                    $count_proceso = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM solicitudes_recuperacion WHERE estado = 'en_proceso'"))['total'];
                                    echo $count_proceso;
                                    ?>
                                </h5>
                                <p class="card-text">En Proceso</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php
                                    $count_resueltas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM solicitudes_recuperacion WHERE estado = 'resuelta'"))['total'];
                                    echo $count_resueltas;
                                    ?>
                                </h5>
                                <p class="card-text">Resueltas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php
                                    $count_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM solicitudes_recuperacion"))['total'];
                                    echo $count_total;
                                    ?>
                                </h5>
                                <p class="card-text">Total</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Solicitudes -->
                <?php if (!empty($solicitudes)): ?>
                    <div class="row">
                        <?php foreach ($solicitudes as $solicitud): ?>
                            <div class="col-md-6">
                                <div class="card solicitud-card <?= 'solicitud-' . $solicitud['estado'] ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>Solicitud #<?= $solicitud['id'] ?></strong>
                                        <span class="badge 
                                            <?= $solicitud['estado'] == 'pendiente' ? 'bg-danger' : '' ?>
                                            <?= $solicitud['estado'] == 'en_proceso' ? 'bg-warning' : '' ?>
                                            <?= $solicitud['estado'] == 'resuelta' ? 'bg-success' : '' ?> estado-badge">
                                            <?= ucfirst($solicitud['estado']) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nombre:</strong> <?= htmlspecialchars($solicitud['nombre_completo']) ?></p>
                                        <p><strong>Cédula:</strong> <?= htmlspecialchars($solicitud['cedula']) ?></p>
                                        <p><strong>Contacto:</strong> <?= htmlspecialchars($solicitud['contacto']) ?></p>
                                        <p><strong>Usuario:</strong> <?= htmlspecialchars($solicitud['usuario_sistema'] ?? 'No especificado') ?></p>
                                        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></p>
                                        <p><strong>Problema:</strong> <?= htmlspecialchars($solicitud['descripcion_problema']) ?></p>
                                        
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="accion" value="actualizar_solicitud">
                                            <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Estado:</label>
                                                <select name="estado" class="form-select">
                                                    <option value="pendiente" <?= $solicitud['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                    <option value="en_proceso" <?= $solicitud['estado'] === 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                                                    <option value="resuelta" <?= $solicitud['estado'] === 'resuelta' ? 'selected' : '' ?>>Resuelta</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notas del Administrador:</label>
                                                <textarea name="notas_administrador" class="form-control" rows="3" 
                                                          placeholder="Agregue notas sobre la resolución..."><?= htmlspecialchars($solicitud['notas_administrador'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save me-2"></i>Actualizar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay solicitudes <?= $filtro_estado === 'todas' ? '' : $filtro_estado ?> en este momento.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>