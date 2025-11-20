<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if ($_POST['action'] !== 'buscar_usuario') {
    echo json_encode(['success' => false, 'message' => 'Acción inválida']);
    exit();
}

$cedula = trim($_POST['cedula']);

try {
    // Buscar usuario por cédula
    $query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.id as vehiculo_id
              FROM usuarios_parqueadero u 
              INNER JOIN vehiculos v ON u.id = v.usuario_id 
              WHERE u.cedula = ? AND u.estado = 'aprobado' AND u.acceso_activo = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o no autorizado']);
        exit();
    }
    
    $userData = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'nombre_completo' => $userData['nombre_completo'],
            'cedula' => $userData['cedula'],
            'tipo' => $userData['tipo'],
            'placa' => $userData['placa'],
            'tipo_vehiculo' => $userData['tipo_vehiculo']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()]);
}
?>