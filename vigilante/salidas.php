<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/paneles/administrador.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lector QR - Salida Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- LIBRERÍA LOCAL -->
    <script src="../assets/js/html5-qrcode.min.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        .scanner-container { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            overflow: hidden;
        }
        #reader { 
            width: 100%; 
            height: 300px; 
            border: 3px dashed #dc3545; 
            border-radius: 10px; 
            background: #f8f9fa;
        }
        .user-card { 
            transition: all 0.3s ease; 
            border: none; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .hidden { 
            display: none; 
        }
        .placa-display { 
            font-family: 'Courier New', monospace; 
            font-size: 1.5rem; 
            font-weight: bold; 
            letter-spacing: 2px; 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 5px; 
        }
        .scan-animation { 
            animation: pulse 2s infinite; 
        }
        @keyframes pulse { 
            0% { border-color: #dc3545; } 
            50% { border-color: #28a745; } 
            100% { border-color: #dc3545; } 
        }
        .loading-text {
            color: #6c757d;
            font-style: italic;
        }
        .parqueadero-badge {
            font-size: 1.1rem;
            padding: 8px 15px;
        }
        .btn-salida {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-salida:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center text-white mb-4">
                    <h1><i class="fas fa-sign-out-alt me-2"></i>Salida de Parqueadero</h1>
                    <p class="lead">Registro de salidas del parqueadero</p>
                </div>

                <!-- Selector de Parqueadero -->
                <div class="scanner-container p-4 mb-4">
                    <div class="text-center mb-3">
                        <h4 class="text-danger"><i class="fas fa-parking me-2"></i>Seleccionar Parqueadero</h4>
                        <p class="text-muted">Elija el parqueadero donde registrará las salidas</p>
                    </div>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="select-parqueadero" class="form-label"><strong>Parqueadero Actual:</strong></label>
                                <select class="form-select form-select-lg" id="select-parqueadero">
                                    <option value="">-- Seleccione un parqueadero --</option>
                                    <option value="1">🏢 Parqueadero 1 - Principal</option>
                                    <option value="2">🚗 Parqueadero 2 - Secundario</option>
                                </select>
                            </div>
                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Escanee el código QR del usuario para registrar su salida
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área de Escaneo -->
                <div id="scanner-section" class="scanner-container p-4 mb-4" style="display: none;">
                    <div class="text-center mb-3">
                        <h4 class="text-danger">
                            <i class="fas fa-camera me-2"></i>Escanear QR de Salida
                            <span id="current-parqueadero" class="badge bg-danger parqueadero-badge ms-2"></span>
                        </h4>
                        <p class="text-muted">Enfoca el código QR dentro del marco</p>
                    </div>
                    
                    <div id="reader">
                        <div class="text-center py-5 loading-text">
                            <i class="fas fa-camera fa-2x mb-3"></i><br>
                            Presiona "Iniciar Cámara" para comenzar
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button id="btn-start" class="btn btn-success btn-lg">
                            <i class="fas fa-play me-2"></i>Iniciar Cámara
                        </button>
                        <button id="btn-stop" class="btn btn-danger btn-lg" style="display: none;">
                            <i class="fas fa-stop me-2"></i>Detener Cámara
                        </button>
                    </div>
                </div>

                <!-- Resultado del Escaneo -->
                <div id="result-container" class="user-card hidden p-4">
                    <div class="text-center mb-3">
                        <h4 class="text-success"><i class="fas fa-check-circle me-2"></i>Salida Registrada</h4>
                        <p class="text-muted" id="result-parqueadero"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Nombre:</strong>
                                <div id="user-name" class="fw-bold text-dark"></div>
                            </div>
                            <div class="mb-3">
                                <strong>Cédula:</strong>
                                <div id="user-cedula" class="text-muted"></div>
                            </div>
                            <div class="mb-3">
                                <strong>Tipo de Usuario:</strong>
                                <span id="user-type" class="badge bg-info"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Placa del Vehículo:</strong>
                                <div id="vehicle-placa" class="placa-display"></div>
                            </div>
                            <div class="mb-3">
                                <strong>Tipo de Vehículo:</strong>
                                <div id="vehicle-type" class="fw-bold"></div>
                            </div>
                            <div class="mb-3">
                                <strong>Hora de Salida:</strong>
                                <div id="access-time" class="text-success fw-bold"></div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button id="btn-new-scan" class="btn btn-primary btn-lg">
                            <i class="fas fa-redo me-2"></i>Nueva Salida
                        </button>
                    </div>
                </div>

                <!-- Mensaje de Error -->
                <div id="error-container" class="alert alert-danger hidden text-center">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Error</h5>
                    <p id="error-message"></p>
                    <button id="btn-retry" class="btn btn-warning">
                        <i class="fas fa-sync me-2"></i>Reintentar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Esperar a que la librería esté disponible
        function initScanner() {
            if (typeof Html5QrcodeScanner === 'undefined') {
                console.error('Librería QR no cargada');
                document.getElementById('error-message').textContent = 'Error: No se pudo cargar el lector QR. Recarga la página.';
                document.getElementById('error-container').classList.remove('hidden');
                return;
            }

            console.log('Librería QR cargada correctamente');

            // Manejar selección de parqueadero
            const parqueaderoSelect = document.getElementById('select-parqueadero');
            const scannerSection = document.getElementById('scanner-section');
            const currentParqueaderoBadge = document.getElementById('current-parqueadero');

            parqueaderoSelect.addEventListener('change', function() {
                const parqueaderoId = this.value;
                const selectedText = this.options[this.selectedIndex].text;
                
                if (parqueaderoId) {
                    scannerSection.style.display = 'block';
                    currentParqueaderoBadge.textContent = selectedText.replace('-- Seleccione un parqueadero --', '').trim();
                    
                    // Scroll suave al scanner
                    setTimeout(() => {
                        scannerSection.scrollIntoView({ behavior: 'smooth' });
                    }, 300);
                } else {
                    scannerSection.style.display = 'none';
                    currentParqueaderoBadge.textContent = '';
                }
            });

            class QRScanner {
                constructor() {
                    this.html5QrcodeScanner = null;
                    this.isScanning = false;
                    this.initializeElements();
                    this.initializeEventListeners();
                }

                initializeElements() {
                    this.readerElement = document.getElementById('reader');
                    this.btnStart = document.getElementById('btn-start');
                    this.btnStop = document.getElementById('btn-stop');
                    this.btnNewScan = document.getElementById('btn-new-scan');
                    this.btnRetry = document.getElementById('btn-retry');
                    this.resultContainer = document.getElementById('result-container');
                    this.errorContainer = document.getElementById('error-container');
                }

                initializeEventListeners() {
                    this.btnStart.addEventListener('click', () => this.startScanner());
                    this.btnStop.addEventListener('click', () => this.stopScanner());
                    this.btnNewScan.addEventListener('click', () => this.resetScanner());
                    this.btnRetry.addEventListener('click', () => this.resetScanner());
                }

                async startScanner() {
                    // Verificar que hay un parqueadero seleccionado
                    const parqueaderoId = document.getElementById('select-parqueadero').value;
                    if (!parqueaderoId) {
                        this.showError('Por favor seleccione un parqueadero primero');
                        return;
                    }

                    try {
                        // Limpiar contenido inicial
                        this.readerElement.innerHTML = '';
                        
                        this.html5QrcodeScanner = new Html5QrcodeScanner(
                            "reader", 
                            { 
                                fps: 10, 
                                qrbox: { width: 250, height: 250 },
                                supportedScanTypes: []  // Escanear todos los tipos
                            }, 
                            false
                        );

                        await this.html5QrcodeScanner.render(
                            (decodedText) => this.onScanSuccess(decodedText),
                            (errorMessage) => this.onScanFailure(errorMessage)
                        );

                        this.isScanning = true;
                        this.btnStart.style.display = 'none';
                        this.btnStop.style.display = 'inline-block';
                        this.readerElement.classList.add('scan-animation');

                    } catch (error) {
                        this.showError('Error al iniciar la cámara: ' + error.message);
                    }
                }

                stopScanner() {
                    if (this.html5QrcodeScanner && this.isScanning) {
                        this.html5QrcodeScanner.clear().catch(error => {
                            console.error("Error al detener scanner:", error);
                        });
                        this.isScanning = false;
                    }
                    
                    this.btnStart.style.display = 'inline-block';
                    this.btnStop.style.display = 'none';
                    this.readerElement.classList.remove('scan-animation');
                    
                    // Restaurar mensaje inicial
                    this.readerElement.innerHTML = '<div class="text-center py-5 loading-text"><i class="fas fa-camera fa-2x mb-3"></i><br>Presiona "Iniciar Cámara" para comenzar</div>';
                }

                async onScanSuccess(decodedText) {
                    try {
                        console.log("QR escaneado:", decodedText);
                        
                        // PROCESAR TU FORMATO QR: "PARQ:usuario_id:hash"
                        const qrParts = decodedText.split(':');
                        
                        if (qrParts.length !== 3 || qrParts[0] !== 'PARQ') {
                            throw new Error('Formato QR inválido');
                        }
                        
                        const usuario_id = qrParts[1];
                        const hash = qrParts[2];
                        
                        // Detener scanner temporalmente
                        this.stopScanner();
                        
                        // Procesar la SALIDA
                        await this.processSalida(usuario_id, hash);
                        
                    } catch (error) {
                        this.showError('Código QR inválido: ' + error.message);
                    }
                }

                onScanFailure(error) {
                    // Errores de escaneo normales, no mostrar error
                    console.log("Escaneo en progreso:", error);
                }

                async processSalida(usuario_id, hash) {
                    try {
                        const parqueaderoId = document.getElementById('select-parqueadero').value;
                        const parqueaderoText = document.getElementById('select-parqueadero').options[document.getElementById('select-parqueadero').selectedIndex].text;
                        
                        if (!parqueaderoId) {
                            this.showError('Por favor seleccione un parqueadero primero');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('usuario_id', usuario_id);
                        formData.append('hash', hash);
                        formData.append('parqueadero_id', parqueaderoId);
                        formData.append('action', 'registrar_salida');

                        const response = await fetch('procesar_salida.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Agregar información del parqueadero al resultado
                            result.data.parqueadero = parqueaderoText;
                            this.showSuccess(result.data);
                        } else {
                            this.showError(result.message || 'Error al procesar la salida');
                        }

                    } catch (error) {
                        this.showError('Error de conexión: ' + error.message);
                    }
                }

                showSuccess(userData) {
                    document.getElementById('user-name').textContent = userData.nombre_completo;
                    document.getElementById('user-cedula').textContent = userData.cedula;
                    document.getElementById('user-type').textContent = userData.tipo;
                    document.getElementById('vehicle-placa').textContent = userData.placa;
                    document.getElementById('vehicle-type').textContent = userData.tipo_vehiculo;
                    document.getElementById('access-time').textContent = new Date().toLocaleString();
                    document.getElementById('result-parqueadero').textContent = userData.parqueadero;

                    this.resultContainer.classList.remove('hidden');
                    this.errorContainer.classList.add('hidden');
                }

                showError(message) {
                    document.getElementById('error-message').textContent = message;
                    this.errorContainer.classList.remove('hidden');
                    this.resultContainer.classList.add('hidden');
                }

                resetScanner() {
                    this.resultContainer.classList.add('hidden');
                    this.errorContainer.classList.add('hidden');
                    this.stopScanner();
                    setTimeout(() => this.startScanner(), 1000);
                }
            }

            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    new QRScanner();
                });
            } else {
                new QRScanner();
            }
        }

        // Inicializar cuando la librería esté lista
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScanner);
        } else {
            initScanner();
        }
    </script>
</body>
</html>