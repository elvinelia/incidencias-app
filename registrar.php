<?php
// registro.php
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $email && $password) {
        // Conectar a la base de datos
        $conexion = new mysqli("localhost", "root", "rubicon30$", "supernatural_db");

        if ($conexion->connect_error) {
            die("Error de conexión: " . $conexion->connect_error);
        }

        // Verificar si ya existe el usuario o el email
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensaje = "El usuario o el email ya están registrados.";
        } else {
            // Encriptar contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar en la base de datos
            $stmtInsert = $conexion->prepare("INSERT INTO usuario (nombre, email, password) VALUES (?, ?, ?)");
            $stmtInsert->bind_param("sss", $username, $email, $hash);

            if ($stmtInsert->execute()) {
                $mensaje = "Registro exitoso. Ahora puedes iniciar sesión.";
                header("Refresh: 2; url=login.php");
            } else {
                $mensaje = "Error al registrar usuario.";
            }
            $stmtInsert->close();
        }

        $stmt->close();
        $conexion->close();
    } else {
        $mensaje = "Completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/registrar.css" />
</head>
<body>
    <h1>Registro</h1>

    <?php if (!empty($mensaje)): ?>
        <p style="color:blue;"><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Usuario:</label>
        <input type="text" id="username" name="username" required /><br /><br />

        <label for="email">Correo:</label>
        <input type="email" id="email" name="email" required /><br /><br />

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required /><br /><br />

        <button type="submit">Registrar</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
</body>
</html>