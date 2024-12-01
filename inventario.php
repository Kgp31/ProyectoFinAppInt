<?php
// Incluir el middleware para verificar la autenticación
include '../middlewares/authmiddleware.php';

// Iniciar la sesión para manejar las variables de sesión
session_start();

// Verificar si el usuario está autenticado
checkAuth(); // Llamar a la función de verificación de autenticación

// Mostrar errores en pantalla para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Datos de conexión a la base de datos
$host = 'localhost';
$db = 'inventario_db';
$user = 'root';
$pass = 'root';
$port = 8889;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Agregar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_producto'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $cantidad = (int)$_POST['cantidad'];
    $precio = (float)$_POST['precio'];
    $cantidad_minima = (int)$_POST['cantidad_minima'];
    $cantidad_maxima = (int)$_POST['cantidad_maxima'];

    $imagen = null;
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = 'imagenes/' . basename($_FILES['imagen']['name']);
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen);
    }

    if ($cantidad < $cantidad_minima || $cantidad > $cantidad_maxima) {
        $mensaje = "La cantidad debe estar entre $cantidad_minima y $cantidad_maxima.";
    } else {
        $sql = "INSERT INTO productos (nombre, descripcion, cantidad, precio, cantidad_minima, cantidad_maxima, imagen) 
                VALUES ('$nombre', '$descripcion', $cantidad, $precio, $cantidad_minima, $cantidad_maxima, '$imagen')";
        if ($conn->query($sql) === TRUE) {
            header("Location: inventario.php");
            exit();
        } else {
            $mensaje = "Error: " . $conn->error;
        }
    }
}

// Editar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_producto'])) {
    $id = (int)$_POST['id'];
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $cantidad = (int)$_POST['cantidad'];
    $precio = (float)$_POST['precio'];
    $cantidad_minima = (int)$_POST['cantidad_minima'];
    $cantidad_maxima = (int)$_POST['cantidad_maxima'];

    if ($cantidad < $cantidad_minima || $cantidad > $cantidad_maxima) {
        $mensaje = "La cantidad debe estar entre $cantidad_minima y $cantidad_maxima.";
    } else {
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = 'imagenes/' . basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen);
        }

        $sql = "UPDATE productos SET 
                    nombre='$nombre', 
                    descripcion='$descripcion', 
                    cantidad=$cantidad, 
                    precio=$precio,
                    cantidad_minima=$cantidad_minima,
                    cantidad_maxima=$cantidad_maxima";
        if ($imagen) {
            $sql .= ", imagen='$imagen'";
        }
        $sql .= " WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $mensaje = "Producto actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar: " . $conn->error;
        }
    }
}

// Obtener término de búsqueda si existe
$buscar = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';

// Modificar la consulta SQL para incluir el filtro
$sql = "SELECT * FROM productos";
if ($buscar) {
    $sql .= " WHERE nombre LIKE '%$buscar%' OR descripcion LIKE '%$buscar%'";
}
$result = $conn->query($sql);

// Obtener producto para editar
$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $sql_editar = "SELECT * FROM productos WHERE id=$id_editar";
    $result_editar = $conn->query($sql_editar);
    $producto_editar = $result_editar->fetch_assoc();
}

// Eliminar producto (con AJAX)
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $sql_eliminar = "DELETE FROM productos WHERE id=$id_eliminar";
    if ($conn->query($sql_eliminar) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: space-between;
        }
        .inventario-container {
            display: flex;
            width: 100%;
        }
        .form-list-container {
            display: flex;
            flex: 1;
            padding: 20px;
            flex-direction: column;
        }
        .form-container {
            flex: 1;
            margin-right: 20px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
        }
        .productos-container {
            flex: 2;
            padding: 20px;
        }
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .producto-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
        }
        .producto-img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            font-weight: bold;
        }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .input-group textarea {
            resize: vertical;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .logout-button {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout-button:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="inventario-container">
        <div class="form-list-container">
            <div class="form-container">
                <div class="header-container">
                    <h2 class="titulo-inventario">Inventario Mizaki Campestre (prototipo)</h2>
                    <img src="https://i.postimg.cc/4dzrqM1Z/logo-mizaki-reducido.jpg" alt="Logotipo de Mizaki Campestre" class="logotipo">
                    <a href="logout.php" class="logout-button">Cerrar sesión</a>
                </div>

                <!-- Formulario de Búsqueda -->
                <div class="search-container">
                    <form action="inventario.php" method="GET">
                        <input type="text" name="buscar" placeholder="Buscar por nombre o descripción" value="<?php echo isset($_GET['buscar']) ? $_GET['buscar'] : ''; ?>">
                        <button type="submit">Buscar</button>
                    </form>
                </div>

                <?php if (isset($mensaje)) { echo "<p class='mensaje'>$mensaje</p>"; } ?>

                <form action="inventario.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $producto_editar['id'] ?? ''; ?>">
                    <div class="input-group">
                        <label for="nombre">Nombre del Producto</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo $producto_editar['nombre'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" required><?php echo $producto_editar['descripcion'] ?? ''; ?></textarea>
                    </div>
                    <div class="input-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" value="<?php echo $producto_editar['cantidad'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="precio">Precio</label>
                        <input type="number" id="precio" name="precio" value="<?php echo $producto_editar['precio'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="cantidad_minima">Cantidad Mínima</label>
                        <input type="number" id="cantidad_minima" name="cantidad_minima" value="<?php echo $producto_editar['cantidad_minima'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="cantidad_maxima">Cantidad Máxima</label>
                        <input type="number" id="cantidad_maxima" name="cantidad_maxima" value="<?php echo $producto_editar['cantidad_maxima'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="imagen">Imagen</label>
                        <input type="file" id="imagen" name="imagen">
                    </div>
                    <button type="submit" name="agregar_producto">Agregar Producto</button>
                </form>
            </div>
        </div>

        <div class="productos-container">
            <div class="productos-grid">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="producto-card">
                        <img src="<?php echo $row['imagen']; ?>" alt="Imagen del producto" class="producto-img">
                        <h3><?php echo $row['nombre']; ?></h3>
                        <p><?php echo $row['descripcion']; ?></p>
                        <p>Cantidad: <?php echo $row['cantidad']; ?></p>
                        <p>Precio: $<?php echo $row['precio']; ?></p>
                        <p>Min: <?php echo $row['cantidad_minima']; ?> | Max: <?php echo $row['cantidad_maxima']; ?></p>
                        <a href="inventario.php?editar=<?php echo $row['id']; ?>">Editar</a>
                        <a href="#" onclick="eliminarProducto(<?php echo $row['id']; ?>)">Eliminar</a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        function eliminarProducto(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
                fetch("inventario.php?eliminar=" + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Error al eliminar el producto.");
                        }
                    });
            }
        }
    </script>
</body>
</html>
