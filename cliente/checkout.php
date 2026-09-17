<?php require '../config/config.php';
require_role(['cliente']);

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { flash('error','Tu carrito está vacío.'); redirect('index.php'); }

/* ---------- Confirmación de la compra ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $dir  = trim($_POST['direccion'] ?? '');
    $ref  = trim($_POST['referencia'] ?? '');
    $tel  = trim($_POST['telefono'] ?? '');

    if ($dir === '' || $tel === '') {
        flash('error','Debes registrar la dirección de entrega y un teléfono de contacto.');
        redirect('checkout.php');
    }

    $pdo->beginTransaction();
    try {
        $total = 0; $rows = [];
        $st = $pdo->prepare('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE');
        foreach ($cart as $id => $q) {
            $q = (int)$q;
            $st->execute([(int)$id]);
            $p = $st->fetch();
            if (!$p || $q < 1 || $p['stock'] < $q) throw new Exception('Stock insuficiente para '.($p ? $p['nombre'] : 'un producto').'.');
            $total += $p['precio'] * $q;
            $rows[] = ['id'=>$p['id'],'q'=>$q,'price'=>$p['precio']];
        }

        // La compra queda PENDIENTE y el pago PENDIENTE hasta que el repartidor entregue.
        $pdo->prepare("INSERT INTO ventas(usuario_id,tipo_venta,total,monto_cobrado,estado,estado_pago,direccion_entrega,referencia_entrega,telefono_contacto)
                       VALUES(?,'delivery',?,0,'pendiente','pendiente',?,?,?)")
            ->execute([user()['id'], $total, $dir, $ref ?: null, $tel]);
        $venta = $pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO detalle_venta(venta_id,producto_id,cantidad,precio_unitario) VALUES(?,?,?,?)');
        $upd = $pdo->prepare('UPDATE productos SET stock=stock-? WHERE id=?');
        foreach ($rows as $r) { $ins->execute([$venta,$r['id'],$r['q'],$r['price']]); $upd->execute([$r['q'],$r['id']]); }

        $pdo->commit();
        $_SESSION['cart'] = [];
        flash('ok','Pedido #'.$venta.' registrado por Bs '.number_format($total,2).'. Estado de pago: PENDIENTE hasta la entrega.');
        redirect('pedidos.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error',$e->getMessage());
        redirect('carrito.php');
    }
}

/* ---------- Formulario de entrega ---------- */
$items = []; $total = 0;
$in = implode(',', array_fill(0, count($cart), '?'));
$st = $pdo->prepare("SELECT * FROM productos WHERE id IN ($in)");
$st->execute(array_keys($cart));
foreach ($st as $p) {
    $q = min($cart[$p['id']], $p['stock']);
    $items[] = ['p'=>$p,'q'=>$q,'sub'=>$q*$p['precio']];
    $total += $q * $p['precio'];
}

$me = $pdo->prepare('SELECT telefono FROM usuarios WHERE id=?');
$me->execute([user()['id']]);
$telUsuario = (string)($me->fetchColumn() ?: '');

$title = 'Datos de entrega';
require '../includes/header.php';
?>
<div class="seccion-titulo"><h1>Registrar lugar de entrega</h1></div>
<div class="two">
    <div class="card">
        <h2>Resumen del pedido</h2>
        <table class="table">
            <tr><th>Producto</th><th>Cant.</th><th>Subtotal</th></tr>
            <?php foreach($items as $i): ?>
                <tr><td><?= e($i['p']['nombre']) ?></td><td><?= e($i['q']) ?></td><td>Bs <?= number_format($i['sub'],2) ?></td></tr>
            <?php endforeach; ?>
            <tr><th colspan="2">TOTAL</th><th>Bs <?= number_format($total,2) ?></th></tr>
        </table>
        <p class="small">El pago se cobrará <strong>contra entrega</strong>. Hasta entonces el estado del pago será “pendiente”.</p>
    </div>
    <div class="card">
        <h2>Lugar de entrega</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <div class="field">
                <label>Dirección de entrega *</label>
                <input name="direccion" required maxlength="255" placeholder="Calle, número, zona, ciudad">
            </div>
            <div class="field">
                <label>Referencia (opcional)</label>
                <input name="referencia" maxlength="255" placeholder="Casa de dos pisos, portón verde…">
            </div>
            <div class="field">
                <label>Teléfono de contacto *</label>
                <input name="telefono" required maxlength="30" value="<?= e($telUsuario) ?>">
            </div>
            <div class="actions">
                <button class="btn">Confirmar pedido</button>
                <a class="btn secondary" href="carrito.php">Volver al carrito</a>
            </div>
        </form>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
