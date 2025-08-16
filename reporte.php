<?php
require_once "config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$params=[":d"=>$desde, ":h"=>$hasta];

$totales = $pdo->prepare("SELECT 
  COUNT(*) total,
  SUM(muertos) muertes,
  SUM(heridos) heridos,
  SUM(perdida_rd) perdidas
  FROM incidencia WHERE DATE(fecha_ocurrida) BETWEEN :d AND :h");
$totales->execute($params);
$sum = $totales->fetch();

$por_tipo = $pdo->prepare("SELECT t.nombre, COUNT(*) c
 FROM incidencia i
 JOIN incidencia_tipo it ON it.incidencia_id=i.id_incidencia
 JOIN tipo_incidencia t ON t.id_tipo=it.id_tipo
 WHERE DATE(i.fecha_ocurrida) BETWEEN :d AND :h
 GROUP BY t.nombre ORDER BY c DESC LIMIT 12");
$por_tipo->execute($params);
$dat_tipo=$por_tipo->fetchAll();

$por_fecha = $pdo->prepare("SELECT DATE(fecha_ocurrida) f, COUNT(*) c,
   SUM(muertos) m, SUM(heridos) h, SUM(perdida_rd) p
  FROM incidencia
  WHERE DATE(fecha_ocurrida) BETWEEN :d AND :h
  GROUP BY DATE(fecha_ocurrida) ORDER BY f ASC");
$por_fecha->execute($params);
$dat_fecha=$por_fecha->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes</title>
  <link rel="stylesheet" href="css/estilos.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body>
<header class="topbar">
  <h1>Reportes y Estadísticas</h1>
  <nav>
    <a href="ver_incidencia.php">Incidencias</a>
    <a href="export/export_csv.php?reporte=1&<?= http_build_query($_GET) ?>">Exportar CSV</a>
  </nav>
</header>

<main class="container">
  <form class="card filters" method="GET">
    <label>Desde <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
    <label>Hasta <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
    <button type="submit">Aplicar</button>
  </form>

  <section class="cards-4">
    <div class="card kpi"><h3>Total Incidencias</h3><p><?= (int)($sum['total']??0) ?></p></div>
    <div class="card kpi"><h3>Muertes</h3><p><?= (int)($sum['muertes']??0) ?></p></div>
    <div class="card kpi"><h3>Heridos</h3><p><?= (int)($sum['heridos']??0) ?></p></div>
    <div class="card kpi"><h3>Pérdidas RD$</h3><p><?= number_format($sum['perdidas']??0,2) ?></p></div>
  </section>

  <section class="grid-2">
    <div class="card">
      <h3>Incidencias por Tipo</h3>
      <canvas id="chartTipos"></canvas>
    </div>
    <div class="card">
      <h3>Serie temporal</h3>
      <canvas id="chartSerie"></canvas>
    </div>
  </section>
</main>

<script>
const tipos = <?= json_encode(array_column($dat_tipo,'nombre')) ?>;
const tiposCount = <?= json_encode(array_map('intval', array_column($dat_tipo,'c'))) ?>;

new Chart(document.getElementById('chartTipos'), {
  type: 'bar',
  data: { labels: tipos, datasets: [{ label: 'Incidencias', data: tiposCount }] },
  options: { responsive: true }
});

const fechas = <?= json_encode(array_column($dat_fecha,'f')) ?>;
const cnt = <?= json_encode(array_map('intval', array_column($dat_fecha,'c'))) ?>;
const muer = <?= json_encode(array_map('intval', array_column($dat_fecha,'m'))) ?>;
const heri = <?= json_encode(array_map('intval', array_column($dat_fecha,'h'))) ?>;

new Chart(document.getElementById('chartSerie'), {
  type: 'line',
  data: {
    labels: fechas,
    datasets: [
      { label: 'Incidencias', data: cnt },
      { label: 'Muertes', data: muer },
      { label: 'Heridos', data: heri },
    ]
  },
  options: { responsive: true }
});
</script>
</body>
</html>
