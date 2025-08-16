<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol']!=='validador') { http_response_code(403); die("No autorizado"); }

$principal = (int)($_POST['principal_id'] ?? 0);
$ids = trim($_POST['ids'] ?? '');
if(!$principal || $ids===''){ http_response_code(400); die("Datos faltantes"); }
$otros = array_filter(array_map('intval', explode(',',$ids)));

$pdo->beginTransaction();
try {
  // Agregar métricas al principal
  $sum = $pdo->prepare("SELECT SUM(muertos) sm, SUM(heridos) sh, MAX(perdida_rd) mp FROM incidencia WHERE id_incidencia IN (".implode(',', array_fill(0,count($otros),'?')).")");
  $sum->execute($otros);
  $s = $sum->fetch();

  $pdo->prepare("UPDATE incidencia SET 
    muertos = COALESCE(muertos,0) + ?, 
    heridos = COALESCE(heridos,0) + ?, 
    perdida_rd = GREATEST(COALESCE(perdida_rd,0), ?) 
    WHERE id_incidencia=?")->execute([(int)$s['sm'],(int)$s['sh'],(float)$s['mp'],$principal]);

  // mover comentarios al principal
  if ($otros) {
    $pdo->prepare("UPDATE comentario SET incidencia_id=? WHERE incidencia_id IN (".implode(',', array_fill(0,count($otros),'?')).")")
        ->execute(array_merge([$principal], $otros));
    // borrar duplicados
    $pdo->prepare("DELETE FROM incidencia WHERE id_incidencia IN (".implode(',', array_fill(0,count($otros),'?')).")")
        ->execute($otros);
  }

  $pdo->commit();
  echo "OK";
} catch(Exception $e){
  $pdo->rollBack();
  http_response_code(500);
  echo "Error: ".$e->getMessage();
}
