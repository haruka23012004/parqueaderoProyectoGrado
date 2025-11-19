<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * BASE_URL = Carpeta principal del proyecto
 * Ejemplo automático:
 *   http://localhost/PARQUEADEROPROYECTOGRADO/acceso/login.php
 *
 * $_SERVER['SCRIPT_NAME'] devuelve:
 *   /PARQUEADEROPROYECTOGRADO/acceso/login.php
 *
 * dirname(dirname(...)) devuelve:
 *   /PARQUEADEROPROYECTOGRADO
 */

$root = dirname(dirname($_SERVER['SCRIPT_NAME']));
$root = str_replace('\\', '/', $root);
$root = rtrim($root, '/');
define('BASE_URL', $root);

/*
 * BASE_URL ahora SIEMPRE es:
 *   /PARQUEADEROPROYECTOGRADO
 *   (sin importar desde qué carpeta entres)
 */

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
