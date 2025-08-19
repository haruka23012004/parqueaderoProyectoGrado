<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parqueadero Universitario</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
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
            font-family: 'Open Sans', sans-serif;
            color: var(--color-negro);
        }
        
        h1, h2, h3, h4 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        
        /* Navbar mejorada */
        .navbar {
            background-color: var(--color-negro) !important;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand img {
            height: 50px;
            transition: all 0.3s;
        }
        
        .nav-link {
            color: var(--color-blanco) !important;
            font-weight: 600;
            padding: 8px 15px !important;
            margin: 0 5px;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: var(--color-naranja) !important;
            color: var(--color-blanco) !important;
            transform: translateY(-2px);
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        /* Hero Section rediseñada */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('assets/img/campus.png') no-repeat center center/cover;
            height: 80vh;
            min-height: 600px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            animation: fadeIn 1s ease-out;
        }
        
        .hero-title {
            font-size: 3.5rem;
            color: var(--color-blanco);
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
            margin-bottom: 20px;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: var(--color-blanco);
            opacity: 0.9;
            margin-bottom: 30px;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.4);
        }
        
        .btn-hero {
            background-color: var(--color-naranja);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(239, 62, 54, 0.4);
            transition: all 0.3s;
            margin: 0 10px;
        }
        
        .btn-hero:hover {
            background-color: var(--color-rojo);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(239, 62, 54, 0.6);
        }
        
        .btn-outline-hero {
            border: 2px solid var(--color-blanco);
            color: var(--color-blanco);
            background: transparent;
        }
        
        .btn-outline-hero:hover {
            background-color: var(--color-blanco);
            color: var(--color-negro);
        }
        
        /* Features Section mejorada */
        .features-section {
            padding: 80px 0;
            background-color: #f8f9fa;
            position: relative;
        }
        
        .section-title {
            color: var(--color-negro);
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--color-naranja), var(--color-rojo));
            border-radius: 2px;
        }
        
        .feature-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            background-color: var(--color-blanco);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .feature-icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--color-naranja), var(--color-rojo));
            color: white;
            font-size: 2rem;
            box-shadow: 0 5px 15px rgba(239, 62, 54, 0.3);
        }
        
        .feature-title {
            color: var(--color-negro);
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .feature-text {
            color: var(--color-gris);
        }
        
        /* Footer mejorado */
        .footer {
            background: linear-gradient(to right, var(--color-negro), #343a40);
            color: var(--color-blanco);
            padding: 50px 0 20px;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--color-naranja), var(--color-rojo));
        }
        
        .footer-logo {
            height: 60px;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }
        
        .footer-text {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .social-icons a {
            color: var(--color-blanco);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            margin: 0 8px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background-color: var(--color-naranja);
            transform: translateY(-3px);
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-section {
                height: 70vh;
                min-height: 500px;
            }
            
            .btn-hero {
                display: block;
                width: 80%;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/img/logoUniguajira.png" alt="Logo Universidad">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registro.php"><i class="fas fa-user-plus"></i> Registro</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="acceso/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center hero-content">
                    <h1 class="hero-title">Sistema de Parqueadero Universitario</h1>
                    <p class="hero-subtitle">Gestión inteligente de estacionamiento para la comunidad de la Universidad de la Guajira</p>
                    <div>
                        <a href="registro.php" class="btn btn-hero">
                            <i class="fas fa-car me-2"></i> Solicitar acceso
                        </a>
                        <a href="/PARQUEADEROPROYECTOGRADO/acceso/login.php" class="btn btn-outline-hero">
                            <i class="fas fa-sign-in-alt me-2"></i> Acceso usuarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="text-center section-title">Nuestras Ventajas</h2>
            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-4">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon-container">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Acceso Seguro</h3>
                        <p class="feature-text">Control de acceso mediante identificación QR para garantizar la seguridad de tu vehículo.</p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="col-md-4">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon-container">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="feature-title">Horario Flexible</h3>
                        <p class="feature-text">Acceso disponible según tu horario académico con registro automatizado.</p>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="col-md-4">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon-container">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3 class="feature-title">Zonas Delimitadas</h3>
                        <p class="feature-text">Espacios organizados por tipo de vehículo y prioridades de estacionamiento.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <img src="assets/img/logoUniguajira.png" alt="Logo Universidad" class="footer-logo">
                    
                    <p class="mb-0">© <?= date('Y') ?> Universidad de la Guajira. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efecto de navbar al hacer scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.padding = '10px 0';
                navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.padding = '15px 0';
                navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
            }
        });
    </script>
</body>
</html>