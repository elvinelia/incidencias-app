<?php
session_start();
require_once "config/db.php"; // Conexión centralizada

if (isset($_SESSION['usuario'])) {
    header("Location: panel.php");
    exit();
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, password FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Guardamos usuario como array completo en sesión
            $_SESSION['usuario'] = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre']
            ];

            // Redirigir a panel general
            header("Location: panel.php");
            exit();
        } else {
            $mensaje = "Usuario o contraseña incorrectos.";
        }
    } else {
        $mensaje = "Completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <h1>Login</h1>

    <?php if ($mensaje): ?>
        <p style="color:red;"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Correo:</label>
        <input type="email" name="email" required autocomplete="username">

        <label>Contraseña:</label>
        <input type="password" name="password" required autocomplete="current-password">

        <button type="submit">Iniciar sesión</button>
    </form>

    <p>¿No tienes cuenta? <a href="registrar.php">Regístrate aquí</a></p>
</body>
</html>
