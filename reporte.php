<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once "config/db.php";

// Fecha de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// Construir consulta dinámica
$where = [];
$params = [];

if ($fecha_inicio) {
    $where[] = "fecha_ocurrida >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio . " 00:00:00";
}
if ($fecha_fin) {
    $where[] = "fecha_ocurrida <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin . " 23:59:59";
}

$sql = "SELECT tipo, COUNT(*) AS total, SUM(muertos) AS muertos, SUM(heridos) AS heridos, SUM(perdida_rd) AS perdida
        FROM incidencia";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY tipo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tipos = [];
$totales = [];
$muertos = [];
$heridos = [];
$perdidas = [];

foreach ($estadisticas as $row) {
    $tipos[] = $row['tipo'];
    $totales[] = (int)$row['total'];
    $muertos[] = (int)$row['muertos'];
    $heridos[] = (int)$row['heridos'];
    $perdidas[] = (float)$row['perdida'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Incidencias</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
    <h1 class="h4 m-0">Reportes de Incidencias</h1>
    <div>
        <span class="me-2">Hola, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></span>
        <a class="btn btn-light" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="container my-4">

    <!-- Filtros -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label>Fecha Inicio:</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
        </div>
        <div class="col-md-4">
            <label>Fecha Fin:</label>
            <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
        </div>
        <div class="col-md-4 align-self-end">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <!-- Exportar -->
    <div class="mb-4">
        <button class="btn btn-success me-2">Exportar a Excel</button>
        <button class="btn btn-danger">Exportar a PDF</button>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-md-6">
            <canvas id="graficoTotales"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="graficoMuertos"></canvas>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6">
            <canvas id="graficoHeridos"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="graficoPerdidas"></canvas>
        </div>
    </div>

</div>

<script>
const tipos = <?= json_encode($tipos) ?>;
const totales = <?= json_encode($totales) ?>;
const muertos = <?= json_encode($muertos) ?>;
const heridos = <?= json_encode($heridos) ?>;
const perdidas = <?= json_encode($perdidas) ?>;

const colores = ['#007bff','#dc3545','#ffc107','#28a745','#6f42c1','#fd7e14'];

// Gráfico de Totales
new Chart(document.getElementById('graficoTotales'), {
    type: 'bar',
    data: {
        labels: tipos,
        datasets: [{
            label: 'Número de Incidencias',
            data: totales,
            backgroundColor: colores
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});

// Gráfico de Muertos
new Chart(document.getElementById('graficoMuertos'), {
    type: 'bar',
    data: {
        labels: tipos,
        datasets: [{
            label: 'Muertos',
            data: muertos,
            backgroundColor: colores
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});

// Gráfico de Heridos
new Chart(document.getElementById('graficoHeridos'), {
    type: 'bar',
    data: {
        labels: tipos,
        datasets: [{
            label: 'Heridos',
            data: heridos,
            backgroundColor: colores
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});

// Gráfico de Pérdidas RD$
new Chart(document.getElementById('graficoPerdidas'), {
    type: 'bar',
    data: {
        labels: tipos,
        datasets: [{
            label: 'Pérdidas (RD$)',
            data: perdidas,
            backgroundColor: colores
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
