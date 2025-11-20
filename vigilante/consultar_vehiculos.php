<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante O administrador
if (!estaAutenticado() || ($_SESSION['rol_nombre'] != 'vigilante' && $_SESSION['rol_nombre'] != 'administrador_principal')) {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Consulta CORREGIDA para obtener vehículos con estado actual correcto
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
            -- Última entrada (siempre la mostramos)
            ultima_entrada.fecha_hora as fecha_entrada,
            ultima_entrada.parqueadero_nombre as parqueadero_entrada,
            -- Última salida SOLO si es posterior a la última entrada
            CASE 
                WHEN ultima_salida.fecha_hora > ultima_entrada.fecha_hora THEN ultima_salida.fecha_hora
                ELSE NULL
            END as fecha_salida,
            CASE 
                WHEN ultima_salida.fecha_hora > ultima_entrada.fecha_hora THEN ultima_salida.parqueadero_nombre
                ELSE NULL
            END as parqueadero_salida,
            -- Estado CORREGIDO: DENTRO si no hay salida o si la salida es anterior a la entrada
            CASE 
                WHEN ultima_salida.fecha_hora IS NULL OR ultima_salida.fecha_hora < ultima_entrada.fecha_hora THEN 'DENTRO'
                ELSE 'FUERA'
            END as estado
          FROM usuarios_parqueadero u
          INNER JOIN vehiculos v ON u.id = v.usuario_id
          -- Última entrada de cada usuario
          LEFT JOIN (
              SELECT 
                  ra1.usuario_id, 
                  ra1.fecha_hora, 
                  p.nombre as parqueadero_nombre,
                  ra1.parqueadero_id
              FROM registros_acceso ra1
              INNER JOIN parqueaderos p ON ra1.parqueadero_id = p.id
              WHERE ra1.tipo_movimiento = 'entrada'
              AND ra1.fecha_hora = (
                  SELECT MAX(ra2.fecha_hora)
                  FROM registros_acceso ra2
                  WHERE ra2.usuario_id = ra1.usuario_id
                  AND ra2.tipo_movimiento = 'entrada'
              )
          ) ultima_entrada ON u.id = ultima_entrada.usuario_id
          -- Última salida de cada usuario
          LEFT JOIN (
              SELECT 
                  ra1.usuario_id, 
                  ra1.fecha_hora, 
                  p.nombre as parqueadero_nombre
              FROM registros_acceso ra1
              INNER JOIN parqueaderos p ON ra1.parqueadero_id = p.id
              WHERE ra1.tipo_movimiento = 'salida'
              AND ra1.fecha_hora = (
                  SELECT MAX(ra2.fecha_hora)
                  FROM registros_acceso ra2
                  WHERE ra2.usuario_id = ra1.usuario_id
                  AND ra2.tipo_movimiento = 'salida'
              )
          ) ultima_salida ON u.id = ultima_salida.usuario_id
          WHERE ultima_entrada.fecha_hora IS NOT NULL
          ORDER BY ultima_entrada.fecha_hora DESC";

$result = $conn->query($query);

// Verificar si hay error en la consulta
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$vehiculos = [];
while ($row = $result->fetch_assoc()) {
    $vehiculos[] = $row;
}

// Estadísticas
$total_vehiculos = count($vehiculos);
$vehiculos_dentro = count(array_filter($vehiculos, function($v) { 
    return $v['estado'] === 'DENTRO'; 
}));
$vehiculos_fuera = count(array_filter($vehiculos, function($v) { 
    return $v['estado'] === 'FUERA'; 
}));
$placas_unicas = count(array_unique(array_column($vehiculos, 'placa')));
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
        .no-registros {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
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
                    <div class="stats-number text-primary"><?= $total_vehiculos ?></div>
                    <div class="text-muted">Total Registros</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?= $vehiculos_dentro ?></div>
                    <div class="text-muted">En Parqueadero</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-danger"><?= $vehiculos_fuera ?></div>
                    <div class="text-muted">Fuera</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?= $placas_unicas ?></div>
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
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i>Control de Vehículos
                            <small class="float-end">Actualizado: <?= date('d/m/Y H:i:s') ?></small>
                        </h4>
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
                                            <th>Última Entrada</th>
                                            <th>Última Salida</th>
                                            <th>Tiempo Activo</th>
                                            <th>Parqueadero</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body">
                                        <?php foreach ($vehiculos as $vehiculo): 
                                            // Calcular tiempo activo solo si está DENTRO
                                            $tiempoActivo = '';
                                            if ($vehiculo['estado'] === 'DENTRO' && $vehiculo['fecha_entrada']) {
                                                $entrada = new DateTime($vehiculo['fecha_entrada']);
                                                $ahora = new DateTime();
                                                $diferencia = $entrada->diff($ahora);
                                                
                                                if ($diferencia->d > 0) {
                                                    $tiempoActivo = $diferencia->format('%d días %h hrs');
                                                } else if ($diferencia->h > 0) {
                                                    $tiempoActivo = $diferencia->format('%h hrs %i min');
                                                } else {
                                                    $tiempoActivo = $diferencia->format('%i minutos');
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
                                                        <?php if ($vehiculo['codigo_universitario']): ?>
                                                            <br><?= htmlspecialchars($vehiculo['codigo_universitario']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($vehiculo['fecha_entrada']): ?>
                                                        <small class="text-success">
                                                            <i class="fas fa-sign-in-alt"></i>
                                                            <?= date('d/m/Y H:i', strtotime($vehiculo['fecha_entrada'])) ?>
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
                                                            <?= date('d/m/Y H:i', strtotime($vehiculo['fecha_salida'])) ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($vehiculo['parqueadero_salida']) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
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
                            <div class="no-registros">
                                <i class="fas fa-car fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay vehículos registrados</h4>
                                <p class="text-muted">No se encontraron vehículos con movimientos en el sistema</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Búsqueda y filtrado mejorado
        function filtrarTabla() {
            const searchText = document.getElementById('search-input').value.toLowerCase();
            const filterEstado = document.getElementById('filter-estado').value;
            const rows = document.querySelectorAll('#tabla-body tr');
            
            let visibleCount = 0;
            
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
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            const noResults = document.getElementById('no-results');
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResults) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results';
                    noResultsRow.innerHTML = `
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-search fa-2x text-muted mb-2"></i>
                            <h5 class="text-muted">No se encontraron resultados</h5>
                            <p class="text-muted">Intente con otros términos de búsqueda</p>
                        </td>
                    `;
                    document.querySelector('#tabla-body').appendChild(noResultsRow);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }

        // Event listeners
        document.getElementById('search-input').addEventListener('keyup', filtrarTabla);
        document.getElementById('filter-estado').addEventListener('change', filtrarTabla);

        // Auto-refresh cada 30 segundos
        setInterval(() => {
            const timestamp = document.querySelector('.card-header small');
            if (timestamp) {
                const now = new Date();
                timestamp.textContent = 'Actualizado: ' + 
                    now.toLocaleDateString('es-ES') + ' ' + 
                    now.toLocaleTimeString('es-ES');
            }
        }, 30000);
    </script>
</body>
</html>