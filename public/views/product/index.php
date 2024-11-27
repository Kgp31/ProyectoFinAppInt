<h1>Productos</h1>
<table>
    <tr>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Descripción</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($productos as $producto): ?>
    <tr>
        <td><?php echo $producto['nombre']; ?></td>
        <td><?php echo $producto['precio']; ?></td>
        <td><?php echo $producto['descripcion']; ?></td>
        <td>
            <a href="/inventariomizaki/public/productos/editar?id=<?php echo $producto['id']; ?>">Editar</a>
            <a href="/inventariomizaki/public/productos/eliminar?id=<?php echo $producto['id']; ?>">Eliminar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<a href="/inventariomizaki/public/productos/crear">Crear producto</a>
