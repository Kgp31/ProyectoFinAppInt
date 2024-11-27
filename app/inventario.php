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
    <link rel="stylesheet" href="styles.css">
    <script>
        function eliminarProducto(id) {
            if (confirm("¿Estás seguro de eliminar este producto?")) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "inventario.php?eliminar=" + id, true);
                xhr.onload = function() {
                    var response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        document.getElementById("producto-" + id).remove();
                    } else {
                        alert("Error al eliminar el producto.");
                    }
                };
                xhr.send();
            }
        }
    </script>
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
                        <label for="cantidad_minima">Cantidad Mínima</label>
                        <input type="number" id="cantidad_minima" name="cantidad_minima" value="<?php echo $producto_editar['cantidad_minima'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="cantidad_maxima">Cantidad Máxima</label>
                        <input type="number" id="cantidad_maxima" name="cantidad_maxima" value="<?php echo $producto_editar['cantidad_maxima'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="precio">Precio</label>
                        <input type="number" step="0.01" id="precio" name="precio" value="<?php echo $producto_editar['precio'] ?? ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="imagen">Subir Imagen</label>
                        <input type="file" id="imagen" name="imagen">
                    </div>
                    <button type="submit" name="<?php echo $producto_editar ? 'editar_producto' : 'agregar_producto'; ?>">
                        <?php echo $producto_editar ? 'Guardar Cambios' : 'Agregar Producto'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Listado de Productos -->
        <div class="productos-container">
            <h3>Lista de Productos</h3>
            <div class="productos-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="producto-card" id="producto-<?php echo $row['id']; ?>">
                        <div class="producto-img-container">
                            <?php if ($row['imagen']): ?>
                                <img src="<?php echo $row['imagen']; ?>" alt="Imagen del Producto" class="producto-img">
                            <?php endif; ?>
                        </div>
                        <h4><?php echo $row['nombre']; ?></h4>
                        <p><?php echo $row['descripcion']; ?></p>
                        <div class="producto-info">
                            <span>Cantidad: <?php echo $row['cantidad']; ?></span>
                            <span>Precio: $<?php echo $row['precio']; ?></span>
                        </div>
                        <div class="producto-actions">
                            <a href="inventario.php?editar=<?php echo $row['id']; ?>" class="edit-button">Editar</a>
                            <a href="javascript:void(0);" onclick="eliminarProducto(<?php echo $row['id']; ?>)" class="delete-button">Eliminar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
