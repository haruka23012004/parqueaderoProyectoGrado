<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Parqueadero Universitario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 40px;
            font-size: 1.1em;
        }

        h2 {
            color: #3498db;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-top: 40px;
            margin-bottom: 25px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 i {
            background: #3498db;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9em;
        }

        h3 {
            color: #2c3e50;
            margin: 20px 0 15px 0;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0;
            border: 2px solid #e8eeef;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            margin-top: 30px;
            width: 100%;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .hidden {
            display: none;
        }

        .consentimiento {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .file-input-container {
            position: relative;
            margin: 15px 0;
        }

        .file-input-label {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px dashed #bdc3c7;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            top: 0;
            left: 0;
        }

        .file-info {
            margin-top: 8px;
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
        }

        .photo-requirement {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px 15px;
            margin: 10px 0;
            font-size: 14px;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photo-requirement i {
            color: #f39c12;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .btn-back {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #7f8c8d;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            z-index: 1;
        }

        .input-with-icon input, .input-with-icon select {
            padding-left: 45px;
        }

        /* ESTILO SIMPLE PARA INPUT FILE - FUNCIONAL */
        .simple-file-input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 2px solid #e8eeef;
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }

        .simple-file-input:focus {
            border-color: #3498db;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1><i class="fas fa-parking"></i> Registro de Parqueadero</h1>
        <p class="subtitle">Complete el formulario para solicitar acceso al parqueadero universitario</p>
        
        <form action="procesar_registro.php" method="POST" enctype="multipart/form-data">
            
            <!-- Sección 1: Tipo de Usuario -->
            <div class="form-section">
                <h2><i class="fas fa-user-tag"></i> Tipo de Usuario</h2>
                <div class="input-with-icon">
                    <i class="fas fa-users"></i>
                    <select name="tipo" id="tipo-usuario" required>
                        <option value="">Seleccione su tipo</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="profesor">Profesor</option>
                        <option value="administrativo">Personal Administrativo</option>
                        <option value="visitante">Visitante</option>
                    </select>
                </div>
            </div>

            <!-- Sección 2: Datos Personales -->
            <div class="form-section">
                <h2><i class="fas fa-id-card"></i> Datos Personales</h2>
                
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nombre_completo" placeholder="Nombre completo" required>
                </div>
                
                <div class="input-with-icon">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="cedula" placeholder="Cédula" required>
                </div>
                
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email institucional o personal para visitantes" required>
                </div>

                <!-- Campos condicionales para estudiantes -->
                <div id="campos-universitarios" class="hidden">
                    <h3><i class="fas fa-graduation-cap"></i> Datos Universitarios</h3>
                    
                    <div class="input-with-icon">
                        <i class="fas fa-barcode"></i>
                        <input type="text" name="codigo_universitario" placeholder="Código universitario">
                    </div>
                    
                    <div class="input-with-icon">
                        <i class="fas fa-university"></i>
                        <input type="text" name="facultad" placeholder="Facultad">
                    </div>
                    
                    <div class="input-with-icon">
                        <i class="fas fa-book"></i>
                        <input type="text" name="programa_academico" placeholder="Carrera/Departamento">
                    </div>
                </div>

                <!-- Semestre solo para estudiantes -->
                <div id="campo-semestre" class="hidden">
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="text" name="semestre" placeholder="Semestre (ej: 2024-1)">
                    </div>
                </div>
            </div>

            <!-- Sección 3: Foto de Perfil -->
            <div class="form-section">
                <h2><i class="fas fa-camera"></i> Foto de Perfil</h2>
                
                <!-- INPUT FILE SIMPLE Y FUNCIONAL -->
                <input type="file" name="foto_usuario" accept="image/*" class="simple-file-input" required>
                <div class="file-info">
                    <i class="fas fa-lightbulb"></i> Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 5MB
                </div>
                
                <div class="consentimiento">
                    <input type="checkbox" name="consentimiento_imagen" id="consentimiento" required>
                    <label for="consentimiento">Autorizo el tratamiento de mi imagen para fines de identificación en el sistema de parqueadero.</label>
                </div>
            </div>

            <!-- Sección 4: Vehículo -->
            <div class="form-section">
                <h2><i class="fas fa-car"></i> Datos del Vehículo</h2>
                
                <div class="input-with-icon">
                    <i class="fas fa-car-side"></i>
                    <select name="tipo_vehiculo" required>
                        <option value="">Seleccione tipo de vehículo</option>
                        <option value="bicicleta">Bicicleta</option>
                        <option value="motocicleta">Motocicleta</option>
                        <option value="carro">Carro</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div class="input-with-icon">
                    <i class="fas fa-list-alt"></i>
                    <input type="text" name="placa" placeholder="Placa (si aplica)">
                </div>
                
                <div class="input-with-icon">
                    <i class="fas fa-industry"></i>
                    <input type="text" name="marca_vehiculo" placeholder="Marca">
                </div>
                
                <div class="input-with-icon">
                    <i class="fas fa-palette"></i>
                    <input type="text" name="color_vehiculo" placeholder="Color">
                </div>

                <!-- Nueva sección: Foto del vehículo -->
                <h3><i class="fas fa-camera-retro"></i> Foto del Vehículo</h3>
                
                <div class="photo-requirement">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Requisito importante:</strong> Por favor, suba una fotografía clara de su vehículo donde sea visible la placa de identificación. Esto nos ayudará a verificar la información proporcionada.
                    </div>
                </div>
                
                <!-- INPUT FILE SIMPLE Y FUNCIONAL -->
                <input type="file" name="foto_vehiculo" accept="image/*" class="simple-file-input" required>
                <div class="file-info">
                    <i class="fas fa-lightbulb"></i> Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 5MB
                </div>
            </div>

            <button type="submit">
                <i class="fas fa-paper-plane me-2"></i> Enviar Solicitud
            </button>

            <div style="text-align: center;">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Regresar al Inicio
                </a>
            </div>
        </form>
    </div>

    <script src="assets/js/registro.js"></script>
</body>
</html>