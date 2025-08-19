<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Parqueadero Universitario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-top: 30px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .hidden {
            display: none;
        }
        .consentimiento {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Registro de Parqueadero</h1>
    <form action="procesar_registro.php" method="POST" enctype="multipart/form-data">
        
        <!-- Sección 1: Tipo de Usuario -->
        <h2>1. Tipo de Usuario</h2>
        <select name="tipo" id="tipo-usuario" required>
            <option value="">Seleccione su tipo*</option>
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
            <option value="administrativo">Personal Administrativo</option>
            <option value="visitante">Visitante</option>
        </select>

        <!-- Sección 2: Datos Personales -->
        <h2>2. Datos Personales</h2>
        <input type="text" name="nombre_completo" placeholder="Nombre completo*" required>
        <input type="text" name="cedula" placeholder="Cédula*" required>
        <input type="email" name="email" placeholder="Email institucional*" required>

        <!-- Campos condicionales para estudiantes -->
        <div id="campos-universitarios" class="hidden">
            <h3>Datos Universitarios</h3>
            <input type="text" name="codigo_universitario" placeholder="Código universitario*">
            <input type="text" name="facultad" placeholder="Facultad">
            <input type="text" name="programa_academico" placeholder="Carrera/Departamento">
        </div>

        <!-- Semestre solo para estudiantes -->
        <div id="campo-semestre" class="hidden">
            <input type="text" name="semestre" placeholder="Semestre* (ej: 2024-1)">
        </div>

        <!-- Sección 3: Foto de Perfil -->
        <h2>3. Foto de Perfil</h2>
        <input type="file" name="foto_usuario" accept="image/*">
        <div class="consentimiento">
            <input type="checkbox" name="consentimiento_imagen" id="consentimiento" required>
            <label for="consentimiento">Autorizo el tratamiento de mi imagen para fines de identificación en el sistema de parqueadero.</label>
        </div>

        <!-- Sección 4: Vehículo -->
        <h2>4. Datos del Vehículo</h2>
        <select name="tipo_vehiculo" required>
            <option value="">Seleccione tipo de vehículo*</option>
            <option value="bicicleta">Bicicleta</option>
            <option value="motocicleta">Motocicleta</option>
            <option value="carro">Carro</option>
            <option value="otro">Otro</option>
        </select>
        <input type="text" name="placa" placeholder="Placa (si aplica)">
        <input type="text" name="marca_vehiculo" placeholder="Marca">
        <input type="text" name="color_vehiculo" placeholder="Color">

        <button type="submit">Enviar Solicitud</button>

        <a href="index.php" class="btn btn-back animate__animated animate__fadeInLeft">
            <i class="fas fa-arrow-left me-2"></i> Regresar al Inicio
        </a>
    </form>

    
<script src="assets/js/registro.js"></script>

</body>
</html>