<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Consulta SIMPLIFICADA para evitar errores
$query = "SELECT 
            ra.id as registro_id,
            ra.tipo_movimiento,
            ra.fecha_hora,
            u.id as usuario_id,
            u.codigo_universitario,
            u.cedula,
            u.nombre_completo,
            u.tipo as tipo_usuario,
            v.placa,
            v.tipo as tipo_vehiculo,
            v.marca,
            v.color,
            p.nombre as parqueadero_nombre,
            p.id as parqueadero_id
          FROM registros_acceso ra
          INNER JOIN usuarios_parqueadero u ON ra.usuario_id = u.id
          INNER JOIN vehiculos v ON ra.vehiculo_id = v.id
          INNER JOIN parqueaderos p ON ra.parqueadero_id = p.id
          ORDER BY ra.fecha_hora DESC";

$result = $conn->query($query);

// Verificar si hay error en la consulta
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$registros = [];
while ($row = $result->fetch_assoc()) {
    $registros[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Vehículos - Sistema Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-entrada {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .badge-salida {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Encabezado -->
        <div class="row">
            <div class="col-12">
                <div class="header-section">
                    <h1><i class="fas fa-car me-2"></i>Consulta de Vehículos</h1>
                    <p class="mb-0">Historial completo de entradas y salidas del parqueadero</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?= count($registros) ?></div>
                    <div class="text-muted">Total Registros</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-success">
                        <?= count(array_filter($registros, function($r) { return $r['tipo_movimiento'] === 'entrada'; })) ?>
                    </div>
                    <div class="text-muted">Entradas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-danger">
                        <?= count(array_filter($registros, function($r) { return $r['tipo_movimiento'] === 'salida'; })) ?>
                    </div>
                    <div class="text-muted">Salidas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-warning">
                        <?= count(array_unique(array_column($registros, 'placa'))) ?>
                    </div>
                    <div class="text-muted">Vehículos Únicos</div>
                </div>
            </div>
        </div>

        <!-- Barra de Búsqueda -->
        <div class="row">
            <div class="col-12">
                <div class="search-box">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-input" class="form-control form-control-lg" 
                                       placeholder="Buscar por placa, cédula, nombre, código universitario...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="filter-tipo" class="form-select form-select-lg">
                                <option value="">Todos los movimientos</option>
                                <option value="entrada">Solo Entradas</option>
                                <option value="salida">Solo Salidas</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Historial de Movimientos</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($registros) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>Movimiento</th>
                                            <th>Nombre</th>
                                            <th>Cédula</th>
                                            <th>Código</th>
                                            <th>Vehículo</th>
                                            <th>Placa</th>
                                            <th>Parqueadero</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body">
                                        <?php foreach ($registros as $registro): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i:s', strtotime($registro['fecha_hora'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $registro['tipo_movimiento'] === 'entrada' ? 'badge-entrada' : 'badge-salida' ?>">
                                                        <?= $registro['tipo_movimiento'] === 'entrada' ? 'ENTRADA' : 'SALIDA' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($registro['nombre_completo']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($registro['tipo_usuario']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($registro['cedula']) ?></td>
                                                <td><?= htmlspecialchars($registro['codigo_universitario']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($registro['tipo_vehiculo']) ?>
                                                    <?php if ($registro['marca']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($registro['marca']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-dark"><?= htmlspecialchars($registro['placa']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($registro['parqueadero_nombre']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-car fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay registros</h4>
                                <p class="text-muted">No se encontraron movimientos en el sistema</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Búsqueda simple en JavaScript puro
        document.getElementById('search-input').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const filterTipo = document.getElementById('filter-tipo').value;
            const rows = document.querySelectorAll('#tabla-body tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const tipoMovimiento = row.querySelector('.badge').textContent.trim().toLowerCase();
                
                let showRow = true;
                
                // Aplicar búsqueda
                if (searchText && !rowText.includes(searchText)) {
                    showRow = false;
                }
                
                // Aplicar filtro por tipo
                if (filterTipo) {
                    const tipoBuscado = filterTipo === 'entrada' ? 'entrada' : 'salida';
                    if (tipoMovimiento !== tipoBuscado) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        });

        // Filtro por tipo
        document.getElementById('filter-tipo').addEventListener('change', function() {
            // Disparar el evento de búsqueda para aplicar ambos filtros
            document.getElementById('search-input').dispatchEvent(new Event('keyup'));
        });
    </script>
</body>
</html>