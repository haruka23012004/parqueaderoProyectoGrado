<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

if (!estaAutenticado() || ($_SESSION['rol_nombre'] != 'vigilante' && $_SESSION['rol_nombre'] != 'administrador_principal')) {
    header('Location: ../acceso/login.php');
    exit();
}

// Obtener parqueaderos activos
$parqueaderos = [];
try {
    $query_parq = "SELECT id, nombre, capacidad_actual, capacidad_total 
                   FROM parqueaderos 
                   WHERE estado = 'activo'";
    $result_parq = $conn->query($query_parq);
    if ($result_parq) {
        $parqueaderos = $result_parq->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error obteniendo parqueaderos: " . $e->getMessage());
}

// Procesar el formulario si se envió
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula'] ?? '');
    $parqueadero_id = intval($_POST['parqueadero_id'] ?? 0);
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $empleado_id = $_SESSION['usuario_id']; // ID del vigilante que registra
    
    // DEBUG: Ver qué datos llegan
    error_log("Datos POST: cedula=$cedula, parqueadero_id=$parqueadero_id, tipo_movimiento=$tipo_movimiento");
    
    try {
        // Validaciones básicas
        if (empty($cedula) || empty($parqueadero_id) || empty($tipo_movimiento)) {
            throw new Exception('Todos los campos son obligatorios. Cédula: ' . ($cedula ?: 'vacía') . 
                              ', Parqueadero: ' . ($parqueadero_id ?: 'vacío') . 
                              ', Movimiento: ' . ($tipo_movimiento ?: 'vacío'));
        }

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
            throw new Exception('Usuario no encontrado o no autorizado. Cédula: ' . $cedula);
        }
        
        $userData = $result->fetch_assoc();
        
        // Verificar parqueadero
        $query_parqueadero = "SELECT id, nombre, capacidad_actual, capacidad_total 
                             FROM parqueaderos 
                             WHERE id = ? AND estado = 'activo'";
        $stmt_parqueadero = $conn->prepare($query_parqueadero);
        $stmt_parqueadero->bind_param("i", $parqueadero_id);
        $stmt_parqueadero->execute();
        $parqueadero_result = $stmt_parqueadero->get_result();
        
        if ($parqueadero_result->num_rows === 0) {
            throw new Exception('Parqueadero no válido o inactivo. ID: ' . $parqueadero_id);
        }
        
        $parqueadero_data = $parqueadero_result->fetch_assoc();
        
        // Verificar si ya tiene una entrada activa
        $query_entrada_activa = "SELECT id FROM registros_acceso 
                                WHERE usuario_id = ? AND tipo_movimiento = 'entrada'
                                AND NOT EXISTS (
                                    SELECT 1 FROM registros_acceso ra2 
                                    WHERE ra2.usuario_id = registros_acceso.usuario_id 
                                    AND ra2.tipo_movimiento = 'salida' 
                                    AND ra2.fecha_hora > registros_acceso.fecha_hora
                                )";
        $stmt_entrada = $conn->prepare($query_entrada_activa);
        $stmt_entrada->bind_param("i", $userData['id']);
        $stmt_entrada->execute();
        $result_entrada = $stmt_entrada->get_result();
        $tiene_entrada_activa = $result_entrada->num_rows > 0;

        // Validaciones de negocio
        if ($tipo_movimiento === 'entrada') {
            if ($tiene_entrada_activa) {
                throw new Exception('El usuario ya tiene una entrada registrada y no ha salido');
            }
            if ($parqueadero_data['capacidad_actual'] <= 0) {
                throw new Exception('Parqueadero lleno. No se puede registrar entrada.');
            }
        } else { // Salida
            if (!$tiene_entrada_activa) {
                throw new Exception('El usuario no tiene una entrada registrada para salir');
            }
        }

        // Registrar el movimiento
        $insertQuery = "INSERT INTO registros_acceso 
                        (usuario_id, vehiculo_id, parqueadero_id, empleado_id, tipo_movimiento, metodo_acceso, fecha_hora) 
                        VALUES (?, ?, ?, ?, ?, 'manual', NOW())";
        
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iiiis", 
            $userData['id'], 
            $userData['vehiculo_id'], 
            $parqueadero_id, 
            $empleado_id,
            $tipo_movimiento
        );
        
        if ($insertStmt->execute()) {
            // Actualizar capacidad del parqueadero
            if ($tipo_movimiento === 'entrada') {
                $updateQuery = "UPDATE parqueaderos 
                               SET capacidad_actual = capacidad_actual - 1 
                               WHERE id = ?";
            } else {
                $updateQuery = "UPDATE parqueaderos 
                               SET capacidad_actual = capacidad_actual + 1 
                               WHERE id = ?";
            }
            
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("i", $parqueadero_id);
            $updateStmt->execute();
            
            $mensaje = $tipo_movimiento === 'entrada' 
                ? '✅ Entrada registrada exitosamente' 
                : '✅ Salida registrada exitosamente';
            $tipo_mensaje = 'success';
            
            // Limpiar formulario después de éxito
            $_POST['cedula'] = '';
            
        } else {
            throw new Exception('Error al registrar el movimiento: ' . $insertStmt->error);
        }
        
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
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
            padding-top: 20px;
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
        .parqueadero-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .btn-action-selected {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.5);
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
                            <?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario Principal -->
                    <div class="p-4">
                        <form method="POST" id="registro-form">
                            <!-- Selector de Parqueadero -->
                            <div class="mb-4">
                                <label for="select-parqueadero" class="form-label fw-bold">
                                    <i class="fas fa-parking me-2"></i>Seleccionar Parqueadero *
                                </label>
                                <select class="form-select form-select-lg" id="select-parqueadero" name="parqueadero_id" required>
                                    <option value="">-- Seleccione un parqueadero --</option>
                                    <?php foreach ($parqueaderos as $parq): ?>
                                        <option value="<?= $parq['id'] ?>" 
                                                <?= (isset($_POST['parqueadero_id']) && $_POST['parqueadero_id'] == $parq['id']) ? 'selected' : '' ?>
                                                data-capacidad="<?= $parq['capacidad_actual'] ?>/<?= $parq['capacidad_total'] ?>">
                                            🏢 <?= htmlspecialchars($parq['nombre']) ?> 
                                            (<?= $parq['capacidad_actual'] ?>/<?= $parq['capacidad_total'] ?> disponibles)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="info-parqueadero"></div>
                            </div>

                            <!-- Botones Rápidos de Acción -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-bolt me-2"></i>Seleccionar Acción *
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-entrada btn-rapido w-100" id="btn-entrada" onclick="setMovimiento('entrada', this)">
                                            <i class="fas fa-sign-in-alt me-2"></i>REGISTRAR ENTRADA
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-salida btn-rapido w-100" id="btn-salida" onclick="setMovimiento('salida', this)">
                                            <i class="fas fa-sign-out-alt me-2"></i>REGISTRAR SALIDA
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="tipo_movimiento" id="tipo_movimiento" value="<?= isset($_POST['tipo_movimiento']) ? htmlspecialchars($_POST['tipo_movimiento']) : '' ?>">
                            </div>

                            <!-- Búsqueda de Usuario -->
                            <div class="mb-3">
                                <label for="cedula" class="form-label fw-bold">
                                    <i class="fas fa-id-card me-2"></i>Número de Cédula *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="cedula" name="cedula" 
                                           placeholder="Ingrese la cédula del usuario" 
                                           value="<?= isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : '' ?>"
                                           required pattern="[0-9]+" title="Solo números permitidos">
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
                                <button type="submit" class="btn btn-secondary btn-lg w-100" id="btn-confirmar" disabled>
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span id="btn-text">Complete todos los campos primero</span>
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
        let parqueaderoSeleccionado = false;

        function setMovimiento(tipo, elemento) {
            movimientoSeleccionado = tipo;
            document.getElementById('tipo_movimiento').value = tipo;
            
            // Remover selección de ambos botones
            document.getElementById('btn-entrada').classList.remove('btn-action-selected');
            document.getElementById('btn-salida').classList.remove('btn-action-selected');
            
            // Agregar selección al botón clickeado
            elemento.classList.add('btn-action-selected');
            
            // Actualizar texto del botón
            const btnText = document.getElementById('btn-text');
            const btnConfirmar = document.getElementById('btn-confirmar');
            
            if (tipo === 'entrada') {
                btnText.textContent = 'CONFIRMAR ENTRADA MANUAL';
                btnConfirmar.className = 'btn btn-success btn-lg w-100';
            } else {
                btnText.textContent = 'CONFIRMAR SALIDA MANUAL';
                btnConfirmar.className = 'btn btn-danger btn-lg w-100';
            }
            
            // Verificar si podemos habilitar el botón
            verificarEstadoBoton();
        }

        function buscarUsuario() {
            const cedula = document.getElementById('cedula').value.trim();
            const userInfo = document.getElementById('user-info');
            const userDetails = document.getElementById('user-details');
            
            if (!cedula) {
                alert('Por favor ingrese una cédula');
                return;
            }

            // Validar que solo sean números
            if (!/^\d+$/.test(cedula)) {
                alert('La cédula debe contener solo números');
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
                    const estadoEntrada = result.data.tiene_entrada_activa ? 
                        '<span class="badge bg-warning">DENTRO</span>' : 
                        '<span class="badge bg-secondary">FUERA</span>';
                    
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
                                <strong>Estado:</strong> ${estadoEntrada}
                            </div>
                        </div>
                    `;
                    
                } else {
                    usuarioEncontrado = null;
                    userDetails.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            ${result.message}
                        </div>
                    `;
                }
                
                // Verificar estado del botón después de la búsqueda
                verificarEstadoBoton();
            })
            .catch(error => {
                usuarioEncontrado = null;
                userDetails.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Error de conexión: No se pudo buscar el usuario
                    </div>
                `;
                verificarEstadoBoton();
                console.error('Error:', error);
            });
        }

        function verificarEstadoBoton() {
            const btnConfirmar = document.getElementById('btn-confirmar');
            const parqueadero = document.getElementById('select-parqueadero').value;
            
            // Habilitar solo si todos los campos están completos
            if (parqueadero && movimientoSeleccionado && usuarioEncontrado) {
                btnConfirmar.disabled = false;
            } else {
                btnConfirmar.disabled = true;
            }
        }

        // Actualizar información del parqueadero seleccionado
        document.getElementById('select-parqueadero').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('info-parqueadero');
            
            parqueaderoSeleccionado = this.value !== '';
            
            if (selectedOption.value && selectedOption.dataset.capacidad) {
                infoDiv.textContent = `Capacidad: ${selectedOption.dataset.capacidad}`;
            } else {
                infoDiv.textContent = '';
            }
            
            verificarEstadoBoton();
        });

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

        // Verificar cambios en cédula
        document.getElementById('cedula').addEventListener('input', function() {
            usuarioEncontrado = null;
            document.getElementById('user-info').classList.add('hidden');
            verificarEstadoBoton();
        });
    </script>
</body>
</html>