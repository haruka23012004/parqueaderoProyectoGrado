<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if ($_POST['action'] !== 'registrar_salida') {
    echo json_encode(['success' => false, 'message' => 'Acción inválida']);
    exit();
}

$usuario_id = intval($_POST['usuario_id']);
$hash = trim($_POST['hash']);
$parqueadero_id = intval($_POST['parqueadero_id']);

try {
    // Verificar que el parqueadero existe
    $query_parqueadero = "SELECT id, nombre FROM parqueaderos WHERE id = ?";
    $stmt_parqueadero = $conn->prepare($query_parqueadero);
    $stmt_parqueadero->bind_param("i", $parqueadero_id);
    $stmt_parqueadero->execute();
    $parqueadero_result = $stmt_parqueadero->get_result();
    
    if ($parqueadero_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parqueadero no válido']);
        exit();
    }
    
    $parqueadero_data = $parqueadero_result->fetch_assoc();

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

    // Registrar la salida en la tabla registros_acceso
    $insertQuery = "INSERT INTO registros_acceso (usuario_id, vehiculo_id, parqueadero_id, tipo_movimiento, fecha_hora) 
                    VALUES (?, ?, ?, 'salida', NOW())";
    
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iii", $usuario_id, $userData['vehiculo_id'], $parqueadero_id);
    
    if ($insertStmt->execute()) {
        // ACTUALIZAR CAPACIDAD DEL PARQUEADERO (SUMAR 1)
        $updateQuery = "UPDATE parqueaderos 
                       SET capacidad_actual = capacidad_actual + 1 
                       WHERE id = ? AND capacidad_actual < capacidad_total";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $parqueadero_id);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'nombre_completo' => $userData['nombre_completo'],
                'cedula' => $userData['cedula'],
                'tipo' => $userData['tipo'],
                'placa' => $userData['placa'],
                'tipo_vehiculo' => $userData['tipo_vehiculo'],
                'parqueadero' => $parqueadero_data['nombre'],
                'mensaje' => 'Salida registrada exitosamente'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar salida: ' . $insertStmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()]);
}
?>