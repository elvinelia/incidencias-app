<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener datos del usuario
$usuarioNombre = $_SESSION['usuario']['nombre'] ?? 'Usuario';
$usuarioRol = $_SESSION['usuario']['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    
    <link rel="stylesheet" href="/css/panel.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-sA+9cP+M3d+op0g+rXoj7v6XxVg5F8r3rF3GvCq30/0="
        crossorigin=""/>
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="app-container">
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h2><i class="fas fa-shield-alt"></i> Incidencias</h2>
            <a href="registrar-incide.php">
                <i class="fas fa-plus-circle"></i>
                <span>Registrar Incidencia</span>
            </a>
            <a href="ver_incidencia.php">
                <i class="fas fa-list-ul"></i>
                <span>Ver Incidencias</span>
            </a>
            <a href="reporte.php">
                <i class="fas fa-chart-bar"></i>
                <span>Generar Reporte</span>
            </a>

            <?php if($usuarioRol === 'validador'): ?>
            <!-- Administrar catálogos solo para validadores -->
            <h3 class="mt-4"><i class="fas fa-database"></i> Catálogos</h3>
            <a href="provincias.php">
                <i class="fas fa-map-marker-alt"></i>
                <span>Provincias</span>
            </a>
            <a href="municipios.php">
                <i class="fas fa-city"></i>
                <span>Municipios</span>
            </a>
            <a href="barrios.php">
                <i class="fas fa-home"></i>
                <span>Barrios</span>
            </a>
            <?php endif; ?>

            <a href="#" onclick="showUserProfile()">
                <i class="fas fa-user-circle"></i>
                <span>Mi Perfil</span>
            </a>
            <a href="logout.php" onclick="return confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <div class="header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Bienvenido al Panel de Control, <?= htmlspecialchars($usuarioNombre) ?>
                </h1>
                <a href="index.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver al Inicio
                </a> 
            </div>

            <!-- Welcome Box -->
            <div class="welcome-box">
                <p>
                    <i class="fas fa-info-circle"></i>
                    Desde aquí puedes gestionar las incidencias registradas en el sistema. 
                    Utiliza el menú lateral para navegar entre las funciones disponibles y 
                    monitorea el estado de todas las incidencias en tiempo real.
                </p>
            </div>

            <!-- Map Section -->
            <div class="map-section">
                <h2>
                    <i class="fas fa-map-marked-alt"></i>
                    Mapa de Incidencias
                </h2>
                <div id="map" class="map-loading"></div>
            </div>

            <!-- Footer -->
            <footer>
                <i class="fas fa-copyright"></i>
                2025 Sistema de Reporte de Incidencias - Desarrollado con tecnología moderna
            </footer>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-oVZrH1G5W2v1v5sAYBf8l0XjPf0iq+LL1Fz2Q2RYG7M="
            crossorigin=""></script>

    <script>
        // Variables globales
        let map;
        let incidencias = []; // Aquí se cargarían desde PHP

        // Simulación de datos (reemplazar por datos reales de PHP)
        const sampleIncidencias = [
            {tipo: "Accidente", descripcion: "Colisión vehicular", latitud: "18.7357", longitud: "-70.1627"},
            {tipo: "Robo", descripcion: "Asalto a mano armada", latitud: "18.4861", longitud: "-69.9312"},
            {tipo: "Incendio", descripcion: "Fuego en edificio", latitud: "18.5204", longitud: "-69.9442"}
        ];

        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hide');
                initializeMap();
                initializeSidebar();
            }, 1500);
        });

        // Inicializar mapa
        function initializeMap() {
            document.getElementById('map').classList.remove('map-loading');
            map = L.map('map').setView([18.7357, -70.1627], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            addIncidenciaMarkers();
            addMapControls();
        }

        // Agregar marcadores
        function addIncidenciaMarkers() {
            const iconColors = {
                'Accidente': '#dc2626',
                'Robo': '#f59e0b',
                'Incendio': '#059669',
                'default': '#2563eb'
            };
            sampleIncidencias.forEach(function(inc) {
                if (inc.latitud && inc.longitud) {
                    const color = iconColors[inc.tipo] || iconColors.default;
                    const customIcon = L.divIcon({
                        className: 'custom-marker',
                        html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });
                    let popupContent = `
                        <div style="min-width: 200px;">
                            <h4 style="color: ${color}; margin: 0 0 8px 0;">
                                <i class="fas fa-exclamation-triangle"></i> ${inc.tipo}
                            </h4>
                            <p style="margin: 0; color: #374151;">${inc.descripcion}</p>
                            <hr style="margin: 8px 0; border: none; border-top: 1px solid #e5e7eb;">
                            <small style="color: #6b7280;">
                                <i class="fas fa-map-marker-alt"></i> 
                                ${parseFloat(inc.latitud).toFixed(4)}, ${parseFloat(inc.longitud).toFixed(4)}
                            </small>
                        </div>
                    `;
                    L.marker([parseFloat(inc.latitud), parseFloat(inc.longitud)], {icon: customIcon})
                        .addTo(map)
                        .bindPopup(popupContent);
                }
            });
        }

        // Controles del mapa
        function addMapControls() {
            map.zoomControl.setPosition('topright');
            L.control.scale({position: 'bottomleft', metric: true, imperial: false}).addTo(map);
        }

        // Sidebar
        function initializeSidebar() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                }
            });

            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }

        // Funciones extra
        function confirmLogout() {
            return confirm('¿Estás seguro de que deseas cerrar sesión?');
        }

        function showUserProfile() {
            alert('Función de perfil de usuario en desarrollo');
        }

        window.addEventListener('resize', function() {
            if (map) setTimeout(() => map.invalidateSize(), 300);
        });

        // Animaciones
        const observerOptions = {threshold: 0.1, rootMargin: '0px 0px -50px 0px'};
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.addEventListener('DOMContentLoaded', function() {
            const elementsToAnimate = document.querySelectorAll('.header, .welcome-box, .map-section');
            elementsToAnimate.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
