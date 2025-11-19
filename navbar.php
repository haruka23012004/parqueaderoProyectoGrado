<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Sistema de Parqueadero</a>
        <div class="navbar-nav">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <?php if ($_SESSION['rol_nombre'] == 'administrador_principal'): ?>
                    <!-- OPCIONES PARA ADMINISTRADOR -->
                    <a class="nav-link" href="/parqueaderoProyectoGrado/paneles/administrador.php">Panel Admin</a>
                    <a class="nav-link" href="/parqueaderoProyectoGrado/admin/solicitudes_pendientes.php">Solicitudes</a>
                    
                <?php elseif ($_SESSION['rol_nombre'] == 'vigilante'): ?>
                    <!-- OPCIONES PARA VIGILANTE -->
                    <a class="nav-link" href="/parqueaderoProyectoGrado/vigilante/lector_qr.php">
                        <i class="fas fa-qrcode"></i> Lector QR
                    </a>
                    <a class="nav-link" href="/parqueaderoProyectoGrado/paneles/vigilante.php">Panel Vigilante</a>
                    
                <?php elseif ($_SESSION['rol_nombre'] == 'empleado_secundario'): ?>
                    <!-- OPCIONES PARA EMPLEADO -->
                    <a class="nav-link" href="/parqueaderoProyectoGrado/paneles/empleado.php">Panel Empleado</a>
                    
                <?php endif; ?>
                
                <!-- OPCIÓN COMÚN PARA TODOS -->
                <a class="nav-link" href="/parqueaderoProyectoGrado/acceso/logout.php">Cerrar Sesión</a>
                
            <?php endif; ?>
        </div>
    </div>
</nav>