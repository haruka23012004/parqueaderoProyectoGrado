<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setMensaje('danger', 'Token de recuperación inválido');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

// Verificar si el token es válido y no ha expirado
try {
    $sql = "SELECT id, nombre_completo, usuario_login, token_expiracion 
            FROM empleados 
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

} catch (Exception $e) {
    error_log('Error verificando token: ' . $e->getMessage());
    setMensaje('danger', 'Error al validar el enlace de recuperación');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="header-section">
                <h1><i class="fas fa-lock me-2"></i>Nueva Contraseña</h1>
                <p class="mb-0">Hola, <?php echo htmlspecialchars($empleado['nombre_completo']); ?></p>
            </div>

            <div class="p-4">
                <?php mostrarMensaje(); ?>

                <form method="POST" action="procesar_nueva_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="mb-3">
                        <label for="nueva_password" class="form-label">
                            <i class="fas fa-key me-2"></i>Nueva Contraseña
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="nueva_password" name="nueva_password" 
                               placeholder="Ingresa tu nueva contraseña" required
                               minlength="8">
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="form-text">
                            La contraseña debe tener al menos 8 caracteres.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmar_password" class="form-label">
                            <i class="fas fa-check-circle me-2"></i>Confirmar Contraseña
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="confirmar_password" name="confirmar_password" 
                               placeholder="Confirma tu nueva contraseña" required>
                        <div class="form-text" id="passwordMatch"></div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Guardar Nueva Contraseña
                        </button>
                        <a href="login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nuevaPassword = document.getElementById('nueva_password');
            const confirmarPassword = document.getElementById('confirmar_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            function checkPasswordStrength(password) {
                let strength = 0;
                if (password.length >= 8) strength += 25;
                if (/[A-Z]/.test(password)) strength += 25;
                if (/[0-9]/.test(password)) strength += 25;
                if (/[^A-Za-z0-9]/.test(password)) strength += 25;

                passwordStrength.style.width = strength + '%';
                if (strength < 50) {
                    passwordStrength.style.backgroundColor = '#dc3545';
                } else if (strength < 75) {
                    passwordStrength.style.backgroundColor = '#ffc107';
                } else {
                    passwordStrength.style.backgroundColor = '#28a745';
                }
            }

            function checkPasswordMatch() {
                if (confirmarPassword.value === '') {
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'form-text';
                    return;
                }

                if (nuevaPassword.value === confirmarPassword.value) {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.className = 'form-text text-success';
                    submitBtn.disabled = false;
                } else {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.className = 'form-text text-danger';
                    submitBtn.disabled = true;
                }
            }

            nuevaPassword.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });

            confirmarPassword.addEventListener('input', checkPasswordMatch);
        });
    </script>
</body>
</html>