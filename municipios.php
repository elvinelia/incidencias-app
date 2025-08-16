<?php
require_once "config/db.php";
require_once "utils/auth.php";
require_role(['validador', 'admin']);

$provincias = $pdo->query("SELECT * FROM provincia ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (isset($_POST['add'])) {
    $pdo->prepare("INSERT INTO municipio (nombre, provincia_id) VALUES (?,?)")
        ->execute([trim($_POST['nombre']),(int)$_POST['provincia_id']]);
  } elseif (isset($_POST['del'])) {
    $pdo->prepare("DELETE FROM municipio WHERE id_municipio=?")->execute([(int)$_POST['id']]);
  } elseif (isset($_POST['edit'])) {
    $pdo->prepare("UPDATE municipio SET nombre=?, provincia_id=? WHERE id_municipio=?")
        ->execute([trim($_POST['nombre']),(int)$_POST['provincia_id'],(int)$_POST['id']]);
  }
  header("Location: municipios.php"); exit;
}
$rows = $pdo->query("SELECT m.*, p.nombre AS provincia FROM municipio m JOIN provincia p ON p.id_provincia=m.provincia_id ORDER BY p.nombre, m.nombre")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Municipios</title><link rel="stylesheet" href="css/estilos.css"></head><body>
<header class="topbar"><h1>Municipios</h1><nav><a href="index.php">Inicio</a></nav></header>
<main class="container">
  <form class="card" method="post">
    <label>Nombre <input name="nombre" required></label>
    <label>Provincia
      <select name="provincia_id" required>
        <?php foreach($provincias as $p): ?>
          <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button name="add">Agregar</button>
  </form>
  <div class="table">
    <div class="thead"><div>ID</div><div>Nombre</div><div>Provincia</div><div>Acciones</div></div>
    <?php foreach($rows as $r): ?>
      <div class="tr">
        <div><?= $r['id_municipio'] ?></div>
        <div>
          <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= $r['id_municipio'] ?>">
            <input name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>">
            <select name="provincia_id">
              <?php foreach($provincias as $p): ?>
                <option value="<?= $p['id_provincia'] ?>" <?= $p['id_provincia']==$r['provincia_id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <button name="edit">Guardar</button>
          </form>
        </div>
        <div><?= htmlspecialchars($r['provincia']) ?></div>
        <div>
          <form method="post" onsubmit="return confirm('¿Eliminar?')">
            <input type="hidden" name="id" value="<?= $r['id_municipio'] ?>">
            <button name="del" class="danger">Eliminar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main></body></html>
