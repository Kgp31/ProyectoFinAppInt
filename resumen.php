<?php
// Datos de conexión a la base de datos
$host = 'localhost';
$db = 'inventario_db';
$user = 'root';
$pass = 'root';
$port = 8889; // Puerto de MySQL en MAMP

// Crear una nueva conexión a la base de datos
$conn = new mysqli($host, $user, $pass, $db, $port);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta SQL para seleccionar los datos de los productos
$sql = "SELECT id, nombre, descripcion, cantidad, precio, imagen, cantidad_minima, cantidad_maxima FROM productos";
$result = $conn->query($sql);

// Verificar si hay resultados
if ($result->num_rows > 0) {
    // Definir estilos CSS para las clases de cantidad
    echo "<style>
            .low { background-color: #ffcccc; } /* Rojo claro */
            .medium { background-color: #ffebcc; } /* Naranja claro */
            .high { background-color: #ccffcc; } /* Verde claro */
          </style>";
    // Crear la tabla HTML para mostrar los productos
    echo "<table border='1'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Imagen</th>
                    <th>Cantidad Mínima</th>
                    <th>Cantidad Máxima</th>
                </tr>
            </thead>
            <tbody>";
    // Recorrer los resultados y crear filas de la tabla
    while ($row = $result->fetch_assoc()) {
        $cantidad = $row['cantidad'];
        $min = $row['cantidad_minima'];
        $max = $row['cantidad_maxima'];
        $class = '';

        // Asignar clase CSS según la cantidad del producto
        if ($cantidad <= $min) {
            $class = 'low';
        } elseif ($cantidad >= $max) {
            $class = 'high';
        } else {
            $class = 'medium';
        }

        // Crear una fila de la tabla con los datos del producto
        echo "<tr class='{$class}'>
                <td>{$row['id']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['descripcion']}</td>
                <td>{$row['cantidad']}</td>
                <td>{$row['precio']}</td>
                <td>{$row['imagen']}</td>
                <td>{$row['cantidad_minima']}</td>
                <td>{$row['cantidad_maxima']}</td>
              </tr>";
    }
    echo "  </tbody>
        </table>";
} else {
    // Mostrar mensaje si no hay datos disponibles
    echo "No hay datos disponibles.";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>