<?php
// Iniciar la sesión
session_start();

// Incluir el controlador de productos y el middleware de autenticación
require_once '../controllers/ProductController.php';
require_once '../middlewares/authmiddleware.php';

// Obtener la URI de la solicitud
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Definir la ruta base
$base_path = '/public/';
// Eliminar la ruta base de la URI
$uri = str_replace($base_path, '', $uri);

// Verificar la autenticación para todas las rutas excepto 'login' y la raíz
if ($uri !== 'login' && $uri !== '') {
    checkAuth();
}

// Manejar las diferentes rutas usando una estructura switch
switch ($uri) {
    case 'login':
        // Incluir el archivo de login
        include '../app/login.php';
        break;

    case '':
        // Redirigir a la página de inicio de sesión si la URI está vacía
        header("Location: http://localhost:8888/public/login.html");
        break;

    default:
        // Enviar un código de respuesta 404 si la ruta no se encuentra
        http_response_code(404);
        echo "Página no encontrada.";
        break;
}
?>