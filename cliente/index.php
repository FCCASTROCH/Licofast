<?php require '../config/config.php';
require_role(['cliente']);
$title = 'Tienda LicoFast';
require '../includes/header.php';
$products = $pdo->query('SELECT * FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre')->fetchAll();
?>
<div class="seccion-titulo">
    <h1>Tienda</h1>
    <a class="btn" href="carrito.php">🛒 Mi carrito (<?= cart_count() ?>)</a>
</div>
<p class="small">Elige tus productos, agrégalos al carrito y al confirmar registra la dirección de entrega. El pago queda <strong>pendiente</strong> hasta que el repartidor entregue el pedido.</p>
<div class="grid">
    <?php foreach($products as $p): ?>
        <div class="card product">
            <div class="emoji">🍾</div>
            <h3><?= e($p['nombre']) ?></h3>
            <p class="small"><?= e($p['descripcion']) ?></p>
            <p class="price">Bs <?= number_format($p['precio'],2) ?></p>
            <p class="small">Disponibles: <?= e($p['stock']) ?></p>
            <form method="post" action="carrito.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="producto_id" value="<?= e($p['id']) ?>">
                <input type="number" name="cantidad" value="1" min="1" max="<?= e($p['stock']) ?>">
                <button class="btn">Agregar</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php if(!$products): ?><div class="card"><p class="small">No hay productos disponibles por el momento.</p></div><?php endif; ?>
<?php require '../includes/footer.php'; ?>
