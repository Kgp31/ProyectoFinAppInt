<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../app/database.php';

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password == $user['password']) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: http://localhost:8888/app/inventario.php");
            exit();
        } else {
            $error_message = "Contraseña incorrecta.";
        }
    } else {
        $error_message = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <h1>Iniciar sesión</h1>

    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="username">Nombre de usuario:</label>
        <input type="text" name="username" required>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Iniciar sesión</button>
    </form>

    <p><a href="register.html">¿No tienes cuenta? Regístrate aquí</a></p>
</body>
</html>