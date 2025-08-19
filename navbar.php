<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Sistema de Parqueadero</a>
        <div class="navbar-nav">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a class="nav-link" href="/PARQUEADEROPROYECTOGRADO/paneles/administrador.php">Panel</a>
                <a class="nav-link" href="/PARQUEADEROPROYECTOGRADO/acceso/logout.php">Cerrar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>