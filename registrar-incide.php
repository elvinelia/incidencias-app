<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Conexión a BD (PDO)
require_once "config/db.php";

// Cargar provincias, municipios y barrios
$provincias = $pdo->query("SELECT id_provincia, nombre FROM provincia ORDER BY nombre")->fetchAll();
$municipios = $pdo->query("SELECT id_municipio, nombre, provincia_id FROM municipio ORDER BY nombre")->fetchAll();
$barrios = $pdo->query("SELECT id_barrio, nombre, municipio_id FROM barrio ORDER BY nombre")->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo        = trim($_POST['titulo'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? '');
    $fecha         = trim($_POST['fecha'] ?? '');
    $provincia_id  = intval($_POST['provincia_id'] ?? 0);
    $municipio_id  = intval($_POST['municipio_id'] ?? 0);
    $barrio_nombre = trim($_POST['barrio_nombre'] ?? '');
    $latitud       = $_POST['latitud'] !== '' ? floatval($_POST['latitud']) : null;
    $longitud      = $_POST['longitud'] !== '' ? floatval($_POST['longitud']) : null;
    $muertos       = intval($_POST['muertos'] ?? 0);
    $heridos       = intval($_POST['heridos'] ?? 0);
    $perdida_rd    = floatval($_POST['perdida_rd'] ?? 0);
    $link_red      = trim($_POST['link_red'] ?? '');
    $reportero_id  = intval($_SESSION['id_usuario']);
    $fecha_ocurrida = $fecha ? $fecha . " 00:00:00" : null;

    // Buscar o crear barrio
    $stmt = $pdo->prepare("SELECT id_barrio FROM barrio WHERE nombre = :nombre AND municipio_id = :municipio_id");
    $stmt->execute([':nombre' => $barrio_nombre, ':municipio_id' => $municipio_id]);
    $barrio = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($barrio) {
        $barrio_id = $barrio['id_barrio'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO barrio (nombre, municipio_id) VALUES (:nombre, :municipio_id)");
        $stmt->execute([':nombre' => $barrio_nombre, ':municipio_id' => $municipio_id]);
        $barrio_id = $pdo->lastInsertId();
    }

    // Subida de foto
    $foto_url = null;
    if (!empty($_FILES['foto']['name'])) {
        $directorio = "uploads/";
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $nombreFoto = time() . "_" . basename($_FILES['foto']['name']);
        $rutaDestino = $directorio . $nombreFoto;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $foto_url = $rutaDestino;
        }
    }

    if ($titulo && $descripcion && $tipo && $fecha_ocurrida && $provincia_id && $municipio_id && $barrio_id) {
        // Insertar incidencia
        $sql = "INSERT INTO incidencia 
            (fecha_ocurrida, titulo, descripcion, provincia_id, municipio_id, barrio_id,
             latitud, longitud, muertos, heridos, perdida_rd, link_red, foto_url, reportero_id)
            VALUES 
            (:fecha_ocurrida, :titulo, :descripcion, :provincia_id, :municipio_id, :barrio_id,
             :latitud, :longitud, :muertos, :heridos, :perdida_rd, :link_red, :foto_url, :reportero_id)";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            ':fecha_ocurrida' => $fecha_ocurrida,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':provincia_id' => $provincia_id,
            ':municipio_id' => $municipio_id,
            ':barrio_id' => $barrio_id,
            ':latitud' => $latitud,
            ':longitud' => $longitud,
            ':muertos' => $muertos,
            ':heridos' => $heridos,
            ':perdida_rd' => $perdida_rd,
            ':link_red' => $link_red,
            ':foto_url' => $foto_url,
            ':reportero_id' => $reportero_id
        ]);

        // Insertar relación con tipo de incidencia
        if ($resultado) {
            $incidencia_id = $pdo->lastInsertId();
            $stmtTipo = $pdo->prepare("SELECT id_tipo FROM tipo_incidencia WHERE nombre = :tipo");
            $stmtTipo->execute([':tipo' => $tipo]);
            $tipoRow = $stmtTipo->fetch(PDO::FETCH_ASSOC);
            if ($tipoRow) {
                $stmtRel = $pdo->prepare("INSERT INTO incidencia_tipo (incidencia_id, id_tipo) VALUES (:incidencia_id, :id_tipo)");
                $stmtRel->execute([':incidencia_id' => $incidencia_id, ':id_tipo' => $tipoRow['id_tipo']]);
            }
        }

        $mensaje = $resultado ? "✅ Incidencia registrada correctamente." : "❌ Error al registrar la incidencia.";
    } else {
        $mensaje = "⚠️ Completa todos los campos obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Incidencia</title>
<link rel="stylesheet" href="css/incide.css">
<style>
form { max-width:600px; margin:auto; display:flex; flex-direction:column; gap:10px; }
input, select, textarea, button { padding:8px; font-size:1em; }
button { cursor:pointer; background:#007BFF; color:#fff; border:none; border-radius:4px; }
button:hover { background:#0056b3; }
label { font-weight:bold; }
</style>
</head>
<body>
<header>
    <h1>Registrar Incidencia</h1>
    <nav>
        <a href="ver_incidencia.php">Ver Incidencias</a>
        <a href="reporte.php">Reporte Diario</a>
        <a href="index.php">Panel de Mapa</a>
    </nav>
</header>
<main>
<?php if (!empty($mensaje)): ?>
    <p><?= $mensaje ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Título:</label>
    <input type="text" name="titulo" required>

    <label>Descripción:</label>
    <textarea name="descripcion" required></textarea>

    <label>Tipo de Incidencia:</label>
    <select name="tipo" required>
        <option value="">Seleccione...</option>
        <?php 
        $tipos = $pdo->query("SELECT nombre FROM tipo_incidencia ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
        foreach($tipos as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Fecha de ocurrencia:</label>
    <input type="date" name="fecha" required>

    <label>Provincia:</label>
    <select name="provincia_id" required>
        <option value="">Seleccione...</option>
        <?php foreach($provincias as $prov): ?>
            <option value="<?= $prov['id_provincia'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Municipio:</label>
    <select name="municipio_id" required>
        <option value="">Seleccione...</option>
        <?php foreach($municipios as $mun): ?>
            <option value="<?= $mun['id_municipio'] ?>" data-provincia="<?= $mun['provincia_id'] ?>"><?= htmlspecialchars($mun['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Barrio:</label>
    <input list="barrios" name="barrio_nombre" required placeholder="Escriba o seleccione un barrio">
    <datalist id="barrios">
        <?php foreach($barrios as $bar): ?>
            <option value="<?= htmlspecialchars($bar['nombre']) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <label>Latitud:</label>
    <input type="text" name="latitud" placeholder="Ej: 18.7357">

    <label>Longitud:</label>
    <input type="text" name="longitud" placeholder="-70.1627">

    <label>Muertos:</label>
    <input type="number" name="muertos" min="0" value="0">

    <label>Heridos:</label>
    <input type="number" name="heridos" min="0" value="0">

    <label>Pérdida estimada (RD$):</label>
    <input type="number" name="perdida_rd" step="0.01" min="0" value="0">

    <label>Link a redes sociales:</label>
    <input type="url" name="link_red" placeholder="https://...">

    <label>Foto del hecho:</label>
    <input type="file" name="foto" accept="image/*">

    <label>Reportero:</label>
    <input type="text" value="<?= htmlspecialchars($_SESSION['usuario']) ?>" readonly>

    <button type="submit">Guardar Incidencia</button>
</form>
</main>

<script>
const provinciaSelect = document.querySelector('select[name="provincia_id"]');
const municipioSelect = document.querySelector('select[name="municipio_id"]');

function filtrarMunicipios() {
    const provinciaId = provinciaSelect.value;
    Array.from(municipioSelect.options).forEach(opt => {
        opt.style.display = (opt.dataset.provincia == provinciaId || opt.value === "") ? '' : 'none';
    });
    municipioSelect.value = '';
}
provinciaSelect.addEventListener('change', filtrarMunicipios);
filtrarMunicipios();
</script>
</body>
</html>
<footer>
    <p>&copy; 2025 Sistema de Reporte de Incidencias</p>
</footer>