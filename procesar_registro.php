<?php
require __DIR__ . '/includes/conexion.php';

// Verificacion de la conexion
if (!isset($conn) || $conn->connect_error) {
    mostrarError("Error crítico: No se pudo conectar a la base de datos");
}

// =============================================
// FUNCIÓN PARA MOSTRAR ERRORES EN VENTANA EMERGENTE
// =============================================
function mostrarError($mensaje) {
    echo "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error en Registro</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .modal-error {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
                overflow: hidden;
                animation: slideIn 0.3s ease;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .modal-header {
                background: #e74c3c;
                color: white;
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .modal-header h2 {
                margin: 0;
                font-size: 1.5em;
            }
            
            .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.3s;
            }
            
            .close-btn:hover {
                background: rgba(255,255,255,0.2);
            }
            
            .modal-body {
                padding: 30px;
                text-align: center;
            }
            
            .error-icon {
                font-size: 64px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            
            .error-message {
                font-size: 18px;
                color: #2c3e50;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            
            .back-btn {
                background: #3498db;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            
            .back-btn:hover {
                background: #2980b9;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class='modal-error'>
            <div class='modal-header'>
                <h2><i class='fas fa-exclamation-triangle'></i> Error</h2>
                <button class='close-btn' onclick='volverAlRegistro()'>
                    <i class='fas fa-times'></i>
                </button>
            </div>
            <div class='modal-body'>
                <div class='error-icon'>
                    <i class='fas fa-exclamation-circle'></i>
                </div>
                <div class='error-message'>
                    $mensaje
                </div>
                <button class='back-btn' onclick='volverAlRegistro()'>
                    <i class='fas fa-arrow-left me-2'></i> Volver al Registro
                </button>
            </div>
        </div>
        
        <script>
            function volverAlRegistro() {
                window.history.back();
            }
            
            // Cerrar con tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    volverAlRegistro();
                }
            });
            
            // Cerrar haciendo clic fuera del modal
            document.addEventListener('click', function(e) {
                if (e.target === document.body) {
                    volverAlRegistro();
                }
            });
        </script>
    </body>
    </html>
    ";
    exit;
}

// =============================================
// 1. CONFIGURACIONES INICIALES
// =============================================
$ruta_imagenes = __DIR__ . '/assets/img/usuarios/';
$extensiones_permitidas = ['jpg', 'jpeg', 'png'];
$max_tamano_imagen = 2 * 1024 * 1024; // 2MB

// =============================================
// 2. VALIDACIONES DE SEGURIDAD
// =============================================
// Verificar método de envío
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarError("Error: Método no permitido");
}

// Validar consentimiento
if (!isset($_POST['consentimiento_imagen'])) {
    mostrarError("Debe aceptar el tratamiento de imágenes");
}

// Campos obligatorios
$campos_requeridos = [
    'tipo' => 'Tipo de usuario',
    'nombre_completo' => 'Nombre completo',
    'cedula' => 'Cédula',
    'email' => 'Email',
    'tipo_vehiculo' => 'Tipo de vehículo'
];

foreach ($campos_requeridos as $campo => $nombre) {
    if (empty($_POST[$campo])) {
        mostrarError("Error: El campo <strong>$nombre</strong> es obligatorio");
    }
}

// Validar tipo de usuario
$tipos_permitidos = ['estudiante', 'profesor', 'administrativo', 'visitante'];
if (!in_array($_POST['tipo'], $tipos_permitidos)) {
    mostrarError("Error: Tipo de usuario no válido");
}

// Validar email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    mostrarError("Error: Formato de email inválido<br><br>Por favor ingrese un email válido");
}

// =============================================
// 3. VALIDACIONES DE DATOS EXISTENTES
// =============================================
// Verificar si la cédula ya existe
$check_cedula = $conn->prepare("SELECT id FROM usuarios_parqueadero WHERE cedula = ?");
$check_cedula->bind_param("s", $_POST['cedula']);
$check_cedula->execute();
if ($check_cedula->get_result()->num_rows > 0) {
    mostrarError("Error: La cédula <strong>{$_POST['cedula']}</strong> ya está registrada en el sistema");
}

// Validar placa según tipo de vehículo
if ($_POST['tipo_vehiculo'] !== 'bicicleta' && empty($_POST['placa'])) {
    mostrarError("Error: La placa es obligatoria para este tipo de vehículo");
}

// Si es bicicleta y no tiene placa, generar una única 
$placa = $_POST['placa'] ?? null;
if ($_POST['tipo_vehiculo'] === 'bicicleta' && empty($placa)) {
    $placa = 'BIC-' . substr(md5($_POST['cedula'] . time()), 0, 8);
}

// Verificar si la placa ya existe (excepto para bicicletas generadas)
if (!empty($placa) && $placa !== $_POST['placa']) {
    $check_placa = $conn->prepare("SELECT id FROM vehiculos WHERE placa = ?");
    $check_placa->bind_param("s", $placa);
    $check_placa->execute();
    if ($check_placa->get_result()->num_rows > 0) {
        mostrarError("Error: La placa <strong>$placa</strong> ya está registrada en el sistema");
    }
}

// =============================================
// VALIDACIONES CONDICIONALES PARA ESTUDIANTES
// =============================================
if ($_POST['tipo'] === 'estudiante') {
    if (empty(trim($_POST['codigo_universitario']))) {
        mostrarError("Error: El código universitario es obligatorio para estudiantes");
    }
    if (empty(trim($_POST['facultad']))) {
        mostrarError("Error: La facultad es obligatoria para estudiantes");
    }
    if (empty(trim($_POST['programa_academico']))) {
        mostrarError("Error: El programa académico es obligatorio para estudiantes");
    }
}

