<?php
require_once "../config/db.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario'])) { http_response_code(401); die("No auth"); }

$texto = trim($_POST['texto'] ?? '');
$inc = (int)($_POST['incidencia_id'] ?? 0);
if ($texto==='' || !$inc) { http_response_code(400); die("Datos inválidos"); }

$stmt = $pdo->prepare("INSERT INTO comentario (texto, autor_id, incidencia_id) VALUES (?,?,?)");
$stmt->execute([$texto, $_SESSION['usuario']['id_usuario'], $inc]);
echo "OK";
