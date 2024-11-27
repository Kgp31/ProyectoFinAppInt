<h1>Crear Producto</h1>
<form action="/inventariomizaki/public/productos/crear" method="POST">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" required>
    
    <label for="precio">Precio:</label>
    <input type="text" name="precio" required>
    
    <label for="descripcion">Descripción:</label>
    <textarea name="descripcion" required></textarea>
    
    <button type="submit">Crear Producto</button>
</form>
