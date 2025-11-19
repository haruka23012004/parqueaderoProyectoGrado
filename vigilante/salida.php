<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registro de Salida - Parqueadero</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #reader { width: 500px; margin: 20px auto; }
        #result { margin: 20px; padding: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h2>📤 Registro de Salida</h2>
    <div id="reader"></div>
    <div id="result"></div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Detener el scanner temporalmente
            html5QrcodeScanner.pause();
            
            const data = JSON.parse(decodedText);
            
            fetch('procesar_salida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=registrar_salida&usuario_id=${data.usuario_id}&hash=${data.hash}&parqueadero_id=${data.parqueadero_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('result').innerHTML = `
                        <div class="success">
                            <h3>✅ Salida registrada exitosamente</h3>
                            <p><strong>Nombre:</strong> ${data.data.nombre_completo}</p>
                            <p><strong>Cédula:</strong> ${data.data.cedula}</p>
                            <p><strong>Vehículo:</strong> ${data.data.placa} - ${data.data.tipo_vehiculo}</p>
                            <p><strong>Parqueadero:</strong> ${data.data.parqueadero}</p>
                            <p><strong>Tipo:</strong> ${data.data.tipo}</p>
                        </div>
                    `;
                } else {
                    document.getElementById('result').innerHTML = `
                        <div class="error">
                            <h3>❌ Error</h3>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
                
                // Reanudar el scanner después de 3 segundos
                setTimeout(() => {
                    html5QrcodeScanner.resume();
                    document.getElementById('result').innerHTML = '';
                }, 3000);
            });
        }

        function onScanFailure(error) {
            // console.warn(`Error al escanear: ${error}`);
        }

        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { 
                fps: 10, 
                qrbox: { width: 250, height: 250 } 
            });
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
</body>
</html>