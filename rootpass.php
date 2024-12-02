<?php
// Me es facil usar MD5 , asi que agregamos el texto.
$password = "root";

// Generar el hash MD5
$hash = md5($password);

// Mostrar la contraseña "encriptada"
echo "La contraseña en MD5 es: " . $hash;
?>