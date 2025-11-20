<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensaje('danger', 'Método no permitido');
    header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    setMensaje('danger', 'El correo electrónico es obligatorio');
    header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
    exit();
}

// Validar dominios de email permitidos
$dominiosPermitidos = ['gmail.com', 'uniguajira.edu.co'];
$dominio_usuario = strtolower(substr(strrchr($email, "@"), 1));

if (!in_array($dominio_usuario, $dominiosPermitidos)) {
    setMensaje('danger', 'Solo se permiten correos de @gmail.com y @uniguajira.edu.co');
    header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
    exit();
}

try {
    // Verificar si el email existe en la base de datos
    $sql = "SELECT id, nombre_completo, usuario_login, estado FROM empleados WHERE email = ? AND estado = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        // Por seguridad, no revelamos si el email existe o no
        setMensaje('success', 'Si el email existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    $empleado = mysqli_fetch_assoc($result);

    // Generar token único
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en la base de datos
    $sqlUpdate = "UPDATE empleados SET token_reset = ?, token_expiracion = ? WHERE email = ?";
    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "sss", $token, $expiracion, $email);
    
    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception('Error al guardar el token de recuperación');
    }

    // Configurar PHPMailer
    require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // CONFIGURACIÓN UNIVERSAL PARA HOSTINGER - SIN CREDENCIALES PERSONALES
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->SMTPAuth = false;
        $mail->Port = 25;
        
        // Email FROM automático - NO requiere configuración manual
        $dominio_servidor = $_SERVER['HTTP_HOST'];
        $mail->setFrom('sistema@' . $dominio_servidor, 'Sistema de Parqueadero - Uniguajira');

        // Destinatarios - ENVÍA A CUALQUIER EMAIL VÁLIDO (@gmail.com o @uniguajira.edu.co)
        $mail->addAddress($email, $empleado['nombre_completo']);

        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - Sistema Parqueadero Uniguajira';
        
        $resetLink = "https://" . $dominio_servidor . BASE_URL . "/acceso/reset_password.php?token=" . $token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                    <h2>Universidad de La Guajira</h2>
                    <h3>Sistema de Parqueadero</h3>
                </div>
                
                <div style='padding: 20px; background: #f8f9fa;'>
                    <h3>Recuperación de Contraseña</h3>
                    <p>Hola <strong>{$empleado['nombre_completo']}</strong>,</p>
                    <p>Has solicitado restablecer tu contraseña para el usuario: <strong>{$empleado['usuario_login']}</strong></p>
                    
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
        
        $mail->AltBody = "Recuperación de contraseña para {$empleado['usuario_login']}. Visita: {$resetLink} (Expira en 1 hora)";

        if ($mail->send()) {
            setMensaje('success', 'Se ha enviado un enlace de recuperación a tu correo electrónico. Revisa tu bandeja de entrada.');
            header('Location: ' . BASE_URL . '/acceso/login.php');
            exit();
        } else {
            throw new Exception('No se pudo enviar el email');
        }

    } catch (Exception $e) {
        // Si falla el email, mostrar mensaje alternativo
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        
        // En desarrollo: mostrar enlace directo
        if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
            setMensaje('info', "EN MODO DESARROLLO: <a href='reset_password.php?token=$token' style='color: #007bff;'>Haz clic aquí para restablecer contraseña</a>");
            header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
        } else {
            setMensaje('warning', 'El sistema de email no está disponible temporalmente. Por favor, contacta al administrador.');
            header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
        }
        exit();
    }

} catch (Exception $e) {
    error_log('Error en recuperación: ' . $e->getMessage());
    setMensaje('danger', 'Error al procesar la solicitud');
    header('Location: ' . BASE_URL . '/acceso/olvido_password.php');
    exit();
}