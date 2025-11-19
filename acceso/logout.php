<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones de autenticación
require_once __DIR__.'/../includes/auth.php';

// 1. Limpiar todas las variables de sesión
$_SESSION = [];

// 2. Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión
session_destroy();

// 4. Establecer mensaje de éxito
setMensaje('success', 'Has cerrado sesión correctamente');

// 5. Redirigir al login
header('Location: ' . BASE_URL . '/acceso/login.php');

exit();
?>