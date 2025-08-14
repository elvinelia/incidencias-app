<?php
// config/db.php
// Configuración de la conexión a la base de datos
$host = "localhost";
$dbname = "supernatural_db";
$username = "root";   // Cambia si tu usuario de MySQL es diferente
$password = "rubicon30$";       // Cambia si tienes contraseña

try {
    // DSN para PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    // Crear conexión PDO
    $pdo = new PDO($dsn, $username, $password);

    // Configuración de PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
