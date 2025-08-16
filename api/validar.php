<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol']!=='validador') { http_response_code(403); die("No autorizado"); }

$id = (int)($_POST['id'] ?? 0);
if(!$id) { http_response_code(400); die("ID requerido"); }
$pdo->prepare("UPDATE incidencia SET validado=1 WHERE id_incidencia=?")->execute([$id]);
echo "OK";
