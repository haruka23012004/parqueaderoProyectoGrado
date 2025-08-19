<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
            padding: 40px 30px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .parking-icon {
            font-size: 50px;
            color: #2196F3;
            margin: 15px 0;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        p {
            color: #7f8c8d;
            margin-bottom: 25px;
        }
        
        .btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0d8bf2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h1>¡Registro Exitoso!</h1>
        <div class="parking-icon">🚗</div>
        <p>Tu solicitud para el parqueadero universitario ha sido recibida. El administrador la revisará y te notificará por correo.</p>
        <button class="btn" onclick="window.location.href='index.php'">Volver al inicio</button>
    </div>

    <script>
        // Efecto adicional al cargar
        document.querySelector('.success-card').style.transform = 'scale(1.05)';
        setTimeout(() => {
            document.querySelector('.success-card').style.transform = 'scale(1)';
        }, 300);
        
        // Sonido opcional de éxito (descomentar para activar)
        /*
        window.onload = function() {
            var audio = new Audio('success-sound.mp3');
            audio.volume = 0.3;
            audio.play();
        }
        */
    </script>
</body>
</html>