<?php
require_once "config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Incidencias</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
<header class="topbar">
  <h1>Incidencias</h1>
  <nav>
    <a href="ver_incidencia.php">Incidencias</a>
    <a href="reporte.php">Reportes</a>
    <a href="registrar-incide.php">Registrar incidencias</a>

    <?php if(isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])): ?>
        <?php if(isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'validador'): ?>
            <a href="super.php">Validador</a>
            <a href="provincias.php">Catálogos</a>
        <?php endif; ?>
        <span class="user">👤 <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        <a href="logout.php">Salir</a>
    <?php else: ?>
        <a href="login.php">Iniciar sesion</a>
        <a href="registrar.php">Registrate</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
  <p>Bienvenido a Reporte de Incidencias</p>
</main>
</body>
</html>
