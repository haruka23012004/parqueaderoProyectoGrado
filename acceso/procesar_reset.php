<?php
session_start();
require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $nueva_password = $_POST['password'] ?? '';
    
    try {
        // Validaciones
        if (empty($token) || empty($nueva_password)) {
            throw new Exception('Datos incompletos.');
        }
        
        if (strlen($nueva_password) < 8) {
            throw new Exception('La contraseña debe tener al menos 8 caracteres.');
        }
        
        // Verificar token válido
        $query = "SELECT id FROM empleados WHERE token_reset = ? AND token_expiracion > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('El enlace de recuperación ha expirado o es inválido.');
        }
        
        $usuario = $result->fetch_assoc();
        
        // Hashear nueva contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizar contraseña y limpiar token
        $query_update = "UPDATE empleados SET password_hash = ?, token_reset = NULL, token_expiracion = NULL WHERE id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("si", $password_hash, $usuario['id']);
        
        if ($stmt_update->execute()) {
            $_SESSION['mensaje'] = '✅ Tu contraseña ha sido restablecida correctamente. Ahora puedes iniciar sesión.';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: login.php');
            exit();
        } else {
            throw new Exception('Error al actualizar la contraseña.');
        }
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = '❌ Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }
}