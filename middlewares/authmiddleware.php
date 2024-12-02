<?php
// Función para verificar la autenticación del usuario
function checkAuth() {
    // Verificar si la sesión no tiene un nombre de usuario establecido
    if (!isset($_SESSION['username'])) {
        // Redirigir al usuario a la página de inicio de sesión si no está autenticado
        header("Location: http://localhost:8888/public/login.html");
        // Terminar la ejecución del script para asegurar que no se ejecute más código
        exit();
    }
}
?>