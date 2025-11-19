<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Obtener todos los accesos de entrada que no tienen salida registrada
$query = "SELECT 
            ra.id as registro_id,
            u.id as usuario_id,
            u.codigo_universitario,
            u.cedula,
            u.nombre_completo,
            u.tipo as tipo_usuario,
            v.placa,
            v.tipo as tipo_vehiculo,
            v.marca,
            v.color,
            p.nombre as parqueadero_nombre,
            p.id as parqueadero_id,
            ra.fecha_hora as fecha_entrada
          FROM registros_acceso ra
          INNER JOIN usuarios_parqueadero u ON ra.usuario_id = u.id
          INNER JOIN vehiculos v ON ra.vehiculo_id = v.id
          INNER JOIN parqueaderos p ON ra.parqueadero_id = p.id
          WHERE ra.tipo_movimiento = 'entrada'
          AND NOT EXISTS (
              SELECT 1 
              FROM registros_acceso ra2 
              WHERE ra2.usuario_id = u.id 
              AND ra2.tipo_movimiento = 'salida' 
              AND ra2.fecha_hora > ra.fecha_hora
          )
          ORDER BY ra.fecha_hora DESC";

$result = $conn->query($query);
$accesos = [];
while ($row = $result->fetch_assoc()) {
    $accesos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Salidas - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .btn-salida {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-salida:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #ff5252 0%, #e53935 100%);
        }
        .acceso-card {
            border-left: 4px solid #28a745;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .acceso-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Encabezado -->
        <div class="row">
            <div class="col-12">
                <div class="header-section">
                    <h1><i class="fas fa-sign-out-alt me-2"></i>Registrar Salidas</h1>
                    <p class="mb-0">¿Quieres registrar una salida del parqueadero? Selecciona un vehículo de la lista</p>
                </div>
            </div>
        </div>

        <!-- Lista de Accesos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Accesos Activos en el Parqueadero</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($accesos) > 0): ?>
                            <div class="row">
                                <?php foreach ($accesos as $acceso): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card acceso-card">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-9">
                                                        <h6 class="card-title"><?= htmlspecialchars($acceso['nombre_completo']) ?></h6>
                                                        <p class="card-text mb-1">
                                                            <small><strong>Cédula:</strong> <?= htmlspecialchars($acceso['cedula']) ?></small>
                                                        </p>
                                                        <p class="card-text mb-1">
                                                            <small><strong>Código:</strong> <?= htmlspecialchars($acceso['codigo_universitario']) ?></small>
                                                        </p>
                                                        <p class="card-text mb-1">
                                                            <small><strong>Vehículo:</strong> <?= htmlspecialchars($acceso['placa']) ?> - <?= htmlspecialchars($acceso['tipo_vehiculo']) ?></small>
                                                        </p>
                                                        <p class="card-text mb-1">
                                                            <small><strong>Entrada:</strong> <?= date('H:i', strtotime($acceso['fecha_entrada'])) ?></small>
                                                        </p>
                                                        <p class="card-text mb-0">
                                                            <small><strong>Parqueadero:</strong> <?= htmlspecialchars($acceso['parqueadero_nombre']) ?></small>
                                                        </p>
                                                    </div>
                                                    <div class="col-3 d-flex align-items-center justify-content-end">
                                                        <button class="btn btn-salida" 
                                                                onclick="registrarSalida(<?= $acceso['usuario_id'] ?>, <?= $acceso['parqueadero_id'] ?>)">
                                                            <i class="fas fa-sign-out-alt me-1"></i>Salir
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-car fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay vehículos en el parqueadero</h5>
                                <p class="text-muted">Todos los vehículos han registrado su salida</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para escanear QR de salida -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-qrcode me-2"></i>Escanear QR para Salida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="reader" style="width: 100%; margin: 0 auto;"></div>
                    <div id="result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    <script>
        let currentUsuarioId = null;
        let currentParqueaderoId = null;
        let html5QrcodeScanner = null;

        function registrarSalida(usuarioId, parqueaderoId) {
            currentUsuarioId = usuarioId;
            currentParqueaderoId = parqueaderoId;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
            
            // Inicializar scanner cuando el modal se muestra
            setTimeout(initScanner, 500);
        }

        function initScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", 
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 } 
                }
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Detener el scanner
            html5QrcodeScanner.clear();
            
            // Verificar que el QR escaneado corresponde al usuario correcto
            try {
                const data = JSON.parse(decodedText);
                
                if (data.usuario_id != currentUsuarioId) {
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-danger">
                            <h6>❌ Error</h6>
                            <p>El QR escaneado no corresponde al vehículo seleccionado</p>
                        </div>
                    `;
                    setTimeout(() => {
                        initScanner();
                        document.getElementById('result').innerHTML = '';
                    }, 3000);
                    return;
                }

                // Procesar la salida
                procesarSalida(data);

            } catch (error) {
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ QR inválido</h6>
                        <p>El código QR no es válido</p>
                    </div>
                `;
                setTimeout(() => {
                    initScanner();
                    document.getElementById('result').innerHTML = '';
                }, 3000);
            }
        }

        function onScanFailure(error) {
            // Error silencioso
        }

        function procesarSalida(data) {
            const formData = new FormData();
            formData.append('action', 'registrar_salida');
            formData.append('usuario_id', data.usuario_id);
            formData.append('hash', data.hash);
            formData.append('parqueadero_id', data.parqueadero_id);

            fetch('procesar_salida.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ Salida registrada exitosamente</h6>
                            <p><strong>Nombre:</strong> ${result.data.nombre_completo}</p>
                            <p><strong>Vehículo:</strong> ${result.data.placa}</p>
                            <p><strong>Parqueadero:</strong> ${result.data.parqueadero}</p>
                            <p class="mb-0">${result.data.mensaje}</p>
                        </div>
                    `;
                    
                    // Recargar la página después de 2 segundos
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-danger">
                            <h6>❌ Error</h6>
                            <p>${result.message}</p>
                        </div>
                    `;
                    setTimeout(() => {
                        initScanner();
                        document.getElementById('result').innerHTML = '';
                    }, 3000);
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ Error del sistema</h6>
                        <p>No se pudo procesar la salida</p>
                    </div>
                `;
                setTimeout(() => {
                    initScanner();
                    document.getElementById('result').innerHTML = '';
                }, 3000);
            });
        }

        // Limpiar scanner cuando se cierra el modal
        document.getElementById('qrModal').addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
        });
    </script>
</body>
</html>