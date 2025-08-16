// Debes tener Leaflet y Leaflet.markercluster cargados en index.php
document.addEventListener("DOMContentLoaded", () => {
    const map = L.map('map').setView([18.5, -69.9], 8); // Centro RD

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map);

    const markers = L.markerClusterGroup();

    fetch('api/incidencias_24h.php')
        .then(res => res.json())
        .then(data => {
            data.forEach(inc => {
                let icon = L.icon({
                    iconUrl: `img/icon_${inc.tipos.split(',')[0]}.png`,
                    iconSize: [32, 32]
                });

                let marker = L.marker([inc.lat, inc.lng], { icon });
                marker.bindPopup(`
                    <strong>${inc.titulo}</strong><br>
                    <small>${inc.fecha}</small><br>
                    Muertos: ${inc.muertos} | Heridos: ${inc.heridos}<br>
                    <a href="ver_incidencia.php?id=${inc.id}">Ver detalles</a>
                `);
                markers.addLayer(marker);
            });

            map.addLayer(markers);
        });
});
