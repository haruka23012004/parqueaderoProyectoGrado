<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------- BASE_URL UNIVERSAL ---------------- //

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Ejemplo en servidor:
// /parqueaderoProyectoGrado/acceso/procesar_login.php

$parts = explode('/', trim($scriptPath, '/')); 

// El primer segmento es SIEMPRE la carpeta del proyecto
$rootFolder = '/' . $parts[0];

define('BASE_URL', $rootFolder);


// ---------------- FUNCIONES ---------------- //

function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

function redirigirSegunRol() {
    if (!estaAutenticado()) {
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    $rol = $_SESSION['rol_nombre'] ?? '';

    $paginas = [
        'administrador_principal' => BASE_URL . '/paneles/administrador.php',
        'empleado_secundario'     => BASE_URL . '/paneles/empleado.php',
        'vigilante'               => BASE_URL . '/paneles/vigilante.php'
    ];

    if (isset($paginas[$rol])) {
        header('Location: ' . $paginas[$rol]);
        exit();
    }

    header('Location: ' . BASE_URL . '/acceso/logout.php');
    exit();
}

function mostrarMensaje() {
    if (!empty($_SESSION['mensaje'])) {
        $m = $_SESSION['mensaje'];
        echo '<div class="alert alert-' . htmlspecialchars($m['tipo']) . '">' .
             htmlspecialchars($m['texto']) .
             '</div>';
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
