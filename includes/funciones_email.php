<?php
/**
 * Funciones de email para el sistema de recuperación de contraseña
 */

function enviarEmailRecuperacion($email, $nombre, $usuario, $token) {
    require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración automática para Hostinger
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->SMTPAuth = false;
        $mail->Port = 25;
        
        // Email FROM automático
        $dominio = $_SERVER['HTTP_HOST'];
        $mail->setFrom('sistema@' . $dominio, 'Sistema de Parqueadero - Uniguajira');
        $mail->CharSet = 'UTF-8';
        
        // Destinatarios
        $mail->addAddress($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - Sistema Parqueadero Uniguajira';
        
        $resetLink = "https://" . $dominio . BASE_URL . "/acceso/reset_password.php?token=" . $token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                    <h2>Universidad de La Guajira</h2>
                    <h3>Sistema de Parqueadero</h3>
                </div>
                
                <div style='padding: 20px; background: #f8f9fa;'>
                    <h3>Recuperación de Contraseña</h3>
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Has solicitado restablecer tu contraseña para el usuario: <strong>{$usuario}</strong></p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' style='background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;'>
                            Restablecer Contraseña
                        </a>
                    </div>
                    
                    <p><strong>⚠️ Este enlace expirará en 1 hora.</strong></p>
                    
                    <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0;'>
                        <strong>Seguridad:</strong> Si no solicitaste este cambio, por favor ignora este mensaje.
                    </div>
                    
                    <p>Saludos cordiales,<br>
                    <strong>Equipo de Sistema de Parqueadero</strong><br>
                    Universidad de La Guajira</p>
                </div>
                
                <div style='background: #343a40; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                    © " . date('Y') . " Universidad de La Guajira - Sistema de Parqueadero
                </div>
            </div>
        ";
        
        $mail->AltBody = "Recuperación de contraseña para {$usuario}. Visita: {$resetLink} (Expira en 1 hora)";

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}