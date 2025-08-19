<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador 
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: solicitudes_pendientes.php?error=ID no proporcionado');
    exit();
}

$usuario_id = $_GET['id'];

// Verificar que el usuario existe y está pendiente
$check_query = "SELECT * FROM usuarios_parqueadero WHERE id = ? AND estado = 'pendiente'";
$check_stmt = $conexion->prepare($check_query);
$check_stmt->bind_param("i", $usuario_id);
$check_stmt->execute();
$usuario = $check_stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header('Location: solicitudes_pendientes.php?error=Usuario no encontrado o ya procesado');
    exit();
}

// Si se envió el formulario con observaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observaciones = trim($_POST['observaciones']);
    
    $update_query = "UPDATE usuarios_parqueadero 
                    SET estado = 'rechazado', 
                        observaciones = ?
                    WHERE id = ?";
    
    $update_stmt = $conexion->prepare($update_query);
    $update_stmt->bind_param("si", $observaciones, $usuario_id);
    
    if ($update_stmt->execute()) {
        header('Location: solicitudes_pendientes.php?msg=Usuario rechazado correctamente');
        exit();
    } else {
        header('Location: solicitudes_pendientes.php?error=Error al rechazar usuario');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechazar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Rechazar Solicitud</h4>
                    </div>
                    <div class="card-body">
                        <p>Está a punto de rechazar la solicitud de <strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong> (Cédula: <?= htmlspecialchars($usuario['cedula']) ?>).</p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Razón del rechazo (opcional):</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Explique brevemente la razón del rechazo"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="solicitudes_pendientes.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>