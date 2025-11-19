<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Procesando login --- BASE_URL=" . BASE_URL);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensaje('danger', 'Método no permitido');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    setMensaje('danger', 'Usuario y contraseña son obligatorios');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}

try {
    $sql = "SELECT e.id, e.usuario_login, e.password_hash, e.estado,
                   r.nombre AS rol_nombre, r.nivel_permiso
            FROM empleados e
            JOIN roles_sistema r ON e.rol_id = r.id
            WHERE e.usuario_login = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        setMensaje('danger', 'Usuario o contraseña incorrectos');
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    $empleado = mysqli_fetch_assoc($result);

    if (!password_verify($password, $empleado['password_hash'])) {
        setMensaje('danger', 'Usuario o contraseña incorrectos');
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    if ($empleado['estado'] !== 'activo') {
        $mensaje = match($empleado['estado']) {
            'inactivo' => 'Tu cuenta está inactiva',
            'suspendido' => 'Tu cuenta está suspendida',
            default => 'No puedes iniciar sesión con este estado'
        };
        setMensaje('danger', $mensaje);
        header('Location: ' . BASE_URL . '/acceso/login.php');
        exit();
    }

    // Guardar sesión
    $_SESSION['usuario_id'] = $empleado['id'];
    $_SESSION['usuario_login'] = $empleado['usuario_login'];
    $_SESSION['rol_nombre'] = $empleado['rol_nombre'];
    $_SESSION['nivel_permiso'] = $empleado['nivel_permiso'];


error_log("ROL DETECTADO: " . $_SESSION['rol_nombre']);
error_log("URL DE REDIRECCION: " . BASE_URL . "/paneles/" . $_SESSION['rol_nombre'] . ".php");



    // Redirige según rol
    redirigirSegunRol();

} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    setMensaje('danger', 'Error al procesar la solicitud');
    header('Location: ' . BASE_URL . '/acceso/login.php');
    exit();
}
?>
