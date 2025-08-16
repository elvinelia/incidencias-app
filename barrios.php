<?php
require_once "config/db.php";
require_once "utils/auth.php";
require_role(['validador', 'admin']);

$municipios = $pdo->query("SELECT m.id_municipio, CONCAT(p.nombre,' - ',m.nombre) nom
  FROM municipio m JOIN provincia p ON p.id_provincia=m.provincia_id
  ORDER BY p.nombre, m.nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (isset($_POST['add'])) {
    $pdo->prepare("INSERT INTO barrio (nombre, municipio_id) VALUES (?,?)")
        ->execute([trim($_POST['nombre']),(int)$_POST['municipio_id']]);
  } elseif (isset($_POST['del'])) {
    $pdo->prepare("DELETE FROM barrio WHERE id_barrio=?")->execute([(int)$_POST['id']]);
  } elseif (isset($_POST['edit'])) {
    $pdo->prepare("UPDATE barrio SET nombre=?, municipio_id=? WHERE id_barrio=?")
        ->execute([trim($_POST['nombre']),(int)$_POST['municipio_id'],(int)$_POST['id']]);
  }
  header("Location: barrios.php"); exit;
}
$rows = $pdo->query("SELECT b.*, m.nombre AS mun, p.nombre AS prov
 FROM barrio b
 JOIN municipio m ON m.id_municipio=b.municipio_id
 JOIN provincia p ON p.id_provincia=m.provincia_id
 ORDER BY p.nombre, m.nombre, b.nombre")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Barrios</title><link rel="stylesheet" href="css/estilos.css"></head><body>
<header class="topbar"><h1>Barrios</h1><nav><a href="index.php">Inicio</a></nav></header>
<main class="container">
  <form class="card" method="post">
    <label>Nombre <input name="nombre" required></label>
    <label>Municipio
      <select name="municipio_id" required>
        <?php foreach($municipios as $m): ?>
          <option value="<?= $m['id_municipio'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button name="add">Agregar</button>
  </form>
  <div class="table">
    <div class="thead"><div>ID</div><div>Nombre</div><div>Municipio</div><div>Provincia</div><div>Acciones</div></div>
    <?php foreach($rows as $r): ?>
      <div class="tr">
        <div><?= $r['id_barrio'] ?></div>
        <div>
          <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= $r['id_barrio'] ?>">
            <input name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>">
            <select name="municipio_id">
              <?php foreach($municipios as $m): ?>
                <option value="<?= $m['id_municipio'] ?>" <?= $m['id_municipio']==$r['municipio_id']?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <button name="edit">Guardar</button>
          </form>
        </div>
        <div><?= htmlspecialchars($r['mun']) ?></div>
        <div><?= htmlspecialchars($r['prov']) ?></div>
        <div>
          <form method="post" onsubmit="return confirm('¿Eliminar?')">
            <input type="hidden" name="id" value="<?= $r['id_barrio'] ?>">
            <button name="del" class="danger">Eliminar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main></body></html>
