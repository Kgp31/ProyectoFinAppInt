<?php
// Configuración para mostrar todos los errores y advertencias
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar la sesión
session_start();

// Incluir el archivo de conexión a la base de datos
include '../app/database.php';

// Obtener la conexión a la base de datos
$conn = getDbConnection();

// Verificar si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener el nombre de usuario y la contraseña del formulario
    $username = $_POST['username'];
    // Encriptar la contraseña usando md5 (Se que MD5 no es seguro.)
    $password = md5($_POST['password']);

    // Preparar una declaración SQL para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ?");
    // Vincular el parámetro de nombre de usuario a la declaración preparada
    $stmt->bind_param("s", $username);
    // Ejecutar la declaración
    $stmt->execute();
    // Obtener el resultado de la consulta
    $result = $stmt->get_result();

    // Verificar si se encontró un usuario con el nombre de usuario proporcionado
    if ($result->num_rows > 0) {
        // Obtener los datos del usuario
        $user = $result->fetch_assoc();

        // Verificar si la contraseña encriptada coincide con la almacenada en la base de datos
        if ($password == $user['password']) {
            // Guardar el nombre de usuario y el rol en la sesión
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirigir al usuario según su rol
            if ($user['role'] == 'admin') {
                header("Location: http://localhost:8888/app/inventario.php");
            } elseif ($user['role'] == 'read_only') {
                header("Location: http://localhost:8888/app/usuarios.php");
            }
            exit();
        } else {
            // Redirigir al formulario de inicio de sesión con un error si la contraseña no coincide
            header("Location: http://localhost:8888/public/login.html?error=1");
            exit();
        }
    } else {
        // Redirigir al formulario de inicio de sesión con un error si no se encuentra el usuario
        header("Location: http://localhost:8888/public/login.html?error=1");
        exit();
    }
}
?>