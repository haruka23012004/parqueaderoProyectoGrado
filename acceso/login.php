<?php
require_once __DIR__.'/../includes/conexion.php';
require_once __DIR__.'/../includes/auth.php';


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            background: #fff;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <img src="/PARQUEADEROPROYECTOGRADO/assets/img/logoUniguajira.png" alt="Logo Parqueadero">
                <h3 class="mt-3">Sistema de Parqueadero</h3>
            </div>
            
            <?php mostrarMensaje(); ?>
            
            <form action="/PARQUEADEROPROYECTOGRADO/acceso/procesar_login.php" method="POST">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
                <div class="text-center mt-3">
                    <a href="/PARQUEADEROPROYECTOGRADO/acceso/recuperar_contrasena.php">¿Olvidaste tu contraseña?</a>
                </div>
                <div class="text-center mt-3">
                    <a href="../index.php">Inicio</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>