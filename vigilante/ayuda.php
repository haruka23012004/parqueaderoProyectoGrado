<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';

// Verificar que sea vigilante O administrador
if (!estaAutenticado() || ($_SESSION['rol_nombre'] != 'vigilante' && $_SESSION['rol_nombre'] != 'administrador_principal')) {
    header('Location: ../acceso/login.php');
    exit();
}

// Procesar el formulario de ayuda
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_problema = $_POST['tipo_problema'] ?? '';
    $subcategoria = $_POST['subcategoria'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $urgencia = $_POST['urgencia'] ?? 'media';
    $usuario_id = $_SESSION['usuario_id'];
    
    try {
        // Validar campos obligatorios
        if (empty($tipo_problema) || empty($descripcion)) {
            throw new Exception('Por favor complete todos los campos obligatorios');
        }
        
        // Insertar en la base de datos
        $query = "INSERT INTO pedidos_ayuda 
                  (usuario_id, tipo_problema, subcategoria, descripcion, urgencia, estado, fecha_creacion) 
                  VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $usuario_id, $tipo_problema, $subcategoria, $descripcion, $urgencia);
        
        if ($stmt->execute()) {
            $mensaje = '✅ Tu solicitud de ayuda ha sido enviada correctamente. Te contactaremos pronto.';
            $tipo_mensaje = 'success';
            
            // Limpiar el formulario
            $_POST = [];
        } else {
            throw new Exception('Error al enviar la solicitud: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener solicitudes anteriores del usuario
$query_solicitudes = "SELECT * FROM pedidos_ayuda 
                      WHERE usuario_id = ? 
                      ORDER BY fecha_creacion DESC 
                      LIMIT 5";
$stmt_solicitudes = $conn->prepare($query_solicitudes);
$stmt_solicitudes->bind_param("i", $_SESSION['usuario_id']);
$stmt_solicitudes->execute();
$solicitudes_anteriores = $stmt_solicitudes->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ayuda - Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .help-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .problem-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .problem-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .problem-option.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        .urgency-low { border-left: 4px solid #28a745; }
        .urgency-medium { border-left: 4px solid #ffc107; }
        .urgency-high { border-left: 4px solid #dc3545; }
        .subcategory-option {
            padding: 8px 15px;
            margin: 5px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .subcategory-option:hover {
            background-color: #f8f9fa;
        }
        .subcategory-option.selected {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="help-container">
            <!-- Header -->
            <div class="header-section">
                <h1><i class="fas fa-life-ring me-2"></i>Centro de Ayuda</h1>
                <p class="mb-0">Reporta problemas y solicita asistencia técnica</p>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?> m-3">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="p-4">
                <form method="POST" id="help-form">
                    <!-- Paso 1: Tipo de Problema -->
                    <div class="mb-4">
                        <h4 class="mb-3">📋 ¿Qué tipo de problema tienes?</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="problem-option" data-problem="tecnico">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-laptop-code fa-2x text-primary me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Problema Técnico</h5>
                                            <p class="mb-0 text-muted">Fallos en el sistema, errores de software</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="problem-option" data-problem="hardware">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tools fa-2x text-warning me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Problema de Hardware</h5>
                                            <p class="mb-0 text-muted">Equipos, lectores QR, impresoras</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="problem-option" data-problem="procedimiento">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clipboard-list fa-2x text-success me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Procedimiento</h5>
                                            <p class="mb-0 text-muted">Dudas sobre procesos y procedimientos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="problem-option" data-problem="sugerencia">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-lightbulb fa-2x text-info me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Sugerencia</h5>
                                            <p class="mb-0 text-muted">Ideas para mejorar el sistema</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="tipo_problema" id="tipo_problema" required>
                    </div>

                    <!-- Paso 2: Subcategorías (se muestra dinámicamente) -->
                    <div class="mb-4" id="subcategoria-section" style="display: none;">
                        <h4 class="mb-3">🔍 Especifica el problema</h4>
                        <div id="subcategoria-options"></div>
                        <input type="hidden" name="subcategoria" id="subcategoria">
                    </div>

                    <!-- Paso 3: Descripción -->
                    <div class="mb-4">
                        <h4 class="mb-3">📝 Describe el problema en detalle</h4>
                        <textarea class="form-control" name="descripcion" id="descripcion" 
                                  rows="5" placeholder="Describe con el mayor detalle posible el problema, error o sugerencia..." 
                                  required></textarea>
                        <div class="form-text">
                            Incluye pasos para reproducir el error, mensajes específicos, o detalles de tu sugerencia.
                        </div>
                    </div>

                    <!-- Paso 4: Urgencia -->
                    <div class="mb-4">
                        <h4 class="mb-3">⚡ Nivel de Urgencia</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="problem-option urgency-low" data-urgencia="baja">
                                    <div class="text-center">
                                        <i class="fas fa-walking fa-2x text-success mb-2"></i>
                                        <h5>Baja</h5>
                                        <small>No afecta operaciones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="problem-option urgency-medium selected" data-urgencia="media">
                                    <div class="text-center">
                                        <i class="fas fa-running fa-2x text-warning mb-2"></i>
                                        <h5>Media</h5>
                                        <small>Afecta algunas funciones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="problem-option urgency-high" data-urgencia="alta">
                                    <div class="text-center">
                                        <i class="fas fa-bolt fa-2x text-danger mb-2"></i>
                                        <h5>Alta</h5>
                                        <small>Bloquea operaciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="urgencia" id="urgencia" value="media">
                    </div>

                    <!-- Botón de envío -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud de Ayuda
                        </button>
                    </div>
                </form>

                <!-- Solicitudes Anteriores -->
                <?php if (!empty($solicitudes_anteriores)): ?>
                <div class="mt-5">
                    <h4 class="mb-3">📨 Tus Solicitudes Recientes</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Urgencia</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes_anteriores as $solicitud): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_creacion'])) ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= ucfirst($solicitud['tipo_problema']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($solicitud['descripcion'], 0, 50)) ?>...</small>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = [
                                            'baja' => 'bg-success',
                                            'media' => 'bg-warning',
                                            'alta' => 'bg-danger'
                                        ][$solicitud['urgencia']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= ucfirst($solicitud['urgencia']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $estado_class = [
                                            'pendiente' => 'bg-warning',
                                            'en_proceso' => 'bg-info',
                                            'resuelto' => 'bg-success',
                                            'cerrado' => 'bg-secondary'
                                        ][$solicitud['estado']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $estado_class ?>">
                                            <?= ucfirst(str_replace('_', ' ', $solicitud['estado'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración de subcategorías por tipo de problema
        const subcategorias = {
            tecnico: [
                'Error al escanear QR',
                'Sistema muy lento',
                'Error en registro manual',
                'Problema de conexión',
                'Error en reportes',
                'Otro problema técnico'
            ],
            hardware: [
                'Lector QR no funciona',
                'Problema con la computadora',
                'Impresora no funciona',
                'Problema de red/internet',
                'Tablet/dispositivo móvil',
                'Otro problema de hardware'
            ],
            procedimiento: [
                'Duda sobre registro manual',
                'Procedimiento de entrada/salida',
                'Manejo de emergencias',
                'Reporte de incidentes',
                'Uso del sistema',
                'Otra duda de procedimiento'
            ],
            sugerencia: [
                'Mejora de interfaz',
                'Nueva funcionalidad',
                'Proceso más eficiente',
                'Reportes mejorados',
                'Característica móvil',
                'Otra sugerencia'
            ]
        };

        // Selección de tipo de problema
        document.querySelectorAll('.problem-option[data-problem]').forEach(option => {
            option.addEventListener('click', function() {
                // Remover selección anterior
                document.querySelectorAll('.problem-option[data-problem]').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Seleccionar actual
                this.classList.add('selected');
                const problema = this.dataset.problem;
                document.getElementById('tipo_problema').value = problema;
                
                // Mostrar subcategorías
                mostrarSubcategorias(problema);
            });
        });

        // Selección de urgencia
        document.querySelectorAll('.problem-option[data-urgencia]').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.problem-option[data-urgencia]').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('urgencia').value = this.dataset.urgencia;
            });
        });

        // Mostrar subcategorías
        function mostrarSubcategorias(problema) {
            const section = document.getElementById('subcategoria-section');
            const optionsContainer = document.getElementById('subcategoria-options');
            
            if (subcategorias[problema]) {
                optionsContainer.innerHTML = '';
                subcategorias[problema].forEach(subcat => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'subcategory-option';
                    button.textContent = subcat;
                    button.addEventListener('click', function() {
                        document.querySelectorAll('.subcategory-option').forEach(opt => {
                            opt.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        document.getElementById('subcategoria').value = subcat;
                    });
                    optionsContainer.appendChild(button);
                });
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        // Validación del formulario
        document.getElementById('help-form').addEventListener('submit', function(e) {
            const tipoProblema = document.getElementById('tipo_problema').value;
            const descripcion = document.getElementById('descripcion').value.trim();
            
            if (!tipoProblema) {
                e.preventDefault();
                alert('Por favor selecciona el tipo de problema');
                return;
            }
            
            if (!descripcion) {
                e.preventDefault();
                alert('Por favor describe el problema o sugerencia');
                return;
            }
            
            // Confirmación final
            if (!confirm('¿Estás seguro de enviar esta solicitud de ayuda?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>