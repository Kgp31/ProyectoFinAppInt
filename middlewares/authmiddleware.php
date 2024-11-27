<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['username'])) {
        header("Location: /inventariomizaki/public/login.html"); // Ruta ajustada para asegurar que es correcta
        exit();
    }
}
?>
