<?php
session_start();
require_once '../includes/conexion.php';

$token = $_GET['token'] ?? '';
$error = '';
$valido = false;

if (empty($token)) {
    $error = 'Token inválido o faltante.';
} else {
    // Verificar token válido y no expirado
    $query = "SELECT id, nombre_completo, token_expiracion 
              FROM empleados 
              WHERE token_reset = ? AND token_expiracion > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        $valido = true;
    } else {
        $error = 'El enlace de recuperación ha expirado o es inválido.';
    }
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
        }
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-medium { background-color: #ffc107; width: 50%; }
        .strength-strong { background-color: #28a745; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="header-section">
                <h1><i class="fas fa-lock me-2"></i>Nueva Contraseña</h1>
                <p class="mb-0">Crea una nueva contraseña para tu cuenta</p>
            </div>

            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                    <div class="d-grid">
                        <a href="olvido_password.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Solicitar Nuevo Enlace
                        </a>
                    </div>
                <?php elseif ($valido): ?>
                    
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?>">
                            <?= $_SESSION['mensaje'] ?>
                        </div>
                        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
                    <?php endif; ?>

                    <form method="POST" action="procesar_reset.php" id="reset-form">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-key me-2"></i>Nueva Contraseña
                            </label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                   placeholder="Ingresa tu nueva contraseña" required minlength="8">
                            <div class="password-strength" id="password-strength"></div>
                            <div class="form-text">
                                La contraseña debe tener al menos 8 caracteres.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-check-circle me-2"></i>Confirmar Contraseña
                            </label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" 
                                   placeholder="Repite tu nueva contraseña" required>
                            <div class="form-text" id="password-match"></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <i class="fas fa-save me-2"></i>Guardar Nueva Contraseña
                            </button>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Login
                            </a>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de fortaleza de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            strengthBar.className = 'password-strength ';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 2) {
                strengthBar.className += 'strength-weak';
            } else if (strength <= 4) {
                strengthBar.className += 'strength-medium';
            } else {
                strengthBar.className += 'strength-strong';
            }
        });

        // Validación de coincidencia de contraseñas
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const matchText = document.getElementById('password-match');
            const submitBtn = document.getElementById('submit-btn');
            
            if (confirm.length === 0) {
                matchText.innerHTML = '';
                submitBtn.disabled = true;
            } else if (password === confirm) {
                matchText.innerHTML = '<span class="text-success">✓ Las contraseñas coinciden</span>';
                submitBtn.disabled = false;
            } else {
                matchText.innerHTML = '<span class="text-danger">✗ Las contraseñas no coinciden</span>';
                submitBtn.disabled = true;
            }
        });

        // Validación del formulario
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres.');
                return;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                return;
            }
        });
    </script>
</body>
</html>