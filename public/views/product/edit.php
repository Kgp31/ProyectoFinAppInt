<h1>Editar Producto</h1>
<form action="/inventariomizaki/public/productos/editar?id=<?php echo $producto['id']; ?>" method="POST">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" value="<?php echo $producto['nombre']; ?>" required>
    
    <label for="precio">Precio:</label>
    <input type="text" name="precio" value="<?php echo $producto['precio']; ?>" required>
    
    <label for="descripcion">Descripción:</label>
    <textarea name="descripcion" required><?php echo $producto['descripcion']; ?></textarea>
    
    <button type="submit">Actualizar Producto</button>
</form>
