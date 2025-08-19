<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

// Consulta para obtener todos los usuarios aprobados con sus vehículos
$query = "SELECT 
            u.*, 
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo,
            e.nombre_completo as nombre_aprobador
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          LEFT JOIN empleados e ON u.aprobado_por = e.id
          WHERE u.estado = 'aprobado'
          ORDER BY u.fecha_aprobacion DESC";

$result = $conn->query($query);
$usuarios = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Aprobados - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .qr-img {
            max-width: 100px;
            height: auto;
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
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Usuarios Aprobados</h2>
            <a href="solicitudes_pendientes.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Solicitudes
            </a>
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
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               placeholder="Nombre, cédula o placa" value="<?= isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo de Usuario</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <option value="estudiante" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'estudiante') ? 'selected' : '' ?>>Estudiante</option>
                            <option value="profesor" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'profesor') ? 'selected' : '' ?>>Profesor</option>
                            <option value="administrativo" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'administrativo') ? 'selected' : '' ?>>Administrativo</option>
                            <option value="visitante" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'visitante') ? 'selected' : '' ?>>Visitante</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="orden" class="form-label">Ordenar por</label>
                        <select class="form-select" id="orden" name="orden">
                            <option value="fecha_reciente" <?= (isset($_GET['orden']) && $_GET['orden'] == 'fecha_reciente') ? 'selected' : '' ?>>Fecha más reciente</option>
                            <option value="fecha_antigua" <?= (isset($_GET['orden']) && $_GET['orden'] == 'fecha_antigua') ? 'selected' : '' ?>>Fecha más antigua</option>
                            <option value="nombre" <?= (isset($_GET['orden']) && $_GET['orden'] == 'nombre') ? 'selected' : '' ?>>Nombre A-Z</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
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
                                <span class="badge bg-success badge-estado">Aprobado</span>
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
                                    <?php if ($usuario['facultad']): ?>
                                        <p class="mb-1"><strong>Facultad:</strong> <?= $usuario['facultad'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($usuario['tipo_vehiculo']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="fas fa-car me-1"></i> Vehículo</h6>
                                        <p class="mb-1"><strong>Tipo:</strong> <?= ucfirst($usuario['tipo_vehiculo']) ?></p>
                                        <?php if ($usuario['placa']): ?>
                                            <p class="mb-1"><strong>Placa:</strong> <?= $usuario['placa'] ?></p>
                                        <?php endif; ?>
                                        <?php if ($usuario['marca']): ?>
                                            <p class="mb-1"><strong>Marca/Modelo:</strong> <?= $usuario['marca'] ?></p>
                                        <?php endif; ?>
                                        <?php if ($usuario['color']): ?>
                                            <p class="mb-1"><strong>Color:</strong> <?= $usuario['color'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-calendar-check me-1"></i> Información de Aprobación</h6>
                                    <p class="mb-1"><strong>Fecha aprobación:</strong> 
                                        <?= date('d/m/Y H:i', strtotime($usuario['fecha_aprobacion'])) ?>
                                    </p>
                                    <?php if ($usuario['nombre_aprobador']): ?>
                                        <p class="mb-1"><strong>Aprobado por:</strong> <?= $usuario['nombre_aprobador'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($usuario['qr_code']): ?>
                                    <div class="text-center mt-3">
                                        <p class="mb-1"><strong>Código QR:</strong></p>
                                        <img src="../<?= $usuario['qr_code'] ?>" alt="Código QR" class="qr-img img-thumbnail">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                                    </span>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginación (si es necesaria en el futuro) -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Anterior</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Siguiente</a>
                    </li>
                </ul>
            </nav>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay usuarios aprobados</h4>
                <p class="text-muted">Cuando apruebes solicitudes, aparecerán aquí.</p>
                <a href="solicitudes_pendientes.php" class="btn btn-primary mt-2">
                    <i class="fas fa-list me-1"></i> Ver Solicitudes Pendientes
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>