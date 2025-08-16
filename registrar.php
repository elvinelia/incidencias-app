<?php
require_once "config/db.php";
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $email && $password) {
        // Validar fuerza de contraseña
        if (strlen($password) < 6) {
            $mensaje = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            // Verificar usuario/email
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $mensaje = "El usuario o el email ya están registrados.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("INSERT INTO usuario (nombre, email, password) VALUES (?, ?, ?)");

                if ($stmtInsert->execute([$username, $email, $hash])) {
                    $mensaje = "Registro exitoso. Redirigiendo...";
                    header("Refresh: 2; url=login.php");
                } else {
                    $mensaje = "Error al registrar usuario.";
                }
            }
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
    <title>Registro</title>
    <link rel="stylesheet" href="css/registrar.css">
</head>
<body>
    <h1>Registro</h1>

    <?php if ($mensaje): ?>
        <p style="color:blue;"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Usuario:</label>
        <input type="text" name="username" required>

        <label>Correo:</label>
        <input type="email" name="email" required>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Registrar</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
</body>
</html>
