<?php
require_once '../app/database.php';  // Incluir la conexión a la base de datos

class ProductController {

    public function index() {
        $conn = getDbConnection(); // Obtener la conexión a la base de datos
        $sql = "SELECT * FROM productos";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<p>" . $row['nombre'] . " - " . $row['precio'] . "</p>";
            }
        } else {
            echo "<p>No hay productos disponibles.</p>";
        }
    }

    public function create() {
        echo "<h1>Formulario de Crear Producto</h1>";
        // Aquí puedes incluir un formulario HTML para crear un producto
    }

    public function edit($id) {
        $conn = getDbConnection();
        $sql = "SELECT * FROM productos WHERE id = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            echo "<h1>Editar Producto</h1>";
            echo "<form action='../app/edit_product.php' method='POST'>
                    <input type='hidden' name='id' value='" . $product['id'] . "'>
                    <label for='nombre'>Nombre:</label>
                    <input type='text' name='nombre' value='" . $product['nombre'] . "' required>
                    <label for='precio'>Precio:</label>
                    <input type='number' name='precio' value='" . $product['precio'] . "' required>
                    <button type='submit'>Actualizar</button>
                  </form>";
        } else {
            echo "<p>Producto no encontrado.</p>";
        }
    }

    public function delete($id) {
        $conn = getDbConnection();
        $sql = "DELETE FROM productos WHERE id = $id";

        if ($conn->query($sql) === TRUE) {
            echo "<p>Producto eliminado con éxito.</p>";
        } else {
            echo "<p>Error al eliminar el producto: " . $conn->error . "</p>";
        }
    }
}
?>
