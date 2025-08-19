<?php
// Verificar si la sesión no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

function redirigirSegunRol() {
    if (!estaAutenticado()) {
        header('Location: /PARQUEADEROPROYECTOGRADO/acceso/login.php');
        exit();
    }
    
    $rol = $_SESSION['rol_nombre'] ?? '';
    $paginas = [
        'administrador_principal' => '/PARQUEADEROPROYECTOGRADO/paneles/administrador.php',
        'empleado_secundario' => '/PARQUEADEROPROYECTOGRADO/paneles/empleado.php',
        'vigilante' => '/PARQUEADEROPROYECTOGRADO/paneles/vigilante.php'
    ];
    
    if (isset($paginas[$rol])) {
        header('Location: ' . $paginas[$rol]);
        exit();
    }
    
    header('Location: /PARQUEADEROPROYECTOGRADO/acceso/logout.php');
    exit();
}

function mostrarMensaje() {
    if (isset($_SESSION['mensaje'])) {
        echo '<div class="alert alert-'.htmlspecialchars($_SESSION['mensaje']['tipo']).'">'.
             htmlspecialchars($_SESSION['mensaje']['texto']).'</div>';
        unset($_SESSION['mensaje']);
    }
}

function setMensaje($tipo, $texto) {
    $_SESSION['mensaje'] = [
        'tipo' => $tipo,
        'texto' => $texto
    ];
}
?>