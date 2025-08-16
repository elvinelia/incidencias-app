<?php
require '../config/db.php';
header('Content-Type: application/json');

$provincia = $_GET['provincia_id'] ?? null;
$tipo = $_GET['tipo_id'] ?? null;
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;
$q = $_GET['q'] ?? null;

$sql = "
    SELECT i.id, i.titulo, i.fecha, p.nombre AS provincia, i.muertos, i.heridos
    FROM incidencias i
    LEFT JOIN provincias p ON i.provincia_id = p.id
    WHERE i.estado = 'publicada'
";
$params = [];

if ($provincia) { $sql .= " AND i.provincia_id = ?"; $params[] = $provincia; }
if ($desde) { $sql .= " AND i.fecha >= ?"; $params[] = $desde; }
if ($hasta) { $sql .= " AND i.fecha <= ?"; $params[] = $hasta; }
if ($q) { $sql .= " AND i.titulo LIKE ?"; $params[] = "%$q%"; }
if ($tipo) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM incidencia_tipo it WHERE it.incidencia_id = i.id AND it.tipo_id = ?
    )";
    $params[] = $tipo;
}

$sql .= " ORDER BY i.fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
