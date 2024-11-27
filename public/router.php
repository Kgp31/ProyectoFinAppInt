<?php
session_start();
require_once '../controllers/ProductController.php';  // Incluir el controlador de productos

// Obtener la URI solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/inventariomizaki/public/';
$uri = str_replace($base_path, '', $uri);

// Verificar la ruta y redirigir a login.php si es necesario
require_once '../middlewares/authmiddleware.php'; 
checkAuth();

// Rutas del controlador de productos
switch ($uri) {
    case 'productos':  // Muestra la lista de productos
        $controller = new ProductController();
        $controller->index();  // Llama a la función index del controlador
        break;

    case 'productos/crear':  // Formulario de creación de producto
        $controller = new ProductController();
        $controller->create();  // Llama a la función create del controlador
        break;

    case 'productos/editar':  // Formulario de edición de producto
        if (isset($_GET['id'])) {
            $controller = new ProductController();
            $controller->edit($_GET['id']);  // Llama a la función edit pasando el ID
        } else {
            http_response_code(400);  // Error si no se pasa el ID
            echo "ID de producto no proporcionado.";
        }
        break;

    case 'productos/eliminar':  // Eliminar producto
        if (isset($_GET['id'])) {
            $controller = new ProductController();
            $controller->delete($_GET['id']);  // Llama a la función delete pasando el ID
        } else {
            http_response_code(400);  // Error si no se pasa el ID
            echo "ID de producto no proporcionado.";
        }
        break;

    case 'login.html':  // Ruta para login
        include '../app/login.php';  // Incluye la página de login
        break;

    default:
        http_response_code(404);  // Página no encontrada
        echo "Página no encontrada.";
        break;
}
?>
