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
    <title>Lector QR - Entrada Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CDN CORREGIDO -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .scanner-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        #reader { width: 100%; height: 300px; border: 3px dashed #007bff; border-radius: 10px; }
        .user-card { transition: all 0.3s ease; border: none; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .hidden { display: none; }
        .placa-display { font-family: 'Courier New', monospace; font-size: 1.5rem; font-weight: bold; letter-spacing: 2px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .scan-animation { animation: pulse 2s infinite; }
        @keyframes pulse { 
            0% { border-color: #007bff; } 
            50% { border-color: #28a745; } 
            100% { border-color: #007bff; } 
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center text-white mb-4">
                    <h1><i class="fas fa-qrcode me-2"></i>Lector de QR</h1>
                    <p class="lead">Escanea el código QR para registrar el acceso</p>
                </div>

                <div class="scanner-container p-4 mb-4">
                    <div class="text-center mb-3">
                        <h4 class="text-primary"><i class="fas fa-camera me-2"></i>Área de Escaneo</h4>
                        <p class="text-muted">Enfoca el código QR dentro del marco</p>
                    </div>
                    
                    <div id="reader" class="scan-animation"></div>
                    
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
                        <h4 class="text-success"><i class="fas fa-check-circle me-2"></i>Acceso Registrado</h4>
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
                                <strong>Hora de Acceso:</strong>
                                <div id="access-time" class="text-success fw-bold"></div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button id="btn-new-scan" class="btn btn-primary btn-lg">
                            <i class="fas fa-redo me-2"></i>Nuevo Escaneo
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
        // Verificar que la librería se cargó
        console.log('Html5QrcodeScanner disponible:', typeof Html5QrcodeScanner !== 'undefined');
        
        class QRScanner {
            constructor() {
                // Verificar que la librería esté disponible
                if (typeof Html5QrcodeScanner === 'undefined') {
                    this.showError('Error: No se pudo cargar la librería QR. Recarga la página.');
                    return;
                }
                
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
                try {
                    this.html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader", 
                        { 
                            fps: 10, 
                            qrbox: { width: 250, height: 250 },
                            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_QR]
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
            }

            async onScanSuccess(decodedText) {
                try {
                    console.log("QR escaneado:", decodedText);
                    
                    // PROCESAR TU FORMATO QR ACTUAL: "PARQ:usuario_id:hash"
                    const qrParts = decodedText.split(':');
                    
                    if (qrParts.length !== 3 || qrParts[0] !== 'PARQ') {
                        throw new Error('Formato QR inválido');
                    }
                    
                    const usuario_id = qrParts[1];
                    const hash = qrParts[2];
                    
                    // Detener scanner temporalmente
                    this.stopScanner();
                    
                    // Procesar el acceso
                    await this.processAccess(usuario_id, hash);
                    
                } catch (error) {
                    this.showError('Código QR inválido: ' + error.message);
                }
            }

            onScanFailure(error) {
                // Errores de escaneo se manejan silenciosamente
                console.log("Error de escaneo:", error);
            }

            async processAccess(usuario_id, hash) {
                try {
                    const formData = new FormData();
                    formData.append('usuario_id', usuario_id);
                    formData.append('hash', hash);
                    formData.append('action', 'registrar_acceso');

                    const response = await fetch('procesar_acceso.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess(result.data);
                    } else {
                        this.showError(result.message || 'Error al procesar el acceso');
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
                setTimeout(() => this.startScanner(), 500);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new QRScanner();
        });
    </script>
</body>
</html>