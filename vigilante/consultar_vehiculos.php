<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'vigilante') {
    header('Location: /parqueaderoProyectoGrado/acceso/login.php');
    exit();
}

// Obtener todos los registros de acceso con información completa
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
            p.id as parqueadero_id,
            e_entrada.nombre as empleado_entrada,
            e_salida.nombre as empleado_salida
          FROM registros_acceso ra
          INNER JOIN usuarios_parqueadero u ON ra.usuario_id = u.id
          INNER JOIN vehiculos v ON ra.vehiculo_id = v.id
          INNER JOIN parqueaderos p ON ra.parqueadero_id = p.id
          LEFT JOIN empleados e_entrada ON ra.empleado_id_entrada = e_entrada.id
          LEFT JOIN empleados e_salida ON ra.empleado_id_salida = e_salida.id
          ORDER BY ra.fecha_hora DESC";

$result = $conn->query($query);
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        }
        .badge-salida {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 5px;
        }
        .card-registro {
            border-left: 4px solid;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .card-registro.entrada {
            border-left-color: #28a745;
        }
        .card-registro.salida {
            border-left-color: #dc3545;
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
        .filter-tags {
            margin-bottom: 15px;
        }
        .filter-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .filter-tag .close {
            margin-left: 5px;
            cursor: pointer;
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

        <!-- Barra de Búsqueda y Filtros -->
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
                    
                    <!-- Filtros activos -->
                    <div id="filter-tags" class="filter-tags"></div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <input type="date" id="filter-fecha" class="form-control" placeholder="Filtrar por fecha">
                        </div>
                        <div class="col-md-4">
                            <select id="filter-parqueadero" class="form-select">
                                <option value="">Todos los parqueaderos</option>
                                <option value="1">Parqueadero Principal</option>
                                <option value="2">Parqueadero Secundario</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filter-vehiculo" class="form-select">
                                <option value="">Todos los tipos de vehículo</option>
                                <option value="carro">Carro</option>
                                <option value="moto">Moto</option>
                                <option value="bicicleta">Bicicleta</option>
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
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Historial de Movimientos</h4>
                        <div>
                            <button id="btn-export" class="btn btn-sm btn-light">
                                <i class="fas fa-download me-1"></i>Exportar
                            </button>
                            <button id="btn-refresh" class="btn btn-sm btn-light">
                                <i class="fas fa-sync me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-vehiculos" class="table table-hover table-striped">
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
                                        <th>Empleado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros as $registro): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($registro['fecha_hora'])) ?><br>
                                                    <?= date('H:i:s', strtotime($registro['fecha_hora'])) ?>
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
                                            <td>
                                                <small>
                                                    <?php if ($registro['tipo_movimiento'] === 'entrada' && $registro['empleado_entrada']): ?>
                                                        <i class="fas fa-sign-in-alt text-success"></i> <?= htmlspecialchars($registro['empleado_entrada']) ?>
                                                    <?php elseif ($registro['tipo_movimiento'] === 'salida' && $registro['empleado_salida']): ?>
                                                        <i class="fas fa-sign-out-alt text-danger"></i> <?= htmlspecialchars($registro['empleado_salida']) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sistema</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="verDetalles(<?= $registro['registro_id'] ?>)"
                                                        title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles -->
    <div class="modal fade" id="detallesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalles del Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalles-content">
                    <!-- Los detalles se cargarán aquí -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let dataTable;

        $(document).ready(function() {
            // Inicializar DataTable
            dataTable = $('#tabla-vehiculos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [[0, 'desc']],
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                responsive: true
            });

            // Búsqueda en tiempo real
            $('#search-input').on('keyup', function() {
                dataTable.search(this.value).draw();
                updateFilterTags();
            });

            // Filtros adicionales
            $('#filter-tipo, #filter-fecha, #filter-parqueadero, #filter-vehiculo').on('change', function() {
                applyFilters();
                updateFilterTags();
            });

            // Botones de acción
            $('#btn-refresh').on('click', function() {
                location.reload();
            });

            $('#btn-export').on('click', function() {
                exportToExcel();
            });
        });

        function applyFilters() {
            let tipo = $('#filter-tipo').val();
            let fecha = $('#filter-fecha').val();
            let parqueadero = $('#filter-parqueadero').val();
            let vehiculo = $('#filter-vehiculo').val();

            // Aplicar filtros combinados
            dataTable.column(1).search(tipo);
            dataTable.column(0).search(fecha);
            dataTable.column(7).search(parqueadero);
            dataTable.column(5).search(vehiculo);
            
            dataTable.draw();
        }

        function updateFilterTags() {
            let tags = $('#filter-tags');
            tags.empty();

            let filters = [
                { id: 'filter-tipo', text: 'Movimiento' },
                { id: 'filter-fecha', text: 'Fecha' },
                { id: 'filter-parqueadero', text: 'Parqueadero' },
                { id: 'filter-vehiculo', text: 'Vehículo' }
            ];

            filters.forEach(filter => {
                let value = $('#' + filter.id).val();
                if (value) {
                    let displayValue = value;
                    if (filter.id === 'filter-tipo') {
                        displayValue = value === 'entrada' ? 'Entradas' : 'Salidas';
                    } else if (filter.id === 'filter-parqueadero') {
                        displayValue = value === '1' ? 'Principal' : 'Secundario';
                    }
                    
                    let tag = `<span class="filter-tag">
                        ${filter.text}: ${displayValue}
                        <span class="close" onclick="clearFilter('${filter.id}')">×</span>
                    </span>`;
                    tags.append(tag);
                }
            });

            if ($('#search-input').val()) {
                let tag = `<span class="filter-tag">
                    Búsqueda: "${$('#search-input').val()}"
                    <span class="close" onclick="clearSearch()">×</span>
                </span>`;
                tags.append(tag);
            }
        }

        function clearFilter(filterId) {
            $('#' + filterId).val('');
            applyFilters();
            updateFilterTags();
        }

        function clearSearch() {
            $('#search-input').val('');
            dataTable.search('').draw();
            updateFilterTags();
        }

        function verDetalles(registroId) {
            // Aquí puedes cargar detalles más específicos via AJAX si es necesario
            // Por ahora mostramos un mensaje básico
            $('#detalles-content').html(`
                <div class="text-center">
                    <i class="fas fa-info-circle fa-3x text-primary mb-3"></i>
                    <h5>Detalles del Registro #${registroId}</h5>
                    <p class="text-muted">Funcionalidad de detalles en desarrollo</p>
                </div>
            `);
            $('#detallesModal').modal('show');
        }

        function exportToExcel() {
            // Implementar exportación a Excel
            alert('Funcionalidad de exportación en desarrollo');
        }
    </script>
</body>
</html>