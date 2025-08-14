<?php
session_start();

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['usuario'])) {
    header("Location: panel.php");
    exit();
}

$mensaje = "";

// Si envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        // Conexión a la base de datos
        $conexion = new mysqli("localhost", "root", "rubicon30$", "supernatural_db");

        if ($conexion->connect_error) {
            die("Error de conexión: " . $conexion->connect_error);
        }

        // Prevenir inyección SQL
        $stmt = $conexion->prepare("SELECT id_usuario, nombre, password, rol FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_usuario, $nombre, $hashGuardado, $rol);
            $stmt->fetch();

            // Verificar contraseña
            if (password_verify($password, $hashGuardado)) {
                $_SESSION['id_usuario'] = $id_usuario;
                $_SESSION['usuario'] = $nombre;
                $_SESSION['rol'] = $rol;

                // Redirigir según el rol
                if ($rol === 'validador') {
                    header("Location: /super/dashboard.php");
                } else {
                    header("Location: panel.php");
                }
                exit();
            } else {
                $mensaje = "Usuario o contraseña incorrectos.";
            }
        } else {
            $mensaje = "Usuario no encontrado.";
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
  <title>Login</title>
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>
  <h1>Login</h1>
  
  <?php if (!empty($mensaje)): ?>
    <p style="color:red;"><?php echo $mensaje; ?></p>
  <?php endif; ?>

  <form method="POST" action="">
    <label for="email">Correo:</label>
    <input type="email" id="email" name="email" required autocomplete="username" 
           class="<?php echo (!empty($mensaje) ? 'error' : ''); ?>" />

    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" required autocomplete="current-password" 
           class="<?php echo (!empty($mensaje) ? 'error' : ''); ?>" />

    <button type="submit">Login</button>
  </form>

  <p>¿No tienes cuenta? <a href="registrar.php">Regístrate aquí</a></p>
</body>
</html>
