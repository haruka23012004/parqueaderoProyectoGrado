<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Procesar el formulario si se envió
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula']);
    $parqueadero_id = intval($_POST['parqueadero_id']);
    $tipo_movimiento = $_POST['tipo_movimiento'];
    
    try {
        // Buscar usuario por cédula
        $query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.id as vehiculo_id
                  FROM usuarios_parqueadero u 
                  INNER JOIN vehiculos v ON u.id = v.usuario_id 
                  WHERE u.cedula = ? AND u.estado = 'aprobado' AND u.acceso_activo = 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $mensaje = 'Usuario no encontrado o no autorizado';
            $tipo_mensaje = 'error';
        } else {
            $userData = $result->fetch_assoc();
            
            // Verificar parqueadero
            $query_parqueadero = "SELECT id, nombre, capacidad_actual FROM parqueaderos WHERE id = ?";
            $stmt_parqueadero = $conn->prepare($query_parqueadero);
            $stmt_parqueadero->bind_param("i", $parqueadero_id);
            $stmt_parqueadero->execute();
            $parqueadero_result = $stmt_parqueadero->get_result();
            
            if ($parqueadero_result->num_rows === 0) {
                $mensaje = 'Parqueadero no válido';
                $tipo_mensaje = 'error';
            } else {
                $parqueadero_data = $parqueadero_result->fetch_assoc();
                
                // Para ENTRADAS: verificar capacidad
                if ($tipo_movimiento === 'entrada' && $parqueadero_data['capacidad_actual'] <= 0) {
                    $mensaje = 'Parqueadero lleno. No se puede registrar entrada.';
                    $tipo_mensaje = 'error';
                } else {
                    // Registrar el movimiento
                    $insertQuery = "INSERT INTO registros_acceso (usuario_id, vehiculo_id, parqueadero_id, tipo_movimiento, metodo_acceso, fecha_hora) 
                                    VALUES (?, ?, ?, ?, 'manual', NOW())";
                    
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("iiis", $userData['id'], $userData['vehiculo_id'], $parqueadero_id, $tipo_movimiento);
                    
                    if ($insertStmt->execute()) {
                        // Actualizar capacidad del parqueadero
                        if ($tipo_movimiento === 'entrada') {
                            $updateQuery = "UPDATE parqueaderos 
                                           SET capacidad_actual = capacidad_actual - 1 
                                           WHERE id = ? AND capacidad_actual > 0";
                        } else {
                            $updateQuery = "UPDATE parqueaderos 
                                           SET capacidad_actual = capacidad_actual + 1 
                                           WHERE id = ? AND capacidad_actual < capacidad_total";
                        }
                        
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("i", $parqueadero_id);
                        $updateStmt->execute();
                        
                        $mensaje = $tipo_movimiento === 'entrada' ? 'Entrada registrada exitosamente' : 'Salida registrada exitosamente';
                        $tipo_mensaje = 'success';
                        
                        // Limpiar formulario después de éxito
                        $_POST['cedula'] = '';
                    } else {
                        $mensaje = 'Error al registrar el movimiento: ' . $insertStmt->error;
                        $tipo_mensaje = 'error';
                    }
                }
            }
        }
    } catch (Exception $e) {
        $mensaje = 'Error del sistema: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Manual - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        .form-container { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .btn-rapido {
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn-entrada {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
        }
        .btn-salida {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            border: none;
            color: white;
        }
        .btn-rapido:hover {
            transform: scale(1.05);
        }
        .user-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        .hidden {
            display: none;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Encabezado -->
                <div class="text-center text-white mb-4">
                    <h1><i class="fas fa-keyboard me-2"></i>Registro Manual</h1>
                    <p class="lead">Para casos de emergencia cuando no hay código QR disponible</p>
                </div>

                <!-- Contenedor del Formulario -->
                <div class="form-container">
                    <!-- Header -->
                    <div class="header-section">
                        <h3><i class="fas fa-exclamation-triangle me-2"></i>Acceso de Emergencia</h3>
                        <p class="mb-0">Solo usar cuando el usuario haya perdido/olvidado su carnet</p>
                    </div>

                    <!-- Mensajes -->
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?> alert-custom m-3">
                            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check' : 'exclamation-triangle' ?> me-2"></i>
                            <?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario Principal -->
                    <div class="p-4">
                        <!-- Selector de Parqueadero -->
                        <div class="mb-4">
                            <label for="select-parqueadero" class="form-label fw-bold">
                                <i class="fas fa-parking me-2"></i>Seleccionar Parqueadero
                            </label>
                            <select class="form-select form-select-lg" id="select-parqueadero" name="parqueadero_id" required>
                                <option value="">-- Seleccione un parqueadero --</option>
                                <option value="1">🏢 Parqueadero 1 - Principal</option>
                                <option value="2">🚗 Parqueadero 2 - Secundario</option>
                            </select>
                        </div>

                        <!-- Botones Rápidos de Acción -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-bolt me-2"></i>Acción Rápida
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-entrada btn-rapido w-100" onclick="setMovimiento('entrada')">
                                        <i class="fas fa-sign-in-alt me-2"></i>REGISTRAR ENTRADA
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-salida btn-rapido w-100" onclick="setMovimiento('salida')">
                                        <i class="fas fa-sign-out-alt me-2"></i>REGISTRAR SALIDA
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de Búsqueda -->
                        <form method="POST" id="registro-form">
                            <input type="hidden" name="tipo_movimiento" id="tipo_movimiento" value="">
                            
                            <div class="mb-3">
                                <label for="cedula" class="form-label fw-bold">
                                    <i class="fas fa-id-card me-2"></i>Número de Cédula
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="cedula" name="cedula" 
                                           placeholder="Ingrese la cédula del usuario" 
                                           value="<?= isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : '' ?>"
                                           required>
                                    <button type="button" class="btn btn-primary" onclick="buscarUsuario()">
                                        <i class="fas fa-search me-1"></i>Buscar
                                    </button>
                                </div>
                                <div class="form-text">
                                    Ingrese solo el número de cédula (sin puntos ni comas)
                                </div>
                            </div>

                            <!-- Información del Usuario -->
                            <div id="user-info" class="hidden">
                                <div class="user-card bg-light">
                                    <h5 class="mb-2">Información del Usuario</h5>
                                    <div id="user-details">
                                        <!-- Los datos del usuario se cargarán aquí -->
                                    </div>
                                </div>
                            </div>

                            <!-- Botón de Confirmación -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100" id="btn-confirmar" disabled>
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span id="btn-text">Seleccione una acción primero</span>
                                </button>
                            </div>
                        </form>

                        <!-- Información de Emergencia -->
                        <div class="alert alert-warning mt-4">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Procedimiento de Emergencia</h6>
                            <small>
                                1. Verificar identidad con documento oficial<br>
                                2. Confirmar que el usuario está autorizado<br>
                                3. Registrar solo en casos necesarios<br>
                                4. Reportar pérdida de carnet a administración
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let movimientoSeleccionado = '';
        let usuarioEncontrado = null;

        function setMovimiento(tipo) {
            movimientoSeleccionado = tipo;
            document.getElementById('tipo_movimiento').value = tipo;
            
            // Actualizar texto del botón
            const btnText = document.getElementById('btn-text');
            const btnConfirmar = document.getElementById('btn-confirmar');
            
            if (tipo === 'entrada') {
                btnText.textContent = 'CONFIRMAR ENTRADA MANUAL';
                btnConfirmar.classList.remove('btn-secondary');
                btnConfirmar.classList.add('btn-success');
            } else {
                btnText.textContent = 'CONFIRMAR SALIDA MANUAL';
                btnConfirmar.classList.remove('btn-secondary');
                btnConfirmar.classList.add('btn-danger');
            }
            
            // Habilitar botón si ya hay usuario
            if (usuarioEncontrado) {
                btnConfirmar.disabled = false;
            }
        }

        function buscarUsuario() {
            const cedula = document.getElementById('cedula').value.trim();
            const userInfo = document.getElementById('user-info');
            const userDetails = document.getElementById('user-details');
            const btnConfirmar = document.getElementById('btn-confirmar');
            
            if (!cedula) {
                alert('Por favor ingrese una cédula');
                return;
            }

            // Mostrar loading
            userDetails.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin me-2"></i>Buscando usuario...</div>';
            userInfo.classList.remove('hidden');

            // Hacer la búsqueda via AJAX
            const formData = new FormData();
            formData.append('cedula', cedula);
            formData.append('action', 'buscar_usuario');

            fetch('procesar_busqueda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    usuarioEncontrado = result.data;
                    userDetails.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Nombre:</strong> ${result.data.nombre_completo}<br>
                                <strong>Cédula:</strong> ${result.data.cedula}<br>
                                <strong>Tipo:</strong> <span class="badge bg-info">${result.data.tipo}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Vehículo:</strong> ${result.data.placa}<br>
                                <strong>Tipo:</strong> ${result.data.tipo_vehiculo}<br>
                                <strong>Estado:</strong> <span class="badge bg-success">AUTORIZADO</span>
                            </div>
                        </div>
                    `;
                    
                    // Habilitar botón de confirmación si ya se seleccionó movimiento
                    if (movimientoSeleccionado) {
                        btnConfirmar.disabled = false;
                    }
                } else {
                    usuarioEncontrado = null;
                    userDetails.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            ${result.message}
                        </div>
                    `;
                    btnConfirmar.disabled = true;
                }
            })
            .catch(error => {
                usuarioEncontrado = null;
                userDetails.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Error de conexión: No se pudo buscar el usuario
                    </div>
                `;
                btnConfirmar.disabled = true;
            });
        }

        // Validar formulario antes de enviar
        document.getElementById('registro-form').addEventListener('submit', function(e) {
            const parqueadero = document.getElementById('select-parqueadero').value;
            const movimiento = document.getElementById('tipo_movimiento').value;
            
            if (!parqueadero) {
                e.preventDefault();
                alert('Por favor seleccione un parqueadero');
                return;
            }
            
            if (!movimiento) {
                e.preventDefault();
                alert('Por favor seleccione un tipo de movimiento (Entrada/Salida)');
                return;
            }
            
            if (!usuarioEncontrado) {
                e.preventDefault();
                alert('Por favor busque y verifique el usuario primero');
                return;
            }
            
            // Confirmación final
            const accion = movimiento === 'entrada' ? 'ENTRADA' : 'SALIDA';
            if (!confirm(`¿Está seguro de registrar la ${accion} manual para ${usuarioEncontrado.nombre_completo}?`)) {
                e.preventDefault();
            }
        });

        // Enter en cédula ejecuta búsqueda
        document.getElementById('cedula').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarUsuario();
            }
        });
    </script>
</body>
</html>