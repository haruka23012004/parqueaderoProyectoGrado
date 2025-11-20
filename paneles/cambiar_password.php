<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!estaAutenticado()) {
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

$mensaje = '';
$error = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $password_actual = $_POST['password_actual'] ?? '';
        $nueva_password = $_POST['nueva_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';

        // Validaciones
        if (empty($password_actual) || empty($nueva_password) || empty($confirmar_password)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if ($nueva_password !== $confirmar_password) {
            throw new Exception("Las nuevas contraseñas no coinciden");
        }

        if (strlen($nueva_password) < 8) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres");
        }

        // Verificar contraseña actual
        $sql = "SELECT password_hash FROM empleados WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $empleado = mysqli_fetch_assoc($result);

        if (!$empleado || !password_verify($password_actual, $empleado['password_hash'])) {
            throw new Exception("La contraseña actual es incorrecta");
        }

        // Hashear nueva contraseña
        $nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        // Actualizar contraseña en la base de datos
        $sql_update = "UPDATE empleados SET password_hash = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $nueva_password_hash, $_SESSION['usuario_id']);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $mensaje = "Contraseña cambiada exitosamente. Será redirigido al login para iniciar sesión con su nueva contraseña.";
            
            // Redirigir al LOGIN después de 2 segundos
            echo '<script>
                setTimeout(function() {
                    window.location.href = "' . BASE_URL . '/acceso/login.php";
                }, 2000);
            </script>';
        } else {
            throw new Exception("Error al actualizar la contraseña");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .password-container {
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
        .user-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-container">
            <div class="header-section">
                <h1><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h1>
                <p class="mb-0">Por seguridad, debe cambiar su contraseña temporal</p>
            </div>

            <div class="p-4">
                <!-- Información del usuario -->
                <div class="user-info">
                    <h6><i class="fas fa-user me-2"></i>Información del Usuario</h6>
                    <p class="mb-1"><strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['usuario_login'] ?? '') ?></p>
                    <p class="mb-0"><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['rol_nombre'] ?? '') ?></p>
                </div>

                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($mensaje) ?>
                        <div class="spinner-border spinner-border-sm ms-2" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="password_actual" class="form-label required-label">
                            <i class="fas fa-key me-2"></i>Contraseña Actual
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="password_actual" name="password_actual" 
                               placeholder="Ingrese la contraseña temporal" required>
                        <div class="form-text">
                            Contraseña temporal proporcionada por el administrador
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nueva_password" class="form-label required-label">
                            <i class="fas fa-lock me-2"></i>Nueva Contraseña
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="nueva_password" name="nueva_password" 
                               placeholder="Ingrese su nueva contraseña" required
                               minlength="8">
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="form-text">
                            La contraseña debe tener al menos 8 caracteres
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmar_password" class="form-label required-label">
                            <i class="fas fa-check-circle me-2"></i>Confirmar Nueva Contraseña
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="confirmar_password" name="confirmar_password" 
                               placeholder="Confirme su nueva contraseña" required>
                        <div class="form-text" id="passwordMatch"></div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Cambiar Contraseña
                        </button>
                        <a href="<?= BASE_URL ?>/acceso/login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Login
                        </a>
                    </div>
                </form>

                <div class="alert alert-warning mt-4">
                    <small>
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Seguridad:</strong> Por políticas del sistema, debe cambiar la contraseña temporal 
                        proporcionada por el administrador. Esta acción es obligatoria para continuar.
                    </small>
                </div>
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