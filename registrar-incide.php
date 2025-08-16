<?php
require_once "config/db.php";
require_once "utils/auth.php"; // opcional si lo creaste
require_login();

// catálogos
$provincias = $pdo->query("SELECT * FROM provincia ORDER BY nombre")->fetchAll();
$tipos = $pdo->query("SELECT * FROM tipo_incidencia ORDER BY nombre")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $provincia_id = (int)$_POST['provincia_id'];
    $municipio_id = (int)$_POST['municipio_id'];
    $barrio_id = (int)$_POST['barrio_id'];
    $fecha_ocurrida = $_POST['fecha_ocurrida'];
    $muertos = (int)($_POST['muertos'] ?? 0);
    $heridos = (int)($_POST['heridos'] ?? 0);
    $perdida_rd = (float)($_POST['perdida_rd'] ?? 0);
    $link_red = trim($_POST['link_red'] ?? '');
    $latitud = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
    $longitud = !empty($_POST['longitud']) ? $_POST['longitud'] : null;
    $reportero_id = $_SESSION['usuario']['id_usuario'];

    // foto
    $foto_url = null;
    if (!empty($_FILES['foto']['name'])) {
        @mkdir("uploads", 0775, true);
        $nombreFoto = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/","_", $_FILES['foto']['name']);
        $ruta = "uploads/" . $nombreFoto;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta)) {
            $foto_url = $ruta;
        }
    }

    // Inserta incidencia
    $stmt = $pdo->prepare("INSERT INTO incidencia 
        (fecha_ocurrida, titulo, descripcion, provincia_id, municipio_id, barrio_id, latitud, longitud, muertos, heridos, perdida_rd, link_red, foto_url, reportero_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fecha_ocurrida, $titulo, $descripcion, $provincia_id, $municipio_id, $barrio_id, $latitud, $longitud, $muertos, $heridos, $perdida_rd, $link_red, $foto_url, $reportero_id]);

    $id_incidencia = $pdo->lastInsertId();

    // tipos
    if (!empty($_POST['tipos']) && is_array($_POST['tipos'])) {
        $ins = $pdo->prepare("INSERT INTO incidencia_tipo (incidencia_id, id_tipo) VALUES (?, ?)");
        foreach ($_POST['tipos'] as $id_tipo) $ins->execute([$id_incidencia, (int)$id_tipo]);
    }

    header("Location: ver_incidencia.php?msg=Incidencia registrada");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Incidencia</title>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="topbar">
  <h1>Nueva Incidencia</h1>
  <nav><a href="ver_incidencia.php">Volver</a></nav>
</header>
<main class="container">
<form method="POST" enctype="multipart/form-data" class="card">
  <div class="grid">
    <label>Título
      <input type="text" name="titulo" required>
    </label>
    <label>Fecha ocurrida
      <input type="datetime-local" name="fecha_ocurrida" required>
    </label>
    <label>Provincia
      <select name="provincia_id" id="provincia" required>
        <option value="">Seleccione...</option>
        <?php foreach ($provincias as $p): ?>
          <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Municipio
      <select name="municipio_id" id="municipio" required disabled>
        <option value="">Seleccione...</option>
      </select>
    </label>
    <label>Barrio
      <select name="barrio_id" id="barrio" required disabled>
        <option value="">Seleccione...</option>
      </select>
    </label>
    <label>Muertos
      <input type="number" name="muertos" min="0" value="0">
    </label>
    <label>Heridos
      <input type="number" name="heridos" min="0" value="0">
    </label>
    <label>Pérdida RD$
      <input type="number" step="0.01" name="perdida_rd" min="0" value="0">
    </label>
    <label>Link (red social/noticia)
      <input type="url" name="link_red" placeholder="https://...">
    </label>
    <label>Latitud
      <input type="text" name="latitud" id="latitud" placeholder="18.4861">
    </label>
    <label>Longitud
      <input type="text" name="longitud" id="longitud" placeholder="-69.9312">
    </label>
  </div>

  <label>Descripción
    <textarea name="descripcion" rows="4"></textarea>
  </label>

  <fieldset class="chips">
    <legend>Tipos de incidencia</legend>
    <?php foreach ($tipos as $t): ?>
      <label class="chip">
        <input type="checkbox" name="tipos[]" value="<?= $t['id_tipo'] ?>"> <?= htmlspecialchars($t['nombre']) ?>
      </label>
    <?php endforeach; ?>
  </fieldset>

  <label>Foto
    <input type="file" name="foto" accept="image/*">
  </label>

  <div class="actions">
    <button type="button" id="btnGeo">Usar mi ubicación</button>
    <button type="submit">Guardar</button>
  </div>

  <div id="dupAlert" class="alert" style="display:none;"></div>
</form>
</main>

<script src="js/funciones.js"></script>
</body>
</html>
