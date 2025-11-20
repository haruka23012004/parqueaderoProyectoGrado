<?php
require 'includes/conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $cedula = trim($_POST['cedula']);
    $contacto = trim($_POST['contacto']);
    $tipo_contacto = $_POST['tipo_contacto'];
    $descripcion = trim($_POST['descripcion']);
    
    // Validaciones básicas
    if (empty($nombre_completo) || empty($cedula) || empty($contacto) || empty($descripcion)) {
        $mensaje = '<div class="alert alert-danger">Todos los campos obligatorios deben ser completados.</div>';
    } else {
        // Insertar en la base de datos
        $query = "INSERT INTO reportes_carnet_perdido 
                 (nombre_completo, cedula, contacto, tipo_contacto, descripcion, fecha_reporte, estado) 
                 VALUES (?, ?, ?, ?, ?, NOW(), 'pendiente')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $nombre_completo, $cedula, $contacto, $tipo_contacto, $descripcion);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Tu reporte ha sido enviado exitosamente. Nos contactaremos contigo pronto.</div>';
            // Limpiar campos
            $_POST = array();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al enviar el reporte. Por favor intenta nuevamente.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Carnet Perdido - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-naranja: #FF6B35;
            --color-rojo: #EF3E36;
            --color-naranja-claro: #FF8C42;
            --color-blanco: #FFFFFF;
            --color-negro: #212529;
            --color-gris: #6C757D;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Open Sans', sans-serif;
        }
        
        .recovery-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .recovery-header {
            background: linear-gradient(135deg, var(--color-rojo), #d32f2f);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .recovery-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--color-negro);
        }
        
        .btn-recovery {
            background: linear-gradient(135deg, var(--color-rojo), #d32f2f);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .btn-recovery:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 62, 54, 0.4);
        }
        
        .contact-options .form-check {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--color-negro) !important;">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logoUniguajira.png" alt="Logo Universidad" style="height: 50px;">
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Volver al Inicio</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="recovery-container">
            <div class="recovery-header">
                <i class="fas fa-id-card fa-3x mb-3"></i>
                <h2>Reportar Carnet Perdido</h2>
                <p class="mb-0">Complete el formulario para reportar la pérdida de su carnet</p>
            </div>
            
            <div class="recovery-body">
                <?php echo $mensaje; ?>
                
                <form method="POST" action="">
                    <!-- Información Personal -->
                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                               value="<?php echo isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cedula" class="form-label">Cédula *</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" 
                               value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>" 
                               required>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <div class="mb-3">
                        <label class="form-label">Preferencia de Contacto *</label>
                        <div class="contact-options">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_contacto" id="telefono" value="telefono" checked>
                                <label class="form-check-label" for="telefono">
                                    <i class="fas fa-phone me-1"></i> Teléfono
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_contacto" id="correo" value="correo">
                                <label class="form-check-label" for="correo">
                                    <i class="fas fa-envelope me-1"></i> Correo Electrónico
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contacto" class="form-label">Datos de Contacto *</label>
                        <input type="text" class="form-control" id="contacto" name="contacto" 
                               value="<?php echo isset($_POST['contacto']) ? htmlspecialchars($_POST['contacto']) : ''; ?>" 
                               placeholder="Número de teléfono o correo electrónico" required>
                    </div>
                    
                    <!-- Descripción del Problema -->
                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción del Incidente *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" 
                                  placeholder="Describa cómo perdió su carnet, fecha aproximada, lugar, y cualquier información relevante..." 
                                  required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-recovery">
                            <i class="fas fa-paper-plane me-2"></i> Enviar Reporte
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>