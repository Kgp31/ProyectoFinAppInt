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

// Agregar o editar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar_producto']) || isset($_POST['editar_producto'])) {
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $descripcion = $conn->real_escape_string($_POST['descripcion']);
        $cantidad = (float)$_POST['cantidad'];
        $precio = (float)$_POST['precio'];
        $cantidad_minima = (float)$_POST['cantidad_minima'];
        $cantidad_maxima = (float)$_POST['cantidad_maxima'];
        $imagen = null;

        if (!empty($_FILES['imagen']['name'])) {
            $imagen = 'imagenes/' . basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen);
        }

        if ($cantidad > $cantidad_maxima) {
            $mensaje = "La cantidad debe estar entre $cantidad_minima y $cantidad_maxima.";
        } else {
            if (isset($_POST['agregar_producto'])) {
                $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, cantidad, precio, cantidad_minima, cantidad_maxima, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiddis", $nombre, $descripcion, $cantidad, $precio, $cantidad_minima, $cantidad_maxima, $imagen);
            } elseif (isset($_POST['editar_producto'])) {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, cantidad=?, precio=?, cantidad_minima=?, cantidad_maxima=?, imagen=? WHERE id=?");
                $stmt->bind_param("ssiddisi", $nombre, $descripcion, $cantidad, $precio, $cantidad_minima, $cantidad_maxima, $imagen, $id);
            }

            if ($stmt->execute()) {
                header("Location: inventario.php");
                exit();
            } else {
                $mensaje = "Error: " . $conn->error;
            }
        }
    } elseif (isset($_POST['crear_usuario'])) {
        // Lógica para crear un nuevo usuario
        $username = $conn->real_escape_string($_POST['username']);
        $password = md5($_POST['password']);
        $role = $conn->real_escape_string($_POST['role']);

        $stmt = $conn->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Usuario creado exitosamente.";
            header("Location: inventario.php");
            exit();
        } else {
            $mensaje = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['cambiar_contraseña'])) {
        // Lógica para cambiar la contraseña de un usuario
        $user_id = (int)$_POST['user_id'];
        $new_password = md5($_POST['new_password']);

        $stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE id=?");
        $stmt->bind_param("si", $new_password, $user_id);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Contraseña actualizada exitosamente.";
            header("Location: inventario.php");
            exit();
        } else {
            $mensaje = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['eliminar_usuario'])) {
        // Lógica para eliminar un usuario
        $user_id = (int)$_POST['user_id'];
        $username = $conn->real_escape_string($_POST['username']);

        if ($username !== 'admin') {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Usuario eliminado exitosamente.";
                header("Location: inventario.php");
                exit();
            } else {
                $mensaje = "Error: " . $conn->error;
            }
        } else {
            $mensaje = "No se puede eliminar el usuario 'admin'.";
        }
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

$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $sql_editar = "SELECT * FROM productos WHERE id=$id_editar";
    $result_editar = $conn->query($sql_editar);
    $producto_editar = $result_editar->fetch_assoc();
}

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

// Obtener lista de usuarios
$sql_usuarios = "SELECT * FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);

