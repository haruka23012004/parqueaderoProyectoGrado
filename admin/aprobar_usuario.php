<?php
// === CARGAR AUTOLOAD MANUALMENTE - PRIMERA LÍNEA ===
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

require '../includes/auth.php';
require '../includes/conexion.php';
require '../includes/funciones.php';

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
$admin_id = $_SESSION['usuario_id'];

// Verificar que el usuario existe y está pendiente
$check_query = "SELECT * FROM usuarios_parqueadero WHERE id = ? AND estado = 'pendiente'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $usuario_id);
$check_stmt->execute();
$usuario = $check_stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header('Location: solicitudes_pendientes.php?error=Usuario no encontrado o ya procesado');
    exit();
}

try {
    // 1. Generar código QR
    $qr_data = "PARQ:" . $usuario_id . ":" . md5($usuario['cedula'] . time());
    $qr_directory = "../qr_codes/";
    
    // Crear directorio si no existe
    if (!file_exists($qr_directory)) {
        mkdir($qr_directory, 0755, true);
    }
    
    $qr_path = $qr_directory . "user_" . $usuario_id . ".png";
    
    if (!generateQRCode($qr_data, $qr_path)) {
        throw new Exception("Error al generar el código QR");
    }
    
    // 2. Actualizar base de datos
    $update_query = "UPDATE usuarios_parqueadero 
                    SET estado = 'aprobado', 
                        qr_code = ?,
                        acceso_activo = TRUE,
                        fecha_aprobacion = NOW(),
                        aprobado_por = ?
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $relative_qr_path = "qr_codes/user_" . $usuario_id . ".png";
    $update_stmt->bind_param("sii", $relative_qr_path, $admin_id, $usuario_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Error al actualizar la base de datos: " . $update_stmt->error);
    }
    
    header('Location: solicitudes_pendientes.php?msg=Usuario aprobado correctamente');
    exit();
    
} catch (Exception $e) {
    // Eliminar QR si se creó pero falló el proceso
    if (isset($qr_path) && file_exists($qr_path)) {
        unlink($qr_path);
    }
    
    header('Location: solicitudes_pendientes.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>