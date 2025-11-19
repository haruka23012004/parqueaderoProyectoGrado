<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">Sistema de Parqueadero</a>
        <div class="navbar-nav">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/paneles/administrador.php">Panel</a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/acceso/logout.php">Cerrar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>