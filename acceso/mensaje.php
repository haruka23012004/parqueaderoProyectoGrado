<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = '';
$error = '';
$solicitudes = [];

// Procesar nueva solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nueva_solicitud') {
    try {
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $usuario_sistema = trim($_POST['usuario_sistema'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $descripcion_problema = trim($_POST['descripcion_problema'] ?? '');

        // Validaciones
        if (empty($nombre_completo) || empty($cedula) || empty($contacto) || empty($descripcion_problema)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }

        // Verificar si el empleado existe
        $sql_verificar = "SELECT id FROM empleados WHERE cedula = ?";
        $stmt_verificar = mysqli_prepare($conn, $sql_verificar);
        mysqli_stmt_bind_param($stmt_verificar, "s", $cedula);
        mysqli_stmt_execute($stmt_verificar);
        $result_verificar = mysqli_stmt_get_result($stmt_verificar);
        
        $empleado_id = null;
        if ($row = mysqli_fetch_assoc($result_verificar)) {
            $empleado_id = $row['id'];
        }

        // Insertar solicitud
        $sql = "INSERT INTO solicitudes_recuperacion (empleado_id, nombre_completo, cedula, cargo, usuario_sistema, contacto, descripcion_problema) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", $empleado_id, $nombre_completo, $cedula, $cargo, $usuario_sistema, $contacto, $descripcion_problema);
        
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Solicitud enviada correctamente. El administrador la revisará pronto.";
        } else {
            throw new Exception("Error al enviar la solicitud");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar solicitudes por cédula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'buscar_solicitudes') {
    $cedula_buscar = trim($_POST['cedula_buscar'] ?? '');
    
    if (!empty($cedula_buscar)) {
        // Buscar solicitudes de los últimos 30 días
        $sql = "SELECT *, 
                       DATEDIFF(NOW(), fecha_solicitud) as dias_transcurridos
                FROM solicitudes_recuperacion 
                WHERE cedula = ? 
                AND fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY fecha_solicitud DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $cedula_buscar);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $solicitudes = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        if (empty($solicitudes)) {
            $mensaje = "No se encontraron solicitudes para esta cédula en los últimos 30 días.";
        }
    } else {
        $error = "Por favor ingrese una cédula para buscar";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Recuperación - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .solicitud-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            margin: 20px auto;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .solicitud-antigua {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .estado-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="solicitud-container">
            <div class="header-section">
                <h1><i class="fas fa-life-ring me-2"></i>Soporte del Sistema</h1>
                <p class="mb-0">Solicitud de recuperación de contraseña y soporte técnico</p>
            </div>

            <div class="p-4">
                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Pestañas -->
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="nueva-tab" data-bs-toggle="tab" data-bs-target="#nueva" type="button" role="tab">
                            <i class="fas fa-plus-circle me-2"></i>Nueva Solicitud
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="buscar-tab" data-bs-toggle="tab" data-bs-target="#buscar" type="button" role="tab">
                            <i class="fas fa-search me-2"></i>Consultar Estado
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <!-- Pestaña Nueva Solicitud -->
                    <div class="tab-pane fade show active" id="nueva" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="accion" value="nueva_solicitud">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombre_completo" class="form-label required-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cedula" class="form-label required-label">Cédula</label>
                                    <input type="text" class="form-control" id="cedula" name="cedula" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cargo" class="form-label">Cargo/Departamento</label>
                                    <input type="text" class="form-control" id="cargo" name="cargo" placeholder="Opcional">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="usuario_sistema" class="form-label">Usuario del Sistema</label>
                                    <input type="text" class="form-control" id="usuario_sistema" name="usuario_sistema" placeholder="Opcional">
                                </div>
                                
                                <div class="col-12">
                                    <label for="contacto" class="form-label required-label">Teléfono o Correo de Contacto</label>
                                    <input type="text" class="form-control" id="contacto" name="contacto" 
                                           placeholder="ej: 3001234567 o usuario@email.com" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="descripcion_problema" class="form-label required-label">Descripción del Problema</label>
                                    <textarea class="form-control" id="descripcion_problema" name="descripcion_problema" 
                                              rows="4" placeholder="Describa detalladamente el problema que está experimentando..." required></textarea>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nota:</strong> El administrador revisará su solicitud y se pondrá en contacto con usted. 
                                Las solicitudes tienen un tiempo máximo de respuesta de 30 días.
                            </small>
                        </div>
                    </div>

                    <!-- Pestaña Buscar Solicitudes -->
                    <div class="tab-pane fade" id="buscar" role="tabpanel">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="accion" value="buscar_solicitudes">
                            
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="cedula_buscar" class="form-label">Ingrese su Cédula</label>
                                    <input type="text" class="form-control" id="cedula_buscar" name="cedula_buscar" 
                                           placeholder="Ingrese su número de cédula" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (!empty($solicitudes)): ?>
                            <div class="solicitudes-list">
                                <h5 class="mb-3">Sus Solicitudes (Últimos 30 días)</h5>
                                
                                <?php foreach ($solicitudes as $solicitud): ?>
                                    <div class="card mb-3 <?= $solicitud['dias_transcurridos'] > 30 ? 'solicitud-antigua' : '' ?>">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <strong>Solicitud #<?= $solicitud['id'] ?></strong>
                                            <span class="badge 
                                                <?= $solicitud['estado'] == 'pendiente' ? 'bg-warning' : '' ?>
                                                <?= $solicitud['estado'] == 'en_proceso' ? 'bg-info' : '' ?>
                                                <?= $solicitud['estado'] == 'resuelta' ? 'bg-success' : '' ?> estado-badge">
                                                <?= ucfirst($solicitud['estado']) ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></p>
                                            <p><strong>Descripción:</strong> <?= htmlspecialchars($solicitud['descripcion_problema']) ?></p>
                                            
                                            <?php if (!empty($solicitud['notas_administrador'])): ?>
                                                <div class="alert alert-info mt-2">
                                                    <strong><i class="fas fa-comment me-2"></i>Notas del Administrador:</strong><br>
                                                    <?= htmlspecialchars($solicitud['notas_administrador']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($solicitud['dias_transcurridos'] > 30): ?>
                                                <div class="alert alert-warning mt-2">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Esta solicitud tiene más de 30 días.</strong> Por favor contacte nuevamente al administrador.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'buscar_solicitudes'): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No se encontraron solicitudes para esta cédula en los últimos 30 días.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary text-center">
                                <i class="fas fa-search me-2"></i>
                                Ingrese su cédula para consultar el estado de sus solicitudes.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>