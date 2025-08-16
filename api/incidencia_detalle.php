<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
$inc = $pdo->prepare("SELECT i.*, p.nombre prov, m.nombre mun, b.nombre bar
 FROM incidencia i
 JOIN provincia p ON p.id_provincia=i.provincia_id
 JOIN municipio m ON m.id_municipio=i.municipio_id
 JOIN barrio b ON b.id_barrio=i.barrio_id
 WHERE id_incidencia=?");
$inc->execute([$id]);
$incidencia = $inc->fetch();
if(!$incidencia){ http_response_code(404); die("No encontrada"); }

$tipos = $pdo->prepare("SELECT t.nombre FROM incidencia_tipo it JOIN tipo_incidencia t ON t.id_tipo=it.id_tipo WHERE it.incidencia_id=?");
$tipos->execute([$id]);
$incidencia['tipos'] = array_column($tipos->fetchAll(),'nombre');

$com = $pdo->prepare("SELECT c.texto, c.creado_en, u.nombre FROM comentario c JOIN usuario u ON u.id_usuario=c.autor_id WHERE incidencia_id=? ORDER BY c.creado_en DESC");
$com->execute([$id]);
$comentarios = $com->fetchAll();

header('Content-Type: application/json');
echo json_encode([
  "incidencia"=>$incidencia,
  "comentarios"=>$comentarios,
  "usuario"=> $_SESSION['usuario'] ?? null
]);
