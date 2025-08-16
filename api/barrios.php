<?php
require_once "../config/db.php";
$mid = (int)($_GET['municipio_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id_barrio id, nombre FROM barrio WHERE municipio_id=? ORDER BY nombre");
$stmt->execute([$mid]);
header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());
