<?php
session_start();

// 1. Conexión a la base de datos
require __DIR__ . '/includes/conexion.php';
$conexion = $conn;

// Verificar conexión
if (!isset($conexion) || $conexion->connect_error) {
    die("Error crítico: No se pudo conectar a la base de datos. Verifica:
        <ol>
            <li>Que el servidor MySQL esté funcionando</li>
            <li>Que la base de datos 'parqueaderoautomatizado' exista</li>
            <li>Que el usuario y contraseña en conexion.php sean correctos</li>
        </ol>");
}

// 2. Verificar si es el primer acceso (no hay empleados registrados)
$result = $conexion->query("SELECT COUNT(*) as total FROM empleados");
$total_empleados = $result->fetch_assoc()['total'];
$primer_acceso = ($total_empleados == 0);

// 3. Si no es primer acceso, validar sesión de administrador
if (!$primer_acceso) {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: acceso/login.php');
        exit();
    }

    $stmt = $conexion->prepare("SELECT r.nombre FROM empleados e 
                              JOIN roles_sistema r ON e.rol_id = r.id 
                              WHERE e.id = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_actual = $result->fetch_assoc();

    if ($usuario_actual['nombre'] != 'administrador_principal') {
        header('Location: acceso_denegado.php');
        exit();
    }
}

// 4. Procesamiento del formulario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validación de datos
        $datos = [
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'cedula' => trim($_POST['cedula'] ?? ''),
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'usuario_login' => trim($_POST['usuario_login'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'rol_id' => intval($_POST['rol_id'] ?? 0),
            'estado' => in_array($_POST['estado'] ?? '', ['activo', 'inactivo', 'suspendido']) ? $_POST['estado'] : 'activo'
        ];

        // Validaciones
        if (empty($datos['nombre_completo'])) throw new Exception("El nombre completo es obligatorio");
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) throw new Exception("Email no válido");
        if ($datos['password'] !== $datos['confirm_password']) throw new Exception("Las contraseñas no coinciden");
        if (strlen($datos['password']) < 8) throw new Exception("La contraseña debe tener al menos 8 caracteres");

        // Hashear contraseña
        $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

        // Insertar empleado
        $stmt = $conexion->prepare("INSERT INTO empleados (rol_id, nombre_completo, cedula, email, usuario_login, password_hash, estado) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("issssss", 
            $datos['rol_id'],
            $datos['nombre_completo'],
            $datos['cedula'],
            $datos['email'],
            $datos['usuario_login'],
            $password_hash,
            $datos['estado']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al registrar empleado: " . $stmt->error);
        }

        // Si es primer acceso, redirigir al login
        if ($primer_acceso) {
            $_SESSION['registro_exitoso'] = "Primer administrador registrado con éxito. Por favor inicie sesión.";
            header('Location: login.php');
            exit();
        }

        $mensaje = "Empleado registrado exitosamente!";
        
    } catch (mysqli_sql_exception $e) {
        $error = ($e->getCode() == 1062) ? 
            "Error: El email, cédula o usuario ya existen" : 
            "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 5. Obtener roles disponibles
if ($primer_acceso) {
    $roles = $conexion->query("SELECT id, nombre, descripcion FROM roles_sistema WHERE nombre = 'administrador_principal'")->fetch_all(MYSQLI_ASSOC);
} else {
    $roles = $conexion->query("SELECT id, nombre, descripcion FROM roles_sistema WHERE nombre IN ('administrador_principal', 'empleado_secundario', 'vigilante') ORDER BY nivel_permiso")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $primer_acceso ? 'Configuración Inicial' : 'Registro de Empleados' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 700px;
            margin: 30px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            background: #f8f9fa;
        }
        .required-label:after {
            content: " *";
            color: #dc3545;
        }
        .role-desc {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .first-access-header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if (!$primer_acceso) include 'navbar.php'; ?>
    
    <div class="container py-4">
        <div class="form-container">
            <?php if ($primer_acceso): ?>
                <div class="first-access-header text-center">
                    <h2><i class="bi bi-gear-fill"></i> Configuración Inicial del Sistema</h2>
                    <p class="mb-0">Registre el primer administrador del sistema</p>
                </div>
            <?php endif; ?>
            
            <h2 class="text-center mb-4"><?= $primer_acceso ? 'Registro del Administrador Principal' : 'Registro de Nuevo Empleado' ?></h2>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" novalidate>
                <div class="row g-3">
                    <!-- Datos Personales -->
                    <div class="col-md-6">
                        <label for="nombre_completo" class="form-label required-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="cedula" class="form-label required-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label required-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="usuario_login" class="form-label required-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="usuario_login" name="usuario_login" required>
                    </div>
                    
                    <!-- Contraseñas -->
                    <div class="col-md-6">
                        <label for="password" class="form-label required-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <small class="form-text text-muted">Mínimo 8 caracteres</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label required-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <!-- Rol y Estado -->
                    <div class="col-md-6">
                        <label for="rol_id" class="form-label required-label">Rol</label>
                        <select class="form-select" id="rol_id" name="rol_id" required <?= $primer_acceso ? 'disabled' : '' ?>>
                            <?php if ($primer_acceso): ?>
                                <option value="1" selected>Administrador Principal</option>
                                <input type="hidden" name="rol_id" value="1">
                            <?php else: ?>
                                <option value="">Seleccione un rol...</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id'] ?>">
                                        <?= match($rol['nombre']) {
                                            'administrador_principal' => 'Administrador Principal',
                                            'empleado_secundario' => 'Empleado Secundario',
                                            'vigilante' => 'Vigilante',
                                            default => $rol['nombre']
                                        } ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="role-desc" id="role-description">
                            <?= $primer_acceso ? 'Tiene control total sobre el sistema' : 'Seleccione un rol para ver sus permisos' ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="estado" class="form-label required-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="activo" selected>Activo</option>
                            <?php if (!$primer_acceso): ?>
                                <option value="inactivo">Inactivo</option>
                                <option value="suspendido">Suspendido</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Botón de envío -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <?= $primer_acceso ? 'Registrar Administrador Principal' : 'Registrar Empleado' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar descripción del rol seleccionado
        document.getElementById('rol_id').addEventListener('change', function() {
            const descripciones = {
                '1': 'Control total del sistema. Puede crear otros empleados y aprobar usuarios.',
                '2': 'Puede gestionar registros pero no aprobar nuevos usuarios o QR.',
                '3': 'Solo puede verificar accesos al parqueadero.'
            };
            document.getElementById('role-description').textContent = 
                descripciones[this.value] || 'Seleccione un rol para ver sus permisos';
        });

        // Deshabilitar campo de estado si es primer acceso
        <?php if ($primer_acceso): ?>
            document.getElementById('estado').disabled = true;
        <?php endif; ?>
    </script>
</body>
</html>