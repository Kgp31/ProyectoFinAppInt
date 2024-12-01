<?php
// Incluir el middleware para verificar la autenticación
include '../middlewares/authmiddleware.php';
session_start();
checkAuth();

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

// Actualizar cantidad de producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_cantidad'])) {
    $id = (int)$_POST['id'];
    $cantidad = (float)$_POST['cantidad'];

    $stmt = $conn->prepare("UPDATE productos SET cantidad=? WHERE id=?");
    $stmt->bind_param("di", $cantidad, $id);

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit();
    } else {
        $mensaje = "Error: " . $conn->error;
    }
}

// Obtener término de búsqueda si existe
$buscar = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';
$productos_por_pagina = 16;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

$sql = "SELECT * FROM productos";
if ($buscar) {
    $sql .= " WHERE nombre LIKE '%$buscar%' OR descripcion LIKE '%$buscar%'";
}
$sql .= " ORDER BY nombre ASC LIMIT $productos_por_pagina OFFSET $offset";
$result = $conn->query($sql);

$sql_total = "SELECT COUNT(*) as total FROM productos";
if ($buscar) {
    $sql_total .= " WHERE nombre LIKE '%$buscar%' OR descripcion LIKE '%$buscar%'";
}
$result_total = $conn->query($sql_total);
$total_productos = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Usuarios</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            color: #333;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header-container img {
            height: 50px;
        }
        .titulo-inventario {
            font-size: 24px;
            font-weight: bold;
        }
        .inventario-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .form-list-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .productos-container {
            width: 100%;
        }
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .producto-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s;
        }
        .producto-card:hover {
            transform: scale(1.05);
        }
        .producto-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            font-weight: bold;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 15px 20px;
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
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .pagination a:hover {
            background-color: #0056b3;
        }
        .pagination .active {
            background-color: #4CAF50;
        }
        .search-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .search-container input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .search-container button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #f44336;
            margin-top: 20px;
            font-size: 16px;
            text-align: center;
        }
        .producto-card[data-cantidad="low"] {
            background-color: #ffe0e0;
        }
        .producto-card[data-cantidad="medium"] {
            background-color: #fff5cc;
        }
        .producto-card[data-cantidad="high"] {
            background-color: #e0f7e0;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <img src="assets/images/Mizaki_Ajuste_Logotipo_Mesa_de_trabajo_1.png" alt="Logotipo de Mizaki Campestre" class="logotipo">
        <h2 class="titulo-inventario">Inventario Mizaki Campestre (Usuarios)</h2>
        <a href="logout.php" class="logout-button">Cerrar sesión</a>
    </div>

    <div class="inventario-container">
        <div class="form-list-container">
            <div class="search-container">
                <form action="usuarios.php" method="GET">
                    <input type="text" name="buscar" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($buscar); ?>">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <!-- Lista de productos -->
            <div class="productos-container">
                <div class="productos-grid">
                    <?php while ($row = $result->fetch_assoc()): 
                        $cantidad = $row['cantidad'];
                        $cantidad_minima = $row['cantidad_minima'];
                        $cantidad_maxima = $row['cantidad_maxima'];
                        $color = '';

                        if ($cantidad <= $cantidad_minima) {
                            $color = 'low';
                        } elseif ($cantidad >= $cantidad_maxima) {
                            $color = 'high';
                        } else {
                            $color = 'medium';
                        }
                    ?>
                        <div class="producto-card" data-cantidad="<?php echo $color; ?>">
                            <img src="<?php echo $row['imagen'] ? $row['imagen'] : 'https://via.placeholder.com/150'; ?>" alt="Imagen de producto" class="producto-img">
                            <h3><?php echo $row['nombre']; ?></h3>
                            <p><?php echo $row['descripcion']; ?></p>
                            <p><strong>Precio:</strong> $<?php echo number_format($row['precio'], 2); ?></p>
                            <p><strong>Cantidad:</strong> <?php echo $row['cantidad']; ?></p>
                            <p><strong>Cantidad mínima:</strong> <?php echo $row['cantidad_minima']; ?></p>
                            <p><strong>Cantidad máxima:</strong> <?php echo $row['cantidad_maxima']; ?></p>
                            <form action="usuarios.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <div class="input-group">
                                    <label for="cantidad">Actualizar Cantidad</label>
                                    <input type="number" step="0.01" name="cantidad" value="<?php echo $row['cantidad']; ?>" required>
                                </div>
                                <button type="submit" name="actualizar_cantidad">Actualizar</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Paginación -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="usuarios.php?pagina=<?php echo $i; ?>" class="<?php echo $i == $pagina_actual ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <p class="error-message"><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <script>
        function openModal() {
            document.getElementById('modalForm').style.display = 'block';
            document.getElementById('productForm').reset();
            document.getElementById('submitButton').name = 'agregar_producto';
            document.getElementById('submitButton').textContent = 'Agregar Producto';
        }

        function closeModal() {
            document.getElementById('modalForm').style.display = 'none';
        }

        function editProduct(id, nombre, descripcion, cantidad, precio, cantidad_minima, cantidad_maxima) {
            document.getElementById('modalForm').style.display = 'block';
            document.getElementById('productId').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('cantidad').value = cantidad;
            document.getElementById('precio').value = precio;
            document.getElementById('cantidad_minima').value = cantidad_minima;
            document.getElementById('cantidad_maxima').value = cantidad_maxima;
            document.getElementById('submitButton').name = 'editar_producto';
            document.getElementById('submitButton').textContent = 'Actualizar Producto';
        }

        function eliminarProducto(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
                fetch('usuarios.php?eliminar=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Producto eliminado exitosamente.");
                            location.reload();
                        } else {
                            alert("Error al eliminar producto.");
                        }
                    });
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalForm')) {
                document.getElementById('modalForm').style.display = "none";
            }
        }
    </script>
</body>
</html>