// =============================================
// 4. PROCESAMIENTO DE IMAGENES
// =============================================
$foto_usuario = null;
$foto_vehiculo = null;

// Función para procesar imágenes
function procesarImagen($archivo, $cedula, $prefijo, $ruta_imagenes, $extensiones_permitidas, $max_tamano_imagen) {
    if (isset($archivo) && $archivo['error'] === UPLOAD_ERR_OK) {
        // Validar tipo de archivo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensiones_permitidas)) {
            mostrarError("Error: Solo se permiten imágenes JPG, JPEG o PNG<br><br>Formato detectado: .$extension");
        }

        // Validar tamaño
        if ($archivo['size'] > $max_tamano_imagen) {
            mostrarError("Error: La imagen no debe exceder 2MB<br><br>Tamaño actual: " . round($archivo['size'] / 1024 / 1024, 2) . " MB");
        }

        // Crear nombre único
        $nombre_foto = $prefijo . '_' . md5($cedula . time() . $prefijo) . '.' . $extension;
        $ruta_foto = $ruta_imagenes . $nombre_foto;

        // Verificar y crear carpeta si no existe
        if (!file_exists($ruta_imagenes)) {
            if (!mkdir($ruta_imagenes, 0755, true)) {
                mostrarError("Error: No se pudo crear la carpeta de imágenes<br><br>Contacte al administrador del sistema");
            }
        }

        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_foto)) {
            mostrarError("Error: No se pudo guardar la imagen<br><br>Verifique los permisos de la carpeta o contacte al administrador");
        }

        return 'assets/img/usuarios/' . $nombre_foto; // Ruta relativa
    }
    return null;
}

// Procesar foto de usuario
$foto_usuario = procesarImagen(
    $_FILES['foto_usuario'] ?? null,
    $_POST['cedula'],
    'foto_usuario',
    $ruta_imagenes,
    $extensiones_permitidas,
    $max_tamano_imagen
);

// Procesar foto del vehículo
$foto_vehiculo = procesarImagen(
    $_FILES['foto_vehiculo'] ?? null,
    $_POST['cedula'],
    'foto_vehiculo',
    $ruta_imagenes,
    $extensiones_permitidas,
    $max_tamano_imagen
);

// Verificar que se subió la foto del vehículo
if (!$foto_vehiculo) {
    mostrarError("Error: La foto del vehículo es obligatoria<br><br>Por favor, suba una fotografía clara donde sea visible la placa");
}

// =============================================
// 5. PREPARAR DATOS PARA LA BASE DE DATOS
// =============================================
$tipo = $_POST['tipo'];

// Preparar datos universitarios - SOLO para estudiantes
if ($tipo === 'estudiante') {
    $codigo = !empty(trim($_POST['codigo_universitario'])) ? trim($_POST['codigo_universitario']) : null;
    $facultad = !empty(trim($_POST['facultad'])) ? trim($_POST['facultad']) : null;
    $semestre = !empty(trim($_POST['semestre'])) ? trim($_POST['semestre']) : null;
    $programa = !empty(trim($_POST['programa_academico'])) ? trim($_POST['programa_academico']) : null;
} else {
    // Para otros tipos de usuario, los campos universitarios son NULL
    $codigo = null;
    $facultad = null;
    $semestre = null;
    $programa = null;
}

$nombre = $_POST['nombre_completo'];
$cedula = $_POST['cedula'];
$email = $_POST['email'];

// =============================================
// 6. REGISTRO EN BASE DE DATOS
// =============================================
try {
    // Iniciar transacción
    $conn->begin_transaction();

    // Insertar usuario
    $stmt_usuario = $conn->prepare("
        INSERT INTO usuarios_parqueadero (
            tipo, codigo_universitario, nombre_completo, cedula, email,
            facultad, semestre, programa_academico, foto_usuario, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");

    $stmt_usuario->bind_param(
        "sssssssss",
        $tipo,
        $codigo,
        $nombre,
        $cedula,
        $email,
        $facultad,
        $semestre,
        $programa,
        $foto_usuario
    );

    if (!$stmt_usuario->execute()) {
        throw new Exception("Error al registrar usuario: " . $stmt_usuario->error);
    }

    $usuario_id = $conn->insert_id;

    // Preparar datos para vehículo
    $tipo_vehiculo = $_POST['tipo_vehiculo'];
    $marca = $_POST['marca_vehiculo'] ?? null;
    $color = $_POST['color_vehiculo'] ?? null;

    // Insertar vehículo
    $stmt_vehiculo = $conn->prepare("
        INSERT INTO vehiculos (
            usuario_id, tipo, placa, marca, color, foto_vehiculo
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt_vehiculo->bind_param(
        "isssss",
        $usuario_id,
        $tipo_vehiculo,
        $placa,
        $marca,
        $color,
        $foto_vehiculo
    );

    if (!$stmt_vehiculo->execute()) {
        throw new Exception("Error al registrar vehículo: " . $stmt_vehiculo->error);
    }

    // Confirmar transacción
    $conn->commit();

    // Redirigir a página de éxito
    header('Location: registro_exitoso.php');
    exit;

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    // Eliminar imágenes si se subieron pero falló el registro
    if ($foto_usuario && file_exists(__DIR__ . '/' . $foto_usuario)) {
        unlink(__DIR__ . '/' . $foto_usuario);
    }
    if ($foto_vehiculo && file_exists(__DIR__ . '/' . $foto_vehiculo)) {
        unlink(__DIR__ . '/' . $foto_vehiculo);
    }

    mostrarError("Error en el sistema: " . $e->getMessage() . "<br><br>Por favor, intente nuevamente");
}
?>