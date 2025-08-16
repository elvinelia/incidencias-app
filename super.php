<?php
require_once "config/db.php";
require_once "utils/auth.php"; // opcional
require_role(['validador', 'admin']);

// incidencias no validadas
$pend = $pdo->query("SELECT id_incidencia, titulo, fecha_ocurrida, latitud, longitud FROM incidencia WHERE validado=0 ORDER BY fecha_ocurrida DESC LIMIT 200")->fetchAll();

// búsqueda duplicados simple: por distancia (lat/long no nulos) y +- 1 día
function buscarDuplicados($pdo, $id) {
    $q = $pdo->prepare("SELECT * FROM incidencia WHERE id_incidencia=?");
    $q->execute([$id]); $base = $q->fetch();
    if (!$base || !$base['latitud'] || !$base['longitud']) return [];
    $q2 = $pdo->prepare("SELECT id_incidencia, titulo, fecha_ocurrida, 
        (ABS(latitud-?)+ABS(longitud-?)) dist
        FROM incidencia 
        WHERE id_incidencia<>? 
          AND latitud IS NOT NULL AND longitud IS NOT NULL
          AND DATE(fecha_ocurrida) BETWEEN DATE(?) - INTERVAL 1 DAY AND DATE(?) + INTERVAL 1 DAY
        HAVING dist < 0.02
        ORDER BY dist ASC LIMIT 20");
    $q2->execute([$base['latitud'],$base['longitud'],$id,$base['fecha_ocurrida'],$base['fecha_ocurrida']]);
    return $q2->fetchAll();
}

$dup_map = [];
foreach($pend as $p) {
  $dup_map[$p['id_incidencia']] = buscarDuplicados($pdo, $p['id_incidencia']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Validador</title>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="topbar">
  <h1>Centro de Validación</h1>
  <nav>
    <a href="ver_incidencia.php">Volver</a>
  </nav>
</header>
<main class="container">
  <h3>Pendientes de validación</h3>
  <div class="table">
    <div class="thead"><div>ID</div><div>Título</div><div>Fecha</div><div>Posibles duplicados</div><div>Acciones</div></div>
    <?php foreach($pend as $p): ?>
      <div class="tr">
        <div>#<?= $p['id_incidencia'] ?></div>
        <div><?= htmlspecialchars($p['titulo']) ?></div>
        <div><?= $p['fecha_ocurrida'] ?></div>
        <div>
          <?php if(empty($dup_map[$p['id_incidencia']])): ?>
            <span class="muted">Ninguno</span>
          <?php else: ?>
            <?php foreach($dup_map[$p['id_incidencia']] as $d): ?>
              <div>→ #<?= $d['id_incidencia'] ?> (<?= $d['fecha_ocurrida'] ?>) - <?= htmlspecialchars($d['titulo']) ?></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div>
          <button class="link validar-btn" data-id="<?= $p['id_incidencia'] ?>">Validar</button>
          <?php if(!empty($dup_map[$p['id_incidencia']])): ?>
            <button class="link fusionar-btn" data-id="<?= $p['id_incidencia'] ?>">Fusionar...</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- Modal fusionar -->
<div id="modalFusion" class="modal" hidden>
  <div class="modal-content">
    <button class="close" data-close>×</button>
    <h3>Fusionar reportes duplicados</h3>
    <form id="formFusion">
      <input type="hidden" name="principal_id" id="principal_id">
      <label>IDs a fusionar (separados por coma)
        <input type="text" name="ids" placeholder="12,15,18">
      </label>
      <p class="muted">Se conservará el registro principal y se sumarán muertos/heridos y se mantendrá el mayor valor de pérdida. Los comentarios se moverán.</p>
      <button type="submit">Fusionar</button>
    </form>
  </div>
</div>

<script src="js/funciones.js"></script>
</body>
</html>
