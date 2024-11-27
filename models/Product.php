<?php

class Product {
    // Obtiene todos los productos
    public static function all() {
        global $conn;  // Usa la conexión a la base de datos

        $sql = "SELECT * FROM productos";  // Ajusta según tu tabla de productos
        $result = $conn->query($sql);

        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        return $productos;
    }

    // Crear un nuevo producto
    public static function create($nombre, $precio, $descripcion) {
        global $conn;

        $sql = "INSERT INTO productos (nombre, precio, descripcion) VALUES ('$nombre', '$precio', '$descripcion')";
        $conn->query($sql);
    }

    // Buscar un producto por su ID
    public static function find($id) {
        global $conn;

        $sql = "SELECT * FROM productos WHERE id = $id";
        $result = $conn->query($sql);
        return $result->fetch_assoc();
    }

    // Actualizar un producto
    public function update($nombre, $precio, $descripcion) {
        global $conn;
        $sql = "UPDATE productos SET nombre = '$nombre', precio = '$precio', descripcion = '$descripcion' WHERE id = {$this->id}";
        $conn->query($sql);
    }

    // Eliminar un producto
    public function delete() {
        global $conn;
        $sql = "DELETE FROM productos WHERE id = {$this->id}";
        $conn->query($sql);
    }
}
