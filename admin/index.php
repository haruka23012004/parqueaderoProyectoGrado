<?php
require '../includes/auth.php'; // Verifica si el admin está logueado
require '../includes/conexion.php';

$solicitudes = $conexion->query("
    SELECT u.*, v.tipo as tipo_vehiculo 
    FROM usuarios_parqueadero u
    LEFT JOIN vehiculos v ON u.id = v.usuario_id
    WHERE u.estado = 'pendiente'
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel Admin</title>
</head>
<body>
    <h1>Solicitudes Pendientes</h1>
    <table>
        <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
        <tr>
            <td><?= $solicitud['nombre_completo'] ?></td>
            <td><?= $solicitud['tipo_vehiculo'] ?? 'Sin vehículo' ?></td>
            <td>
                <a href="aprobar_usuario.php?id=<?= $solicitud['id'] ?>">
                    Aprobar
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>