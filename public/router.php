<?php
session_start();
require_once '../controllers/ProductController.php';
require_once '../middlewares/authmiddleware.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/public/';
$uri = str_replace($base_path, '', $uri);

if ($uri !== 'login' && $uri !== '') {
    checkAuth();
}

switch ($uri) {
    case 'productos':
        $controller = new ProductController();
        $controller->index();
        break;

    case 'productos/crear':
        $controller = new ProductController();
        $controller->create();
        break;

    case 'productos/editar':
        if (isset($_GET['id'])) {
            $controller = new ProductController();
            $controller->edit($_GET['id']);
        } else {
            http_response_code(400);
            echo "ID de producto no proporcionado.";
        }
        break;

    case 'productos/eliminar':
        if (isset($_GET['id'])) {
            $controller = new ProductController();
            $controller->delete($_GET['id']);
        } else {
            http_response_code(400);
            echo "ID de producto no proporcionado.";
        }
        break;

    case 'login':
        include '../app/login.php';
        break;

    case '':
        header("Location: http://localhost:8888/public/login.html");
        break;

    default:
        http_response_code(404);
        echo "Página no encontrada.";
        break;
}
?>