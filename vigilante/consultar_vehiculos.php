<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Consulta para obtener vehículos con sus últimas entradas y salidas
$query = "SELECT 
            u.id as usuario_id,
            u.codigo_universitario,
            u.cedula,
            u.nombre_completo,
            u.tipo as tipo_usuario,
            v.placa,
            v.tipo as tipo_vehiculo,
            v.marca,
            v.color,
            entrada.fecha_hora as fecha_entrada,
            entrada.parqueadero_nombre as parqueadero_entrada,
            salida.fecha_hora as fecha_salida,
            salida.parqueadero_nombre as parqueadero_salida,
            CASE 
                WHEN salida.fecha_hora IS NULL THEN 'DENTRO'
                ELSE 'FUERA'
            END as estado
          FROM usuarios_parqueadero u
          INNER JOIN vehiculos v ON u.id = v.usuario_id
          LEFT JOIN (
              SELECT ra1.usuario_id, ra1.fecha_hora, p.nombre as parqueadero_nombre
              FROM registros_acceso ra1
              INNER JOIN parqueaderos p ON ra1.parqueadero_id = p.id
              WHERE ra1.tipo_movimiento = 'entrada'
              AND ra1.fecha_hora = (
                  SELECT MAX(ra2.fecha_hora)
                  FROM registros_acceso ra2
                  WHERE ra2.usuario_id = ra1.usuario_id
                  AND ra2.tipo_movimiento = 'entrada'
              )
          ) entrada ON u.id = entrada.usuario_id
          LEFT JOIN (
              SELECT ra1.usuario_id, ra1.fecha_hora, p.nombre as parqueadero_nombre
              FROM registros_acceso ra1
              INNER JOIN parqueaderos p ON ra1.parqueadero_id = p.id
              WHERE ra1.tipo_movimiento = 'salida'
              AND ra1.fecha_hora = (
                  SELECT MAX(ra2.fecha_hora)
                  FROM registros_acceso ra2
                  WHERE ra2.usuario_id = ra1.usuario_id
                  AND ra2.tipo_movimiento = 'salida'
              )
          ) salida ON u.id = salida.usuario_id
          WHERE entrada.fecha_hora IS NOT NULL
          ORDER BY entrada.fecha_hora DESC";

$result = $conn->query($query);

// Verificar si hay error en la consulta
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$vehiculos = [];
while ($row = $result->fetch_assoc()) {
    $vehiculos[] = $row;
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
        .badge-dentro {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .badge-fuera {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
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
        .tiempo-activo {
            background: #fff3cd;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #856404;
        }
        .vehiculo-dentro {
            border-left: 4px solid #28a745;
        }
        .vehiculo-fuera {
            border-left: 4px solid #6c757d;
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
                    <h1><i class="fas fa-car me-2"></i>Estado de Vehículos</h1>
                    <p class="mb-0">Control completo de entradas, salidas y tiempos en parqueadero</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?= count($vehiculos) ?></div>
                    <div class="text-muted">Total Vehículos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-success">
                        <?= count(array_filter($vehiculos, function($v) { return $v['estado'] === 'DENTRO'; })) ?>
                    </div>
                    <div class="text-muted">En Parqueadero</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-danger">
                        <?= count(array_filter($vehiculos, function($v) { return $v['estado'] === 'FUERA'; })) ?>
                    </div>
                    <div class="text-muted">Fuera</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-warning">
                        <?= count(array_unique(array_column($vehiculos, 'placa'))) ?>
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
                            <select id="filter-estado" class="form-select form-select-lg">
                                <option value="">Todos los estados</option>
                                <option value="DENTRO">En Parqueadero</option>
                                <option value="FUERA">Fuera</option>
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
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Control de Vehículos</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($vehiculos) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th>Vehículo</th>
                                            <th>Propietario</th>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th>Tiempo Activo</th>
                                            <th>Parqueadero</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body">
                                        <?php foreach ($vehiculos as $vehiculo): 
                                            $tiempoActivo = '';
                                            if ($vehiculo['estado'] === 'DENTRO' && $vehiculo['fecha_entrada']) {
                                                $entrada = new DateTime($vehiculo['fecha_entrada']);
                                                $ahora = new DateTime();
                                                $diferencia = $entrada->diff($ahora);
                                                
                                                if ($diferencia->d > 0) {
                                                    $tiempoActivo = $diferencia->d . 'd ' . $diferencia->h . 'h';
                                                } else if ($diferencia->h > 0) {
                                                    $tiempoActivo = $diferencia->h . 'h ' . $diferencia->i . 'm';
                                                } else {
                                                    $tiempoActivo = $diferencia->i . 'm';
                                                }
                                            }
                                        ?>
                                            <tr class="<?= $vehiculo['estado'] === 'DENTRO' ? 'vehiculo-dentro' : 'vehiculo-fuera' ?>">
                                                <td>
                                                    <span class="badge <?= $vehiculo['estado'] === 'DENTRO' ? 'badge-dentro' : 'badge-fuera' ?>">
                                                        <?= $vehiculo['estado'] === 'DENTRO' ? '🟢 DENTRO' : '⚫ FUERA' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($vehiculo['placa']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($vehiculo['tipo_vehiculo']) ?>
                                                        <?php if ($vehiculo['marca']): ?>
                                                            • <?= htmlspecialchars($vehiculo['marca']) ?>
                                                        <?php endif; ?>
                                                        <?php if ($vehiculo['color']): ?>
                                                            • <?= htmlspecialchars($vehiculo['color']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($vehiculo['nombre_completo']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($vehiculo['cedula']) ?>
                                                        <br>
                                                        <?= htmlspecialchars($vehiculo['codigo_universitario']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($vehiculo['fecha_entrada']): ?>
                                                        <small class="text-success">
                                                            <i class="fas fa-sign-in-alt"></i>
                                                            <?= date('d/m H:i', strtotime($vehiculo['fecha_entrada'])) ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($vehiculo['parqueadero_entrada']) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($vehiculo['fecha_salida']): ?>
                                                        <small class="text-danger">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                            <?= date('d/m H:i', strtotime($vehiculo['fecha_salida'])) ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($vehiculo['parqueadero_salida']) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-warning">
                                                            <i class="fas fa-clock"></i>
                                                            En parqueadero
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($tiempoActivo): ?>
                                                        <span class="tiempo-activo">
                                                            ⏱️ <?= $tiempoActivo ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-primary">
                                                        <?= htmlspecialchars($vehiculo['parqueadero_entrada']) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-car fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay vehículos registrados</h4>
                                <p class="text-muted">No se encontraron vehículos en el sistema</p>
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
            const filterEstado = document.getElementById('filter-estado').value;
            const rows = document.querySelectorAll('#tabla-body tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const estado = row.querySelector('.badge').textContent.toLowerCase();
                
                let showRow = true;
                
                // Aplicar búsqueda
                if (searchText && !rowText.includes(searchText)) {
                    showRow = false;
                }
                
                // Aplicar filtro por estado
                if (filterEstado) {
                    const estadoBuscado = filterEstado.toLowerCase();
                    if (!estado.includes(estadoBuscado)) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        });

        // Filtro por estado
        document.getElementById('filter-estado').addEventListener('change', function() {
            document.getElementById('search-input').dispatchEvent(new Event('keyup'));
        });
    </script>
</body>
</html>