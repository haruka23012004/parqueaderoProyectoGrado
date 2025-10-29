<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: ../paneles/administrador.php');
    exit();
}

// Construir consulta base con filtros
$where_conditions = ["u.estado = 'rechazado'"];
$params = [];
$types = "";

// Aplicar filtros si existen
if (isset($_GET['busqueda']) && !empty(trim($_GET['busqueda']))) {
    $busqueda = "%" . trim($_GET['busqueda']) . "%";
    $where_conditions[] = "(u.nombre_completo LIKE ? OR u.cedula LIKE ? OR u.email LIKE ?)";
    $params = array_merge($params, [$busqueda, $busqueda, $busqueda]);
    $types .= "sss";
}

// Construir consulta
$query = "SELECT 
            u.*, 
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo,
            e.nombre_completo as nombre_rechazador
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          LEFT JOIN empleados e ON u.aprobado_por = e.id
          WHERE " . implode(" AND ", $where_conditions) . "
          ORDER BY u.fecha_aprobacion DESC";

// Preparar y ejecutar consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Procesar reactivación de usuario
if (isset($_GET['reactivar'])) {
    $usuario_id = intval($_GET['reactivar']);
    
    $reactivar_query = "UPDATE usuarios_parqueadero 
                       SET estado = 'pendiente', 
                           observaciones = NULL,
                           fecha_aprobacion = NULL,
                           aprobado_por = NULL
                       WHERE id = ?";
    
    $reactivar_stmt = $conn->prepare($reactivar_query);
    $reactivar_stmt->bind_param("i", $usuario_id);
    
    if ($reactivar_stmt->execute()) {
        header('Location: usuarios_rechazados.php?msg=Usuario reactivado correctamente');
        exit();
    } else {
        header('Location: usuarios_rechazados.php?error=Error al reactivar usuario');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Rechazados - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .badge-estado {
            font-size: 0.85rem;
        }
        .user-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
        }
        .action-buttons {
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .action-buttons:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-times me-2"></i>Usuarios Rechazados</h2>
            <div>
                <a href="solicitudes_pendientes.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-clock me-1"></i> Solicitudes Pendientes
                </a>
                <a href="usuarios_aprobados.php" class="btn btn-outline-success">
                    <i class="fas fa-user-check me-1"></i> Usuarios Aprobados
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros y búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               placeholder="Nombre, cédula o email" value="<?= isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '' ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (count($usuarios) > 0): ?>
            <div class="row">
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 card-hover">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-danger badge-estado">Rechazado</span>
                                <small class="text-muted">#<?= $usuario['id'] ?></small>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($usuario['foto_usuario']): ?>
                                        <img src="../<?= $usuario['foto_usuario'] ?>" alt="Foto usuario" class="user-img me-3">
                                    <?php else: ?>
                                        <div class="user-img bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($usuario['nombre_completo']) ?></h5>
                                        <p class="text-muted mb-0"><?= ucfirst($usuario['tipo']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-id-card me-1"></i> Información Personal</h6>
                                    <p class="mb-1"><strong>Cédula:</strong> <?= htmlspecialchars($usuario['cedula']) ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                                    <?php if ($usuario['codigo_universitario']): ?>
                                        <p class="mb-1"><strong>Código:</strong> <?= $usuario['codigo_universitario'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($usuario['tipo_vehiculo']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="fas fa-car me-1"></i> Vehículo</h6>
                                        <p class="mb-1"><strong>Tipo:</strong> <?= ucfirst($usuario['tipo_vehiculo']) ?></p>
                                        <?php if ($usuario['placa']): ?>
                                            <p class="mb-1"><strong>Placa:</strong> <?= $usuario['placa'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-calendar-times me-1"></i> Información de Rechazo</h6>
                                    <p class="mb-1"><strong>Fecha rechazo:</strong> 
                                        <?= date('d/m/Y H:i', strtotime($usuario['fecha_aprobacion'])) ?>
                                    </p>
                                    <?php if ($usuario['observaciones']): ?>
                                        <p class="mb-1"><strong>Razón:</strong> <?= htmlspecialchars($usuario['observaciones']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($usuario['nombre_rechazador']): ?>
                                        <p class="mb-1"><strong>Rechazado por:</strong> <?= $usuario['nombre_rechazador'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                                    </span>
                                    <div class="action-buttons">
                                        <a href="usuarios_rechazados.php?reactivar=<?= $usuario['id'] ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('¿Estás seguro de reactivar este usuario? Volverá a estado pendiente.')">
                                            <i class="fas fa-undo me-1"></i> Reactivar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-user-times fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay usuarios rechazados</h4>
                <p class="text-muted">No se han rechazado solicitudes o no hay resultados con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>