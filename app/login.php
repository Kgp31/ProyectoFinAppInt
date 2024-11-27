<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Incluir el archivo de conexión a la base de datos
include '../app/database.php'; // Incluir el archivo de conexión

// Obtener la conexión a la base de datos
$conn = getDbConnection(); // Obtiene la conexión reutilizable

// Comprobamos si es un envío de formulario POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Encriptar la contraseña

    // Comprobamos en la base de datos si existe el usuario
    $sql = "SELECT * FROM usuarios WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Si el usuario existe, iniciamos sesión
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirigir al inventario.php
        header("Location: ../app/inventario.php");
        exit();
    } else {
        // Si las credenciales son incorrectas, redirigir con un error
        header("Location: ../public/login.html?error=1");
        exit();
    }
}
?>
