<?php
require_once "config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// filtros
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$prov = $_GET['provincia_id'] ?? '';
$val = $_GET['validado'] ?? '';
$texto = trim($_GET['q'] ?? '');

$where = ["DATE(fecha_ocurrida) BETWEEN :d AND :h"];
$params = [":d"=>$desde, ":h"=>$hasta];

if ($prov !== '') { $where[] = "provincia_id = :prov"; $params[":prov"] = (int)$prov; }
if ($val !== '')  { $where[] = "validado = :val"; $params[":val"] = ($val==='1'); }
if ($texto !== '') { 
  $where[] = "(titulo LIKE :q OR descripcion LIKE :q)"; 
  $params[":q"] = "%$texto%"; 
}

$sql = "SELECT i.id_incidencia, i.titulo, i.fecha_ocurrida, i.muertos, i.heridos, i.perdida_rd, 
               i.validado, i.foto_url,
               p.nombre AS provincia
        FROM incidencia i
        JOIN provincia p ON p.id_provincia=i.provincia_id
        WHERE ".implode(" AND ", $where)." 
        ORDER BY fecha_ocurrida DESC
        LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$provincias = $pdo->query("SELECT * FROM provincia ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Incidencias</title>
  <link rel="stylesheet" href="css/ver.css">
</head>
<body>
<header class="topbar">
  <h1>Incidencias</h1>
  <nav>
    <a href="registrar-incide.php">Registrar</a>
    <a href="reporte.php">Reportes</a>
     <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'validador'): ?>
      <a href="super.php">Validador</a>
    <?php endif; ?>
    <?php if(!isset($_SESSION['usuario'])): ?>
      <a href="login.php">Ingresar</a>
    <?php else: ?>
      <a href="logout.php">Salir</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
  <?php if(isset($_GET['msg'])): ?><div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>

  <form class="card filters" method="GET">
    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
    <select name="provincia_id">
      <option value="">Todas las provincias</option>
      <?php foreach($provincias as $p): ?>
        <option value="<?= $p['id_provincia'] ?>" <?= $prov==$p['id_provincia']?'selected':'' ?>>
          <?= htmlspecialchars($p['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="validado">
      <option value="">Todos</option>
      <option value="1" <?= $val==='1'?'selected':'' ?>>Validados</option>
      <option value="0" <?= $val==='0'?'selected':'' ?>>No validados</option>
    </select>
    <input type="search" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($texto) ?>">
    <button type="submit">Filtrar</button>
    <a class="secondary" href="export/export_csv.php?<?= http_build_query($_GET) ?>">Exportar CSV</a>
  </form>

  <div class="table">
    <div class="thead">
      <div>Fecha</div><div>Título</div><div>Provincia</div><div>Muertos</div><div>Heridos</div><div>Pérdida</div><div>Estado</div><div>Acciones</div>
    </div>
    <?php foreach($rows as $r): ?>
      <div class="tr">
        <div><?= date('Y-m-d H:i', strtotime($r['fecha_ocurrida'])) ?></div>
        <div class="cell-title">
          <?php if($r['foto_url']): ?><img src="<?= htmlspecialchars($r['foto_url']) ?>" class="thumb"><?php endif; ?>
          <?= htmlspecialchars($r['titulo']) ?>
        </div>
        <div><?= htmlspecialchars($r['provincia']) ?></div>
        <div><?= (int)$r['muertos'] ?></div>
        <div><?= (int)$r['heridos'] ?></div>
        <div><?= number_format($r['perdida_rd'],2) ?></div>
        <div><?= $r['validado']?'✅':'⏳' ?></div>
        <div>
          <button class="link ver-btn" data-id="<?= $r['id_incidencia'] ?>">Ver</button>
          <?php if(isset($_SESSION['usuario'])): ?>
            <button class="link sugerir-btn" data-id="<?= $r['id_incidencia'] ?>">Sugerir</button>
          <?php endif; ?>
     <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'validador'): ?>
            <button class="link validar-btn" data-id="<?= $r['id_incidencia'] ?>">Validar</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- Modal detalle -->
<div id="modalDetalle" class="modal" hidden>
  <div class="modal-content">
    <button class="close" data-close>×</button>
    <div id="detalleBody">Cargando...</div>
    <hr>
    <?php if(isset($_SESSION['usuario'])): ?>
      <form id="formComentario" class="comment-form">
        <input type="hidden" name="incidencia_id" id="c_incidencia_id">
        <textarea name="texto" rows="3" placeholder="Escribe un comentario público..." required></textarea>
        <button type="submit">Comentar</button>
      </form>
      <div id="comentarios"></div>
    <?php else: ?>
      <p class="muted">Inicia sesión para comentar.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Modal sugerencia -->
<div id="modalSugerir" class="modal" hidden>
  <div class="modal-content">
    <button class="close" data-close>×</button>
    <h3>Sugerir corrección</h3>
    <form id="formSugerencia">
      <input type="hidden" name="incidencia_id" id="s_incidencia_id">
      <label>Campo a corregir
        <select name="campo" required>
          <option value="titulo">Título</option>
          <option value="descripcion">Descripción</option>
          <option value="muertos">Muertos</option>
          <option value="heridos">Heridos</option>
          <option value="perdida_rd">Pérdida RD</option>
          <option value="provincia_id">Provincia</option>
          <option value="municipio_id">Municipio</option>
          <option value="barrio_id">Barrio</option>
          <option value="fecha_ocurrida">Fecha ocurrida</option>
        </select>
      </label>
      <label>Valor propuesto
        <input type="text" name="valor_nuevo" required>
      </label>
      <button type="submit">Enviar sugerencia</button>
    </form>
  </div>
</div>

<script src="js/funciones.js"></script>
</body>
</html>
