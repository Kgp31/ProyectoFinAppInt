<?php
// Incluir el middleware para verificar la autenticación
include '../middlewares/authmiddleware.php';
// Iniciar la sesión
session_start();
// Verificar si el usuario está autenticado
checkAuth();

// Configuración para mostrar todos los errores y advertencias
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Datos de conexión a la base de datos
$host = 'localhost';
$db = 'inventario_db';
$user = 'root';
$pass = 'root';
$port = 8889;

// Crear una nueva conexión a la base de datos
$conn = new mysqli($host, $user, $pass, $db, $port);
// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener término de búsqueda si existe
$buscar = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';
$productos_por_pagina = 10; // Número de productos por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Construir la consulta SQL para obtener los productos
$sql = "SELECT * FROM productos";
if ($buscar) {
    $sql .= " WHERE nombre LIKE '%$buscar%' OR descripcion LIKE '%$buscar%'";
}
$sql .= " ORDER BY nombre ASC LIMIT $productos_por_pagina OFFSET $offset";
// Ejecutar la consulta
$result = $conn->query($sql);

// Construir la consulta SQL para obtener el total de productos
$sql_total = "SELECT COUNT(*) as total FROM productos";
if ($buscar) {
    $sql_total .= " WHERE nombre LIKE '%$buscar%' OR descripcion LIKE '%$buscar%'";
}
// Ejecutar la consulta
$result_total = $conn->query($sql_total);
$total_productos = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Verificar si se está editando un producto
$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $sql_editar = "SELECT * FROM productos WHERE id=$id_editar";
    $result_editar = $conn->query($sql_editar);
    $producto_editar = $result_editar->fetch_assoc();
}

// Verificar si se está eliminando un producto
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    // Eliminar registros del historial asociados al producto
    $sql_eliminar_historial = "DELETE FROM historial_modificaciones WHERE producto_id=$id_eliminar";
    $conn->query($sql_eliminar_historial);
    
    // Eliminar el producto
    $sql_eliminar = "DELETE FROM productos WHERE id=$id_eliminar";
    if ($conn->query($sql_eliminar) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}

// Cerrar la conexión a la base de datos
$conn->close();
?>