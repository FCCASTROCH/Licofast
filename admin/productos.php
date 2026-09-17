<?php require '../config/config.php';
require_role(['administrador']);
$title = 'Productos';
require '../includes/header.php';
$prod = $pdo->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll();
?>
<div class="seccion-titulo">
    <h1>Inventario de productos</h1>
    <a class="btn" href="producto.php">+ Nuevo producto</a>
</div>
<div class="card">
    <h2>Productos registrados</h2>
    <div class="tabla-scroll">
    <table class="table">
        <tr><th>#</th><th>Producto</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
        <?php foreach($prod as $p): ?>
            <tr>
                <td><?= e($p['id']) ?></td>
                <td>
                    <?= e($p['nombre']) ?>
                    <?= empty($p['activo']) ? ' <span class="badge inactivo">Inactivo</span>' : '' ?>
                </td>
                <td class="small"><?= e($p['descripcion']) ?></td>
                <td>Bs <?= number_format($p['precio'],2) ?></td>
                <td><?= e($p['stock']) ?></td>
                <td>
                    <a class="btn mini secondary" href="producto.php?id=<?= e($p['id']) ?>">Editar</a>
                    <a class="btn mini danger" href="producto.php?id=<?= e($p['id']) ?>&delete=1" onclick="return confirm('¿Eliminar o desactivar producto?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
