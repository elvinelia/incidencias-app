<?php
require_once "config/db.php";
require_once "utils/auth.php";
require_role(['validador', 'admin']);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (isset($_POST['add'])) {
    $pdo->prepare("INSERT INTO tipo_incidencia (nombre) VALUES (?)")->execute([trim($_POST['nombre'])]);
  } elseif (isset($_POST['del'])) {
    $pdo->prepare("DELETE FROM tipo_incidencia WHERE id_tipo=?")->execute([(int)$_POST['id']]);
  } elseif (isset($_POST['edit'])) {
    $pdo->prepare("UPDATE tipo_incidencia SET nombre=? WHERE id_tipo=?")->execute([trim($_POST['nombre']),(int)$_POST['id']]);
  }
  header("Location: tipos.php"); exit;
}
$rows = $pdo->query("SELECT * FROM tipo_incidencia ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Tipos</title><link rel="stylesheet" href="css/estilos.css"></head><body>
<header class="topbar"><h1>Tipos de Incidencia</h1><nav><a href="index.php">Inicio</a></nav></header>
<main class="container">
  <form class="card" method="post">
    <label>Nombre <input name="nombre" required></label>
    <button name="add">Agregar</button>
  </form>
  <div class="table">
    <div class="thead"><div>ID</div><div>Nombre</div><div>Acciones</div></div>
    <?php foreach($rows as $r): ?>
      <div class="tr">
        <div><?= $r['id_tipo'] ?></div>
        <div>
          <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= $r['id_tipo'] ?>">
            <input name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>">
            <button name="edit">Guardar</button>
          </form>
        </div>
        <div>
          <form method="post" onsubmit="return confirm('¿Eliminar?')">
            <input type="hidden" name="id" value="<?= $r['id_tipo'] ?>">
            <button name="del" class="danger">Eliminar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main></body></html>
