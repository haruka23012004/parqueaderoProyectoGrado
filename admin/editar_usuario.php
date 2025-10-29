<?php
require '../includes/auth.php';
require '../includes/conexion.php';

// Verificar que sea administrador
if (!estaAutenticado() || $_SESSION['rol_nombre'] != 'administrador_principal') {
    header('Location: /PARQUEADEROPROYECTOGRADO/paneles/administrador.php');
    exit();
}

// Obtener ID del usuario a editar
if (!isset($_GET['id'])) {
    header('Location: usuarios_aprobados.php?error=ID no proporcionado');
    exit();
}

$usuario_id = intval($_GET['id']);

// Obtener datos del usuario
$query = "SELECT 
            u.*, 
            v.id as vehiculo_id,
            v.tipo as tipo_vehiculo, 
            v.placa, 
            v.marca, 
            v.color, 
            v.detalle_tipo,
            v.foto_vehiculo
          FROM usuarios_parqueadero u 
          LEFT JOIN vehiculos v ON u.id = v.usuario_id 
          WHERE u.id = ? AND u.estado = 'aprobado'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header('Location: usuarios_aprobados.php?error=Usuario no encontrado');
    exit();
}

// Configuración para subida de archivos
$ruta_imagenes = __DIR__ . '/../assets/img/usuarios/';
$extensiones_permitidas = ['jpg', 'jpeg', 'png'];
$max_tamano_imagen = 2 * 1024 * 1024; // 2MB

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar datos del formulario
    $nombre_completo = trim($_POST['nombre_completo']);
    $cedula = trim($_POST['cedula']);
    $email = trim($_POST['email']);
    $tipo = $_POST['tipo'];
    $codigo_universitario = !empty($_POST['codigo_universitario']) ? trim($_POST['codigo_universitario']) : null;
    $facultad = !empty($_POST['facultad']) ? trim($_POST['facultad']) : null;
    $semestre = !empty($_POST['semestre']) ? trim($_POST['semestre']) : null;
    $programa_academico = !empty($_POST['programa_academico']) ? trim($_POST['programa_academico']) : null;
    
    // Datos del vehículo
    $tipo_vehiculo = $_POST['tipo_vehiculo'];
    $placa = !empty($_POST['placa']) ? trim($_POST['placa']) : null;
    $marca = !empty($_POST['marca']) ? trim($_POST['marca']) : null;
    $color = !empty($_POST['color']) ? trim($_POST['color']) : null;
    $detalle_tipo = !empty($_POST['detalle_tipo']) ? trim($_POST['detalle_tipo']) : null;
    
    // Variables para nuevas fotos
    $nueva_foto_usuario = $usuario['foto_usuario'];
    $nueva_foto_vehiculo = $usuario['foto_vehiculo'];
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Procesar nueva foto de usuario si se subió
        if (isset($_FILES['foto_usuario']) && $_FILES['foto_usuario']['error'] === UPLOAD_ERR_OK) {
            $foto_info = $_FILES['foto_usuario'];
            
            // Validar tipo de archivo
            $extension = strtolower(pathinfo($foto_info['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception("Solo se permiten imágenes JPG, JPEG o PNG para la foto de perfil");
            }

            // Validar tamaño
            if ($foto_info['size'] > $max_tamano_imagen) {
                throw new Exception("La imagen de perfil no debe exceder 2MB");
            }

            // Crear nombre único
            $nombre_foto = 'foto_usuario_' . md5($cedula . time()) . '.' . $extension;
            $ruta_foto = $ruta_imagenes . $nombre_foto;

            // Mover archivo
            if (!move_uploaded_file($foto_info['tmp_name'], $ruta_foto)) {
                throw new Exception("No se pudo guardar la nueva foto de perfil");
            }

            // Eliminar foto anterior si existe
            if (!empty($usuario['foto_usuario']) && file_exists(__DIR__ . '/../' . $usuario['foto_usuario'])) {
                unlink(__DIR__ . '/../' . $usuario['foto_usuario']);
            }

            $nueva_foto_usuario = 'assets/img/usuarios/' . $nombre_foto;
        }
        
        // Procesar nueva foto del vehículo si se subió
        if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] === UPLOAD_ERR_OK) {
            $foto_info = $_FILES['foto_vehiculo'];
            
            // Validar tipo de archivo
            $extension = strtolower(pathinfo($foto_info['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception("Solo se permiten imágenes JPG, JPEG o PNG para la foto del vehículo");
            }

            // Validar tamaño
            if ($foto_info['size'] > $max_tamano_imagen) {
                throw new Exception("La imagen del vehículo no debe exceder 2MB");
            }

            // Crear nombre único
            $nombre_foto = 'foto_vehiculo_' . md5($cedula . time()) . '.' . $extension;
            $ruta_foto = $ruta_imagenes . $nombre_foto;

            // Mover archivo
            if (!move_uploaded_file($foto_info['tmp_name'], $ruta_foto)) {
                throw new Exception("No se pudo guardar la nueva foto del vehículo");
            }

            // Eliminar foto anterior si existe
            if (!empty($usuario['foto_vehiculo']) && file_exists(__DIR__ . '/../' . $usuario['foto_vehiculo'])) {
                unlink(__DIR__ . '/../' . $usuario['foto_vehiculo']);
            }

            $nueva_foto_vehiculo = 'assets/img/usuarios/' . $nombre_foto;
        }
        
        // Actualizar información del usuario
        $update_usuario = "UPDATE usuarios_parqueadero 
                          SET nombre_completo = ?, cedula = ?, email = ?, tipo = ?, 
                              codigo_universitario = ?, facultad = ?, semestre = ?, programa_academico = ?,
                              foto_usuario = ?
                          WHERE id = ?";
        
        $stmt_usuario = $conn->prepare($update_usuario);
        $stmt_usuario->bind_param("sssssssssi", $nombre_completo, $cedula, $email, $tipo, 
                                 $codigo_universitario, $facultad, $semestre, $programa_academico,
                                 $nueva_foto_usuario, $usuario_id);
        
        if (!$stmt_usuario->execute()) {
            throw new Exception("Error al actualizar usuario: " . $stmt_usuario->error);
        }
        
        // Actualizar o insertar información del vehículo
        if ($usuario['vehiculo_id']) {
            // Actualizar vehículo existente
            $update_vehiculo = "UPDATE vehiculos 
                               SET tipo = ?, placa = ?, marca = ?, color = ?, detalle_tipo = ?, foto_vehiculo = ?
                               WHERE id = ?";
            
            $stmt_vehiculo = $conn->prepare($update_vehiculo);
            $stmt_vehiculo->bind_param("ssssssi", $tipo_vehiculo, $placa, $marca, $color, $detalle_tipo, $nueva_foto_vehiculo, $usuario['vehiculo_id']);
        } else {
            // Insertar nuevo vehículo
            $insert_vehiculo = "INSERT INTO vehiculos (usuario_id, tipo, placa, marca, color, detalle_tipo, foto_vehiculo)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_vehiculo = $conn->prepare($insert_vehiculo);
            $stmt_vehiculo->bind_param("issssss", $usuario_id, $tipo_vehiculo, $placa, $marca, $color, $detalle_tipo, $nueva_foto_vehiculo);
        }
        
        if (!$stmt_vehiculo->execute()) {
            throw new Exception("Error al actualizar vehículo: " . $stmt_vehiculo->error);
        }
        
        // Confirmar transacción
        $conn->commit();
        
        header('Location: usuarios_aprobados.php?msg=Usuario actualizado correctamente');
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        
        // Eliminar archivos nuevos si se subieron pero falló el registro
        if (isset($nueva_foto_usuario) && $nueva_foto_usuario !== $usuario['foto_usuario'] && file_exists(__DIR__ . '/../' . $nueva_foto_usuario)) {
            unlink(__DIR__ . '/../' . $nueva_foto_usuario);
        }
        if (isset($nueva_foto_vehiculo) && $nueva_foto_vehiculo !== $usuario['foto_vehiculo'] && file_exists(__DIR__ . '/../' . $nueva_foto_vehiculo)) {
            unlink(__DIR__ . '/../' . $nueva_foto_vehiculo);
        }
        
        $error = "Error al actualizar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
        }
        .vehicle-img {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-section h5 {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .file-input-container {
            margin-bottom: 15px;
        }
        .current-photo {
            border: 3px solid #0d6efd;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-edit me-2"></i>Editar Usuario</h2>
            <a href="usuarios_aprobados.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Usuarios Aprobados
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <!-- Foto actual del usuario -->
                            <?php if ($usuario['foto_usuario']): ?>
                                <img src="../<?= $usuario['foto_usuario'] ?>" alt="Foto usuario" class="user-img current-photo mb-3">
                                <p class="text-muted small">Foto actual</p>
                            <?php else: ?>
                                <div class="user-img bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                    <i class="fas fa-user text-muted" style="font-size: 3rem;"></i>
                                </div>
                                <p class="text-muted small">Sin foto</p>
                            <?php endif; ?>
                            
                            <h5><?= htmlspecialchars($usuario['nombre_completo']) ?></h5>
                            <p class="text-muted"><?= ucfirst($usuario['tipo']) ?></p>
                            <p class="text-muted">ID: <?= $usuario['id'] ?></p>
                            
                            <!-- Código QR -->
                            <?php if ($usuario['qr_code']): ?>
                                <div class="mt-3">
                                    <p class="mb-1"><strong>Código QR:</strong></p>
                                    <img src="../<?= $usuario['qr_code'] ?>" alt="Código QR" class="img-thumbnail" style="max-width: 120px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- Información Personal -->
                    <div class="form-section">
                        <h5><i class="fas fa-id-card me-2"></i>Información Personal</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                       value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cédula *</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" 
                                       value="<?= htmlspecialchars($usuario['cedula']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($usuario['email']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">Tipo de Usuario *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="estudiante" <?= $usuario['tipo'] == 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                                    <option value="profesor" <?= $usuario['tipo'] == 'profesor' ? 'selected' : '' ?>>Profesor</option>
                                    <option value="administrativo" <?= $usuario['tipo'] == 'administrativo' ? 'selected' : '' ?>>Administrativo</option>
                                    <option value="visitante" <?= $usuario['tipo'] == 'visitante' ? 'selected' : '' ?>>Visitante</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="codigo_universitario" class="form-label">Código Universitario</label>
                                <input type="text" class="form-control" id="codigo_universitario" name="codigo_universitario" 
                                       value="<?= htmlspecialchars($usuario['codigo_universitario'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="facultad" class="form-label">Facultad</label>
                                <input type="text" class="form-control" id="facultad" name="facultad" 
                                       value="<?= htmlspecialchars($usuario['facultad'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="semestre" class="form-label">Semestre</label>
                                <input type="text" class="form-control" id="semestre" name="semestre" 
                                       value="<?= htmlspecialchars($usuario['semestre'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="programa_academico" class="form-label">Programa Académico</label>
                                <input type="text" class="form-control" id="programa_academico" name="programa_academico" 
                                       value="<?= htmlspecialchars($usuario['programa_academico'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Foto de Perfil -->
                    <div class="form-section">
                        <h5><i class="fas fa-camera me-2"></i>Foto de Perfil</h5>
                        
                        <div class="file-input-container">
                            <label for="foto_usuario" class="form-label">Cambiar Foto de Perfil</label>
                            <input type="file" class="form-control" id="foto_usuario" name="foto_usuario" 
                                   accept="image/jpeg, image/jpg, image/png">
                            <div class="form-text">
                                Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB.
                                <?php if ($usuario['foto_usuario']): ?>
                                    <br><strong>Nota:</strong> La foto actual será reemplazada.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Vehículo -->
                    <div class="form-section">
                        <h5><i class="fas fa-car me-2"></i>Información del Vehículo</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_vehiculo" class="form-label">Tipo de Vehículo *</label>
                                <select class="form-select" id="tipo_vehiculo" name="tipo_vehiculo" required>
                                    <option value="bicicleta" <?= ($usuario['tipo_vehiculo'] ?? '') == 'bicicleta' ? 'selected' : '' ?>>Bicicleta</option>
                                    <option value="motocicleta" <?= ($usuario['tipo_vehiculo'] ?? '') == 'motocicleta' ? 'selected' : '' ?>>Motocicleta</option>
                                    <option value="motocarro" <?= ($usuario['tipo_vehiculo'] ?? '') == 'motocarro' ? 'selected' : '' ?>>Motocarro</option>
                                    <option value="carro" <?= ($usuario['tipo_vehiculo'] ?? '') == 'carro' ? 'selected' : '' ?>>Carro</option>
                                    <option value="otro" <?= ($usuario['tipo_vehiculo'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="placa" class="form-label">Placa</label>
                                <input type="text" class="form-control" id="placa" name="placa" 
                                       value="<?= htmlspecialchars($usuario['placa'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text" class="form-control" id="marca" name="marca" 
                                       value="<?= htmlspecialchars($usuario['marca'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color" 
                                       value="<?= htmlspecialchars($usuario['color'] ?? '') ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="detalle_tipo" class="form-label">Detalle del Tipo (si seleccionó "Otro")</label>
                                <input type="text" class="form-control" id="detalle_tipo" name="detalle_tipo" 
                                       value="<?= htmlspecialchars($usuario['detalle_tipo'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Foto del Vehículo -->
                    <div class="form-section">
                        <h5><i class="fas fa-camera-retro me-2"></i>Foto del Vehículo</h5>
                        
                        <!-- Mostrar foto actual del vehículo si existe -->
                        <?php if (!empty($usuario['foto_vehiculo'])): ?>
                        <div class="text-center mb-3">
                            <p class="mb-2"><strong>Foto actual del vehículo:</strong></p>
                            <img src="../<?= $usuario['foto_vehiculo'] ?>" alt="Foto del vehículo" class="vehicle-img current-photo">
                        </div>
                        <?php endif; ?>
                        
                        <div class="file-input-container">
                            <label for="foto_vehiculo" class="form-label"><?= empty($usuario['foto_vehiculo']) ? 'Subir' : 'Cambiar' ?> Foto del Vehículo</label>
                            <input type="file" class="form-control" id="foto_vehiculo" name="foto_vehiculo" 
                                   accept="image/jpeg, image/jpg, image/png">
                            <div class="form-text">
                                Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB.
                                <?php if (!empty($usuario['foto_vehiculo'])): ?>
                                    <br><strong>Nota:</strong> La foto actual será reemplazada.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Aprobación (solo lectura) -->
                    <div class="form-section">
                        <h5><i class="fas fa-calendar-check me-2"></i>Información de Aprobación</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Registro</label>
                                <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>" readonly>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Aprobación</label>
                                <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($usuario['fecha_aprobacion'])) ?>" readonly>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Estado</label>
                                <div>
                                    <span class="badge bg-success">Aprobado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="usuarios_aprobados.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar campo de detalle según el tipo de vehículo
        document.getElementById('tipo_vehiculo').addEventListener('change', function() {
            const detalleTipoDiv = document.getElementById('detalle_tipo').closest('.mb-3');
            if (this.value === 'otro') {
                detalleTipoDiv.style.display = 'block';
            } else {
                detalleTipoDiv.style.display = 'block'; // Siempre visible pero podría ajustarse
            }
        });
        
        // Mostrar campos específicos según el tipo de usuario
        document.getElementById('tipo').addEventListener('change', function() {
            const codigoField = document.getElementById('codigo_universitario').closest('.mb-3');
            const facultadField = document.getElementById('facultad').closest('.mb-3');
            const semestreField = document.getElementById('semestre').closest('.mb-3');
            const programaField = document.getElementById('programa_academico').closest('.mb-3');
            
            if (this.value === 'estudiante') {
                codigoField.style.display = 'block';
                facultadField.style.display = 'block';
                semestreField.style.display = 'block';
                programaField.style.display = 'block';
            } else if (this.value === 'profesor' || this.value === 'administrativo') {
                codigoField.style.display = 'block';
                facultadField.style.display = 'block';
                semestreField.style.display = 'none';
                programaField.style.display = 'block';
            } else {
                codigoField.style.display = 'none';
                facultadField.style.display = 'none';
                semestreField.style.display = 'none';
                programaField.style.display = 'none';
            }
        });
        
        // Trigger change event on page load to set initial state
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('tipo').dispatchEvent(new Event('change'));
            document.getElementById('tipo_vehiculo').dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>