<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensaje('danger', 'Método no permitido');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

$token = trim($_POST['token'] ?? '');
$nueva_password = $_POST['nueva_password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

if (empty($token) || empty($nueva_password) || empty($confirmar_password)) {
    setMensaje('danger', 'Todos los campos son obligatorios');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

if ($nueva_password !== $confirmar_password) {
    setMensaje('danger', 'Las contraseñas no coinciden');
    header('Location: ' . BASE_URL . '/acceso/reset_password.php?token=' . urlencode($token));
    exit();
}

if (strlen($nueva_password) < 8) {
    setMensaje('danger', 'La contraseña debe tener al menos 8 caracteres');
    header('Location: ' . BASE_URL . '/acceso/reset_password.php?token=' . urlencode($token));
    exit();
}

try {
    // Verificar que el token sigue siendo válido
    $sql = "SELECT id, usuario_login FROM empleados 
            WHERE token_reset = ? AND token_expiracion > NOW() AND estado = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        setMensaje('danger', 'El enlace de recuperación ha expirado o es inválido');
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    $empleado = mysqli_fetch_assoc($result);

    // Hashear la nueva contraseña
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

    // Actualizar la contraseña y limpiar el token
    $sqlUpdate = "UPDATE empleados 
                  SET password_hash = ?, token_reset = NULL, token_expiracion = NULL, 
                      ultimo_acceso = NOW() 
                  WHERE id = ?";
    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "si", $password_hash, $empleado['id']);
    
    if (mysqli_stmt_execute($stmtUpdate)) {
        setMensaje('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    } else {
        throw new Exception('Error al actualizar la contraseña');
    }

} catch (Exception $e) {
    error_log('Error al cambiar contraseña: ' . $e->getMessage());
    setMensaje('danger', 'Error al actualizar la contraseña');
    header('Location: ' . BASE_URL . '/acceso/reset_password.php?token=' . urlencode($token));
    exit();
}