// Obtener historial de modificaciones
$sql_historial = "SELECT h.*, p.nombre as producto_nombre FROM historial_modificaciones h JOIN productos p ON h.producto_id = p.id ORDER BY h.fecha DESC";
$result_historial = $conn->query($sql_historial);

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
            cursor: pointer;
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
        .form-container {
            width: 100%;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .input-group textarea {
            resize: vertical;
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
        .add-product-button, .add-user-button, .show-users-button, .show-history-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .add-product-button:hover {
            background-color: #0056b3;
        }
        .add-user-button {
            background-color: #28a745;
        }
        .add-user-button:hover {
            background-color: #218838;
        }
        .show-users-button {
            background-color: #17a2b8;
        }
        .show-users-button:hover {
            background-color: #138496;
        }
        .show-history-button {
            background-color: #ffc107;
        }
        .show-history-button:hover {
            background-color: #e0a800;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 40px;
            border: 1px solid #888;
            width: 60%;
            max-width: 800px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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
        <a href="https://www.mizaki.mx">
            <img src="https://i.postimg.cc/h4bhp1mJ/Mizaki-Ajuste-Logotipo-Mesa-de-trabajo-1.png" alt="Logotipo de Mizaki Campestre" class="logotipo">
        </a>
        <h2 class="titulo-inventario">Inventario Mizaki Campestre (prototipo)</h2>
        <div>
            <button class="show-users-button" onclick="openUserListModal()">Usuarios</button>
            <button class="show-history-button" onclick="openHistoryModal()">Historial</button>
            <a href="logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </div>

    <div class="inventario-container">
        <div class="form-list-container">
            <div class="search-container">
                <form action="inventario.php" method="GET">
                    <input type="text" name="buscar" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($buscar); ?>">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="add-product-button" onclick="openProductModal()">Agregar Producto</button>
                <button class="add-user-button" onclick="openUserModal()">Agregar Usuario</button>
            </div>

            <!-- Modal Formulario Producto -->
            <div id="productModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeProductModal()">&times;</span>
                    <form id="productForm" action="inventario.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="productId" value="">
                        <div class="input-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" name="nombre" id="nombre" required>
                        </div>
                        <div class="input-group">
                            <label for="descripcion">Descripción</label>
                            <textarea name="descripcion" id="descripcion" required></textarea>
                        </div>
                        <div class="input-group">
                            <label for="cantidad">Cantidad</label>
                            <input type="number" step="0.01" name="cantidad" id="cantidad" required>
                        </div>
                        <div class="input-group">
                            <label for="precio">Precio</label>
                            <input type="number" step="0.01" name="precio" id="precio" required>
                        </div>
                        <div class="input-group">
                            <label for="cantidad_minima">Cantidad Mínima</label>
                            <input type="number" step="0.01" name="cantidad_minima" id="cantidad_minima" required>
                        </div>
                        <div class="input-group">
                            <label for="cantidad_maxima">Cantidad Máxima</label>
                            <input type="number" step="0.01" name="cantidad_maxima" id="cantidad_maxima" required>
                        </div>
                        <div class="input-group">
                            <label for="imagen">Imagen</label>
                            <input type="file" name="imagen" id="imagen">
                        </div>
                        <button type="submit" name="agregar_producto" id="submitButton">Agregar Producto</button>
                    </form>
                </div>
            </div>

            <!-- Modal Formulario Usuario -->
            <div id="userModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUserModal()">&times;</span>
                    <form id="userForm" action="inventario.php" method="POST">
                        <div class="input-group">
                            <label for="username">Nombre de Usuario</label>
                            <input type="text" name="username" id="username" required>
                        </div>
                        <div class="input-group">
                            <label for="password">Contraseña</label>
                            <input type="password" name="password" id="password" required>
                            </div>
                            <div class="input-group">
                            <label for="role">Rol</label>
                            <select name="role" id="role" required>
                                <option value="admin">Admin</option>
                                <option value="read_only">Read Only</option>
                            </select>
                        </div>
                        <button type="submit" name="crear_usuario">Crear Usuario</button>
                    </form>
                </div>
            </div>

            <!-- Modal Lista de Usuarios -->
            <div id="userListModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUserListModal()">&times;</span>
                    <h3>Lista de Usuarios</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre de Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $result_usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <button onclick="openChangePasswordModal(<?php echo $user['id']; ?>)">Cambiar Contraseña</button>
                                        <?php if ($user['username'] !== 'admin'): ?>
                                            <form action="inventario.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                                                <button type="submit" name="eliminar_usuario">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Cambiar Contraseña -->
            <div id="changePasswordModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeChangePasswordModal()">&times;</span>
                    <h3>Cambiar Contraseña</h3>
                    <form id="changePasswordForm" action="inventario.php" method="POST">
                        <input type="hidden" name="user_id" id="changePasswordUserId" value="">
                        <div class="input-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" name="new_password" id="new_password" required>
                        </div>
                        <button type="submit" name="cambiar_contraseña">Cambiar Contraseña</button>
                    </form>
                </div>
            </div>

            <!-- Modal Historial de Modificaciones -->
            <div id="historyModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeHistoryModal()">&times;</span>
                    <h3>Historial de Modificaciones</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Usuario</th>
                                <th>Cantidad Anterior</th>
                                <th>Cantidad Nueva</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($historial = $result_historial->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($historial['producto_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($historial['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($historial['cantidad_anterior']); ?></td>
                                    <td><?php echo htmlspecialchars($historial['cantidad_nueva']); ?></td>
                                    <td><?php echo htmlspecialchars($historial['fecha']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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
                            <a href="javascript:void(0);" onclick="editProduct(<?php echo $row['id']; ?>, '<?php echo $row['nombre']; ?>', '<?php echo $row['descripcion']; ?>', <?php echo $row['cantidad']; ?>, <?php echo $row['precio']; ?>, <?php echo $row['cantidad_minima']; ?>, <?php echo $row['cantidad_maxima']; ?>)">Editar</a> |
                            <a href="javascript:void(0);" onclick="eliminarProducto(<?php echo $row['id']; ?>)">Eliminar</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Paginación -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="inventario.php?pagina=<?php echo $i; ?>" class="<?php echo $i == $pagina_actual ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <p class="error-message"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></p>
    <?php endif; ?>

    <script>
        function openProductModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('productForm').reset();
            document.getElementById('submitButton').name = 'agregar_producto';
            document.getElementById('submitButton').textContent = 'Agregar Producto';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function openUserModal() {
            document.getElementById('userModal').style.display = 'block';
            document.getElementById('userForm').reset();
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function openUserListModal() {
            document.getElementById('userListModal').style.display = 'block';
        }

        function closeUserListModal() {
            document.getElementById('userListModal').style.display = 'none';
        }

        function openChangePasswordModal(userId) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordModal').style.display = 'block';
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }

        function openHistoryModal() {
            document.getElementById('historyModal').style.display = 'block';
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').style.display = 'none';
        }

        function editProduct(id, nombre, descripcion, cantidad, precio, cantidad_minima, cantidad_maxima) {
            document.getElementById('productModal').style.display = 'block';
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
                fetch('inventario.php?eliminar=' + id)
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
            if (event.target == document.getElementById('productModal')) {
                document.getElementById('productModal').style.display = "none";
            }
            if (event.target == document.getElementById('userModal')) {
                document.getElementById('userModal').style.display = "none";
            }
            if (event.target == document.getElementById('userListModal')) {
                document.getElementById('userListModal').style.display = "none";
            }
            if (event.target == document.getElementById('changePasswordModal')) {
                document.getElementById('changePasswordModal').style.display = "none";
            }
            if (event.target == document.getElementById('historyModal')) {
                document.getElementById('historyModal').style.display = "none";
            }
        }
    </script>
</body>
</html>