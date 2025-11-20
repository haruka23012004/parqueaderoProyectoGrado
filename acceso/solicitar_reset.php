<?php
session_start();
require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        // Verificar si el email existe
        $query = "SELECT id, nombre_completo, usuario_login FROM empleados WHERE email = ? AND estado = 'activo'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Por seguridad, no revelamos si el email existe o no
            $_SESSION['mensaje'] = '✅ Si el email existe en nuestro sistema, recibirás un enlace de recuperación.';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: olvido_password.php');
            exit();
        }
        
        $usuario = $result->fetch_assoc();
        
        // Generar token único
        $token = bin2hex(random_bytes(50));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guardar token en la base de datos
        $query_update = "UPDATE empleados SET token_reset = ?, token_expiracion = ? WHERE id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("ssi", $token, $expiracion, $usuario['id']);
        
        if ($stmt_update->execute()) {
            // En un sistema real, aquí enviarías el email
            // Por ahora, mostraremos el enlace directamente para testing
            
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/parqueaderoProyectoGrado/acceso/reset_password.php?token=" . $token;
            
            // SIMULACIÓN DE ENVÍO DE EMAIL - En producción, usarías PHPMailer o similar
            error_log("=== EMAIL DE RECUPERACIÓN ===");
            error_log("Para: " . $email);
            error_log("Asunto: Recuperación de Contraseña - Sistema Parqueadero");
            error_log("Enlace: " . $reset_link);
            error_log("========================");
            
            $_SESSION['mensaje'] = '🔗 <strong>Enlace de recuperación generado:</strong><br>'
                . '<a href="' . $reset_link . '" class="alert-link">' . $reset_link . '</a><br><br>'
                . '<small>En un sistema real, este enlace se enviaría por email.</small>';
            $_SESSION['tipo_mensaje'] = 'info';
            
        } else {
            throw new Exception('Error al generar el enlace de recuperación');
        }
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = '❌ Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
    
    header('Location: olvido_password.php');
    exit();
}