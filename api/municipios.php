<?php
require_once "../config/db.php";
$pid = (int)($_GET['provincia_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id_municipio id, nombre FROM municipio WHERE provincia_id=? ORDER BY nombre");
$stmt->execute([$pid]);
header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());
