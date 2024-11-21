<?php
// Contraseña que quieres encriptar
$password = "root";

// Generar el hash MD5
$hash = md5($password);

// Mostrar el resultado
echo "La contraseña en MD5 es: " . $hash;
?>
