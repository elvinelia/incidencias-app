<?php
session_start();
require_once "config/db.php";

// Usuario logueado
$usuario = $_SESSION['usuario'] ?? null;

// Obtener incidencias de las últimas 24 horas
$incidencias = $pdo->prepare("
    SELECT i.id_incidencia, i.titulo, i.descripcion, i.latitud, i.longitud, 
           i.fecha_ocurrida, i.muertos, i.heridos, i.perdida_rd,
           i.link_red, i.foto_url,
           GROUP_CONCAT(t.nombre SEPARATOR ', ') AS tipos,
           p.nombre AS provincia,
           m.nombre AS municipio,
           b.nombre AS barrio
    FROM incidencia i
    LEFT JOIN incidencia_tipo it ON i.id_incidencia = it.incidencia_id
    LEFT JOIN tipo_incidencia t ON it.id_tipo = t.id_tipo
    LEFT JOIN provincia p ON i.provincia_id = p.id_provincia
    LEFT JOIN municipio m ON i.municipio_id = m.id_municipio
    LEFT JOIN barrio b ON i.barrio_id = b.id_barrio
    WHERE i.creado_en >= NOW() - INTERVAL 1 DAY
      AND i.validado = 1
    GROUP BY i.id_incidencia
    ORDER BY i.fecha_ocurrida DESC
");
$incidencias->execute();
$incidencias = $incidencias->fetchAll(PDO::FETCH_ASSOC);

// Obtener tipos y provincias para filtros
$tipos = $pdo->query("SELECT nombre FROM tipo_incidencia ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
$provincias = $pdo->query("SELECT nombre FROM provincia ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

// Calcular estadísticas
$totalIncidencias = count($incidencias);
$totalMuertos = array_sum(array_column($incidencias, 'muertos'));
$totalHeridos = array_sum(array_column($incidencias, 'heridos'));
$totalPerdidas = array_sum(array_column($incidencias, 'perdida_rd'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Reporte de Incidencias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <!-- Marker Cluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/index.css">
    
   
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Header -->
    <header class="custom-header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h1><i class="fas fa-exclamation-triangle me-2"></i>Sistema de Incidencias</h1>
            <div class="user-section">
                <?php if ($usuario): ?>
                    <span class="me-2">Hola, <strong><?= htmlspecialchars($usuario) ?></strong></span>
                    <a class="btn-custom btn-outline-custom" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>Cerrar sesión
                    </a>
                <?php else: ?>
                    <a class="btn-custom btn-light-custom me-2" href="login.php">
                        <i class="fas fa-sign-in-alt"></i>Iniciar Sesión
                    </a>
                    <a class="btn-custom btn-primary-custom" href="registrar.php">
                        <i class="fas fa-user-plus"></i>Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Dashboard -->
    <div class="dashboard-container fade-in">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-number" id="totalIncidents"><?= $totalIncidencias ?></div>
                <div class="stat-label">Total Incidencias</div>
            </div>
            <div class="stat-card deaths">
                <div class="stat-icon"><i class="fas fa-skull-crossbones"></i></div>
                <div class="stat-number" id="totalDeaths"><?= $totalMuertos ?></div>
                <div class="stat-label">Total Muertos</div>
            </div>
            <div class="stat-card injured">
                <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
                <div class="stat-number" id="totalInjured"><?= $totalHeridos ?></div>
                <div class="stat-label">Total Heridos</div>
            </div>
            <div class="stat-card losses">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-number" id="totalLosses">RD$ <?= number_format($totalPerdidas) ?></div>
                <div class="stat-label">Pérdidas Totales</div>
            </div>
        </div>

        <div class="row">
            <!-- Filters Sidebar -->
            <aside class="col-md-3">
                <div class="filters-sidebar slide-in">
                    <h5><i class="fas fa-filter me-2"></i>Filtros</h5>
                    
                    <div class="filter-group">
                        <label class="filter-label">Provincia</label>
                        <select id="filtroProvincia" class="form-select">
                            <option value="">Todas las provincias</option>
                            <?php foreach($provincias as $prov): ?>
                                <option value="<?= htmlspecialchars($prov) ?>"><?= htmlspecialchars($prov) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Tipo de Incidencia</label>
                        <select id="filtroTipo" class="form-select">
                            <option value="">Todos los tipos</option>
                            <?php foreach($tipos as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Buscar</label>
                        <input type="text" id="buscarTitulo" class="form-control" placeholder="Buscar por título o descripción...">
                    </div>

                    <button class="btn-custom btn-primary-custom w-100" onclick="limpiarFiltros()">
                        <i class="fas fa-undo me-2"></i>Limpiar Filtros
                    </button>
                </div>
            </aside>

            <!-- Map Section -->
            <main class="col-md-9">
                <div class="map-container fade-in">
                    <div class="map-header">
                        <h5 class="map-title">
                            <i class="fas fa-map-marked-alt me-2"></i>Mapa de Incidencias
                        </h5>
                        <small class="map-subtitle">Últimas 24 horas</small>
                    </div>
                    <div id="mapa"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Datos de incidencias desde PHP
        const INCIDENCIAS = <?= json_encode($incidencias, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

        let map, markersCluster;

        // Initialize map
        function initializeMap() {
            map = L.map('mapa').setView([18.7357, -70.1627], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            markersCluster = L.markerClusterGroup({
                iconCreateFunction: function(cluster) {
                    var count = cluster.getChildCount();
                    var c = ' marker-cluster-';
                    if (count < 10) {
                        c += 'small';
                    } else if (count < 100) {
                        c += 'medium';
                    } else {
                        c += 'large';
                    }
                    return new L.DivIcon({
                        html: '<div><span>' + count + '</span></div>',
                        className: 'marker-cluster' + c,
                        iconSize: new L.Point(40, 40)
                    });
                }
            });

            mostrarMarcadores();
        }

        // Create custom marker icons
        function getMarkerIcon(tipo) {
            let color = '#2563eb';
            let icon = 'exclamation-triangle';
            
            const tipoLower = tipo.toLowerCase();
            if (tipoLower.includes('accidente') || tipoLower.includes('tránsito')) {
                color = '#dc2626';
                icon = 'car-crash';
            } else if (tipoLower.includes('robo') || tipoLower.includes('asalto')) {
                color = '#f59e0b';
                icon = 'user-ninja';
            } else if (tipoLower.includes('incendio') || tipoLower.includes('fuego')) {
                color = '#ea580c';
                icon = 'fire';
            } else if (tipoLower.includes('violencia') || tipoLower.includes('agresión')) {
                color = '#7c2d12';
                icon = 'fist-raised';
            }

            return L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${color}; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"><i class="fas fa-${icon}"></i></div>`,
                iconSize: [25, 25],
                iconAnchor: [12, 12]
            });
        }

        function mostrarMarcadores(filtroProvincia = '', filtroTipo = '', buscarTitulo = '') {
            markersCluster.clearLayers();

            const incidenciasFiltradas = INCIDENCIAS.filter(i => {
                let titulo = i.titulo.toLowerCase();
                let descripcion = i.descripcion.toLowerCase();
                let provincia = (i.provincia || '').toLowerCase();
                let tipo = (i.tipos || '').toLowerCase();
                let filtroT = filtroTipo.toLowerCase();
                let filtroP = filtroProvincia.toLowerCase();
                let buscar = buscarTitulo.toLowerCase();

                return (filtroProvincia === '' || provincia.includes(filtroP)) &&
                       (filtroTipo === '' || tipo.includes(filtroT)) &&
                       (buscarTitulo === '' || titulo.includes(buscar) || descripcion.includes(buscar));
            });

            incidenciasFiltradas.forEach(i => {
                let popup = `
                    <div style="font-family: Inter, sans-serif;">
                        <div style="font-weight: 600; color: #1f2937; font-size: 1.1rem; margin-bottom: 0.5rem; line-height: 1.3;">
                            ${i.titulo}
                        </div>
                        <div style="background: #2563eb; color: white; padding: 0.2rem 0.6rem; border-radius: 15px; font-size: 0.75rem; display: inline-block; margin-bottom: 0.5rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">
                            ${i.tipos || 'Sin especificar'}
                        </div>
                        <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.3rem;">
                            <i class="fas fa-map-marker-alt"></i>
                            ${i.provincia || ''} / ${i.municipio || ''} / ${i.barrio || ''}
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin: 0.75rem 0; padding: 0.75rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <div style="text-align: center; font-size: 0.8rem;">
                                <div style="font-weight: 700; font-size: 1.1rem; color: #dc2626; margin-bottom: 0.2rem;">${i.muertos}</div>
                                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase;">Muertos</div>
                            </div>
                            <div style="text-align: center; font-size: 0.8rem;">
                                <div style="font-weight: 700; font-size: 1.1rem; color: #dc2626; margin-bottom: 0.2rem;">${i.heridos}</div>
                                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase;">Heridos</div>
                            </div>
                        </div>
                        <div style="margin: 0.75rem 0; padding: 0.75rem; background: rgba(5, 150, 105, 0.1); border-radius: 8px; border: 1px solid rgba(5, 150, 105, 0.2); text-align: center;">
                            <div style="font-weight: 700; font-size: 1rem; color: #059669;">
                                Pérdida: RD$ ${parseInt(i.perdida_rd || 0).toLocaleString()}
                            </div>
                        </div>
                `;

                if(i.link_red) {
                    popup += `<div style="margin-top: 0.75rem;">
                        <a href="${i.link_red}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.8rem; background: #2563eb; color: white; text-decoration: none; border-radius: 15px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-external-link-alt"></i>Ver más
                        </a>
                    </div>`;
                }

                if(i.foto_url) {
                    popup += `<img src="${i.foto_url}" style="width: 100%; max-width: 200px; border-radius: 8px; margin-top: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;" alt="Imagen de incidencia">`;
                }

                popup += `</div>`;

                let marker = L.marker([parseFloat(i.latitud), parseFloat(i.longitud)], {
                    icon: getMarkerIcon(i.tipos || 'incidencia')
                }).bindPopup(popup);
                
                markersCluster.addLayer(marker);
            });

            map.addLayer(markersCluster);
        }

        function limpiarFiltros() {
            document.getElementById('filtroProvincia').value = '';
            document.getElementById('filtroTipo').value = '';
            document.getElementById('buscarTitulo').value = '';
            mostrarMarcadores();
        }

        // Animate counter numbers
        function animateCounter(elementId, finalValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const isNumber = typeof finalValue === 'number';
            const duration = 2000;
            const start = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                if (isNumber) {
                    const currentValue = Math.floor(finalValue * progress);
                    element.textContent = currentValue;
                } else {
                    // For currency values
                    const numericValue = parseInt(finalValue.replace(/[^\d]/g, ''));
                    const currentValue = Math.floor(numericValue * progress);
                    element.textContent = 'RD$ ' + currentValue.toLocaleString();
                }
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading overlay
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hide');
            }, 1000);

            // Initialize map
            setTimeout(() => {
                initializeMap();
                
                // Animate statistics counters
                animateCounter('totalIncidents', <?= $totalIncidencias ?>);
                animateCounter('totalDeaths', <?= $totalMuertos ?>);
                animateCounter('totalInjured', <?= $totalHeridos ?>);
                animateCounter('totalLosses', 'RD$ <?= number_format($totalPerdidas) ?>');
            }, 1200);

            // Filter event listeners
            document.getElementById('filtroProvincia').addEventListener('change', function(){
                mostrarMarcadores(this.value, document.getElementById('filtroTipo').value, document.getElementById('buscarTitulo').value);
            });

            document.getElementById('filtroTipo').addEventListener('change', function(){
                mostrarMarcadores(document.getElementById('filtroProvincia').value, this.value, document.getElementById('buscarTitulo').value);
            });

            document.getElementById('buscarTitulo').addEventListener('input', function(){
                mostrarMarcadores(document.getElementById('filtroProvincia').value, document.getElementById('filtroTipo').value, this.value);
            });
        });

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Custom marker cluster styles
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.textContent = `
                .marker-cluster-small {
                    background: rgba(37, 99, 235, 0.8) !important;
                }
                .marker-cluster-small div {
                    background: rgba(37, 99, 235, 0.9) !important;
                }
                .marker-cluster-medium {
                    background: rgba(245, 158, 11, 0.8) !important;
                }
                .marker-cluster-medium div {
                    background: rgba(245, 158, 11, 0.9) !important;
                }
                .marker-cluster-large {
                    background: rgba(220, 38, 38, 0.8) !important;
                }
                .marker-cluster-large div {
                    background: rgba(220, 38, 38, 0.9) !important;
                }
                .leaflet-control-zoom a {
                    border-radius: 8px !important;
                    border: none !important;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                }
                .leaflet-control-zoom a:hover {
                    background: #2563eb !important;
                    color: white !important;
                }
                .custom-marker:hover {
                    transform: scale(1.1);
                    transition: transform 0.2s ease;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>