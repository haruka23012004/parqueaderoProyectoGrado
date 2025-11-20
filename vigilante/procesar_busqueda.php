<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y rol - Permitir vigilante Y administrador
if (!estaAutenticado() || ($_SESSION['rol_nombre'] != 'vigilante' && $_SESSION['rol_nombre'] != 'administrador_principal')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if (!isset($_POST['action']) || $_POST['action'] !== 'buscar_usuario' || !isset($_POST['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit();
}

$cedula = trim($_POST['cedula']);

// Validar formato de cédula (solo números)
if (!preg_match('/^\d+$/', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'La cédula debe contener solo números']);
    exit();
}

try {
    // Buscar usuario por cédula con información de entrada activa
    $query = "SELECT 
                u.id, u.nombre_completo, u.cedula, u.tipo, u.estado, u.acceso_activo,
                v.tipo as tipo_vehiculo, v.placa, v.id as vehiculo_id,
                (SELECT COUNT(*) FROM registros_acceso ra 
                 WHERE ra.usuario_id = u.id AND ra.tipo_movimiento = 'entrada'
                 AND NOT EXISTS (
                     SELECT 1 FROM registros_acceso ra2 
                     WHERE ra2.usuario_id = u.id 
                     AND ra2.tipo_movimiento = 'salida' 
                     AND ra2.fecha_hora > ra.fecha_hora
                 )) as tiene_entrada_activa
              FROM usuarios_parqueadero u 
              INNER JOIN vehiculos v ON u.id = v.usuario_id 
              WHERE u.cedula = ? AND u.estado = 'aprobado' AND u.acceso_activo = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Usuario no encontrado. Verifique la cédula o contacte a administración.'
        ]);
        exit();
    }
    
    $userData = $result->fetch_assoc();
    
    // Verificar si el usuario está activo y aprobado
    if ($userData['estado'] !== 'aprobado' || $userData['acceso_activo'] != 1) {
        echo json_encode([
            'success' => false, 
            'message' => 'Usuario no autorizado para ingresar. Estado: ' . $userData['estado']
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $userData['id'],
            'nombre_completo' => $userData['nombre_completo'],
            'cedula' => $userData['cedula'],
            'tipo' => $userData['tipo'],
            'placa' => $userData['placa'],
            'tipo_vehiculo' => $userData['tipo_vehiculo'],
            'tiene_entrada_activa' => $userData['tiene_entrada_activa'] > 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en procesar_busqueda: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error del sistema. Por favor, intente nuevamente.'
    ]);
}
?>