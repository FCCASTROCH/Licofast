<?php
require '../config/config.php';
require_role(['cliente']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (isset($_POST['vaciar'])) {
        $_SESSION['cart'] = [];
        flash('ok','Carrito vaciado.');
        redirect('carrito.php');
    }
    if (isset($_POST['eliminar_id'])) {
        unset($_SESSION['cart'][(int)$_POST['eliminar_id']]);
        flash('ok','Producto quitado del carrito.');
        redirect('carrito.php');
    }

    $id  = (int)($_POST['producto_id'] ?? 0);
    $qty = max(1,(int)($_POST['cantidad'] ?? 1));
    $st = $pdo->prepare('SELECT stock FROM productos WHERE id=? AND activo=1');
    $st->execute([$id]);
    $p = $st->fetch();
    if ($p) {
        $_SESSION['cart'][$id] = min((int)$p['stock'], ($_SESSION['cart'][$id] ?? 0) + $qty);
        flash('ok','Producto agregado al carrito.');
    }
    redirect('carrito.php');
}

$cart = $_SESSION['cart'] ?? [];
$items = []; $total = 0;
if ($cart) {
    $in = implode(',', array_fill(0, count($cart), '?'));
    $st = $pdo->prepare("SELECT * FROM productos WHERE id IN ($in)");
    $st->execute(array_keys($cart));
    foreach ($st as $p) {
        $q = min($cart[$p['id']], $p['stock']);
        $items[] = ['p'=>$p,'q'=>$q,'sub'=>$q*$p['precio']];
        $total += $q * $p['precio'];
    }
}

$title = 'Carrito de compras';
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Carrito de compras</h1>
    <a class="btn secondary" href="index.php">Seguir comprando</a>
</div>
<div class="card">
    <h2>Productos seleccionados</h2>
    <?php if (!$items): ?>
        <p class="small">Tu carrito está vacío.</p>
        <div class="actions"><a class="btn" href="index.php">Ir a la tienda</a></div>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
            <?php foreach ($items as $i): ?>
                <tr>
                    <td><?= e($i['p']['nombre']) ?></td>
                    <td>Bs <?= number_format($i['p']['precio'],2) ?></td>
                    <td><?= e($i['q']) ?></td>
                    <td>Bs <?= number_format($i['sub'],2) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="eliminar_id" value="<?= e($i['p']['id']) ?>">
                            <button class="btn mini danger" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr><th colspan="3">TOTAL A PAGAR</th><th colspan="2">Bs <?= number_format($total,2) ?></th></tr>
        </table>
        </div>
        <div class="actions">
            <a class="btn" href="checkout.php">Continuar y registrar dirección de entrega</a>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="vaciar" value="1">
                <button class="btn secondary" type="submit" onclick="return confirm('¿Vaciar todo el carrito?')">Vaciar carrito</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
