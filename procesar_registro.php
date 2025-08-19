<?php

require __DIR__ . '/includes/conexion.php';

// Crear alias para mantener compatibilidad
$conexion = $conn; 

// Verificación de conexión
if (!isset($conexion) || $conexion->connect_error) {
    die("Error crítico: No se pudo conectar a la base de datos");
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
    die("Error: Método no permitido");
}

// Validar consentimiento
if (!isset($_POST['consentimiento_imagen'])) {
    die("Debe aceptar el tratamiento de imágenes");
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
        die("Error: El campo {$nombre} es obligatorio");
    }
}

// Validar tipo de usuario
$tipos_permitidos = ['estudiante', 'profesor', 'administrativo', 'visitante'];
if (!in_array($_POST['tipo'], $tipos_permitidos)) {
    die("Error: Tipo de usuario no válido");
}

// Validar email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    die("Error: Formato de email inválido");
}

// =============================================
// 3. PROCESAMIENTO DE IMAGEN
// =============================================
$foto_usuario = null;

if ($_FILES['foto_usuario']['error'] === UPLOAD_ERR_OK) {
    // Verificar errores de subida
    if ($_FILES['foto_usuario']['error'] !== UPLOAD_ERR_OK) {
        die("Error al subir la imagen: Código " . $_FILES['foto_usuario']['error']);
    }

    // Validar tipo de archivo
    $extension = strtolower(pathinfo($_FILES['foto_usuario']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones_permitidas)) {
        die("Error: Solo se permiten imágenes JPG, JPEG o PNG");
    }

    // Validar tamaño
    if ($_FILES['foto_usuario']['size'] > $max_tamano_imagen) {
        die("Error: La imagen no debe exceder 2MB");
    }

    // Crear nombre único
    $nombre_foto = 'foto_' . md5($_POST['cedula'] . time()) . '.' . $extension;
    $ruta_foto = $ruta_imagenes . $nombre_foto;

    // Verificar y crear carpeta si no existe
    if (!file_exists($ruta_imagenes)) {
        if (!mkdir($ruta_imagenes, 0755, true)) {
            die("Error: No se pudo crear la carpeta de imágenes");
        }
    }

    // Mover archivo
    if (!move_uploaded_file($_FILES['foto_usuario']['tmp_name'], $ruta_foto)) {
        die("Error: No se pudo guardar la imagen. Verifica los permisos de la carpeta");
    }

    $foto_usuario = 'assets/img/usuarios/' . $nombre_foto; // Ruta relativa
}

// =============================================
// 4. VALIDACIONES CONDICIONALES
// =============================================
if (in_array($_POST['tipo'], ['estudiante']) && empty($_POST['codigo_universitario'])) {
    die("Error: El código universitario es obligatorio");
}

// =============================================
// 5. REGISTRO EN BASE DE DATOS
// =============================================
try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Insertar usuario
    $stmt_usuario = $conexion->prepare("
        INSERT INTO usuarios_parqueadero (
            tipo, codigo_universitario, nombre_completo, cedula, email,
            facultad, semestre, programa_academico, foto_usuario, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");

    // 1. Primero asigna los valores POST a variables normales
$tipo = $_POST['tipo'];
$codigo = $_POST['codigo_universitario'] ?? null;
$nombre = $_POST['nombre_completo'];
$cedula = $_POST['cedula'];
$email = $_POST['email'];
$facultad = $_POST['facultad'] ?? null;
$semestre = $_POST['semestre'] ?? null;
$programa = $_POST['programa_academico'] ?? null;

// 2. Luego usa esas variables en bind_param()
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

    $usuario_id = $conexion->insert_id;

    // Insertar vehículo
    $stmt_vehiculo = $conexion->prepare("
        INSERT INTO vehiculos (
            usuario_id, tipo, placa, marca, color
        ) VALUES (?, ?, ?, ?, ?)
    ");

    // Para el vehículo
$tipo_vehiculo = $_POST['tipo_vehiculo'];
$placa = $_POST['placa'] ?? null;
$marca = $_POST['marca_vehiculo'] ?? null;
$color = $_POST['color_vehiculo'] ?? null;

$stmt_vehiculo->bind_param(
    "issss",
    $usuario_id,
    $tipo_vehiculo,
    $placa,
    $marca,
    $color
);

    if (!$stmt_vehiculo->execute()) {
        throw new Exception("Error al registrar vehículo: " . $stmt_vehiculo->error);
    }

    // Confirmar transacción
    $conexion->commit();

    // Redirigir a página de éxito
    header('Location: registro_exitoso.php');
    exit;

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();

    // Eliminar imagen si se subió pero falló el registro
    if ($foto_usuario && file_exists(__DIR__ . '/' . $foto_usuario)) {
        unlink(__DIR__ . '/' . $foto_usuario);
    }

    die("Error: " . $e->getMessage());
}