<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();

header('Content-Type: text/csv; charset=utf-8');
$fname = 'export_'.date('Ymd_His').'.csv';
header('Content-Disposition: attachment; filename='.$fname);

$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$params=[":d"=>$desde,":h"=>$hasta];

if (isset($_GET['reporte'])) {
  $sql = "SELECT DATE(fecha_ocurrida) fecha, COUNT(*) incidencias, SUM(muertos) muertos, SUM(heridos) heridos, SUM(perdida_rd) perdidas
          FROM incidencia WHERE DATE(fecha_ocurrida) BETWEEN :d AND :h GROUP BY DATE(fecha_ocurrida) ORDER BY fecha";
  $stmt=$pdo->prepare($sql); $stmt->execute($params);
  $out = fopen('php://output','w');
  fputcsv($out, ['fecha','incidencias','muertos','heridos','perdidas']);
  while($r=$stmt->fetch()) fputcsv($out, $r);
  fclose($out); exit;
}

// incidencias detalle
$where = ["DATE(i.fecha_ocurrida) BETWEEN :d AND :h"];
$params = [":d"=>$desde, ":h"=>$hasta];
if (!empty($_GET['provincia_id'])) { $where[]="i.provincia_id=:p"; $params[":p"]=(int)$_GET['provincia_id']; }
if (isset($_GET['validado']) && $_GET['validado']!=='') { $where[]="i.validado=:v"; $params[":v"] = ($_GET['validado']==='1'); }
if (!empty($_GET['q'])) { $where[]="(i.titulo LIKE :q OR i.descripcion LIKE :q)"; $params[":q"]='%'.$_GET['q'].'%'; }

$sql = "SELECT i.id_incidencia, i.fecha_ocurrida, i.titulo, i.descripcion, p.nombre provincia, m.nombre municipio, b.nombre barrio, i.latitud, i.longitud, i.muertos, i.heridos, i.perdida_rd, i.validado
        FROM incidencia i
        JOIN provincia p ON p.id_provincia=i.provincia_id
        JOIN municipio m ON m.id_municipio=i.municipio_id
        JOIN barrio b ON b.id_barrio=i.barrio_id
        WHERE ".implode(" AND ",$where)."
        ORDER BY i.fecha_ocurrida DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params);

$out = fopen('php://output','w');
fputcsv($out, ['id','fecha_ocurrida','titulo','descripcion','provincia','municipio','barrio','latitud','longitud','muertos','heridos','perdida_rd','validado']);
while($r=$stmt->fetch()) fputcsv($out, $r);
fclose($out);
