<?php
// app/database.php

// Configuración de la base de datos
$host = 'localhost';    // Dirección del servidor MySQL
$db = 'inventario_db';  // Nombre de la base de datos
$user = 'root';        // Usuario de la base de datos
$pass = 'root';         // Contraseña del usuario
$port = 8889;           // Puerto MySQL (por defecto 3306, MAMP usa 8889)

// Función para obtener la conexión
function getDbConnection() {
    global $host, $db, $user, $pass, $port;

    // Crear conexión a la base de datos
    $conn = new mysqli($host, $user, $pass, $db, $port);

    // Comprobar la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    return $conn; // Retorna la conexión para usarla en otros archivos
}
?>
