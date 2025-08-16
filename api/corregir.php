<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario'])) { http_response_code(401); die("No auth"); }

$campo = trim($_POST['campo'] ?? '');
$valor = trim($_POST['valor_nuevo'] ?? '');
$inc = (int)($_POST['incidencia_id'] ?? 0);
if ($campo==='' || $valor==='' || !$inc) { http_response_code(400); die("Datos inválidos"); }

$stmt = $pdo->prepare("INSERT INTO correccion (campo, valor_nuevo, autor_id, incidencia_id) VALUES (?,?,?,?)");
$stmt->execute([$campo, $valor, $_SESSION['usuario']['id_usuario'], $inc]);
echo "OK";
