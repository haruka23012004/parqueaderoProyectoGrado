<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if ($_POST['action'] !== 'registrar_acceso') {
    echo json_encode(['success' => false, 'message' => 'Acción inválida']);
    exit();
}

$usuario_id = intval($_POST['usuario_id']);
$hash = trim($_POST['hash']);

try {
    // Verificar que el usuario existe y está activo
    $query = "SELECT u.*, v.tipo as tipo_vehiculo, v.placa, v.id as vehiculo_id
              FROM usuarios_parqueadero u 
              INNER JOIN vehiculos v ON u.id = v.usuario_id 
              WHERE u.id = ? AND u.estado = 'aprobado' AND u.acceso_activo = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o no autorizado']);
        exit();
    }
    
    $userData = $result->fetch_assoc();
    
    // Registrar el acceso en TU tabla registros_acceso
    $insertQuery = "INSERT INTO registros_acceso (usuario_id, vehiculo_id, tipo_movimiento, fecha_hora) 
                    VALUES (?, ?, 'entrada', NOW())";
    
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ii", $usuario_id, $userData['vehiculo_id']);
    
    if ($insertStmt->execute()) {
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
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar acceso']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()]);
}
?>