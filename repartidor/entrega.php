<?php require '../config/config.php';
require_role(['repartidor']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

/* ---------- Confirmación: entregado o no entregado ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $accion = $_POST['accion'] ?? '';
    $obs    = trim($_POST['observacion'] ?? '');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM ventas WHERE id=? AND estado IN ('pendiente','en_camino') FOR UPDATE");
        $st->execute([$id]);
        $v = $st->fetch();
        if (!$v) throw new Exception('El pedido ya fue procesado o no existe.');

        if ($accion === 'entregado') {
            // Producto entregado: el monto queda registrado como cobrado.
            $pdo->prepare("UPDATE ventas
                           SET estado='entregada', estado_pago='pagado', monto_cobrado=total,
                               repartidor_id=?, fecha_entrega=NOW(), observacion_entrega=?
                           WHERE id=?")
                ->execute([user()['id'], $obs ?: 'Entrega conforme', $id]);
            $pdo->commit();
            flash('ok','Pedido #'.$id.' ENTREGADO. Monto registrado: Bs '.number_format($v['total'],2));

        } elseif ($accion === 'no_entregado') {
            // No entregado: los productos vuelven al inventario y el pago se anula.
            $pdo->prepare("UPDATE ventas
                           SET estado='no_entregado', estado_pago='anulado', monto_cobrado=0,
                               repartidor_id=?, fecha_entrega=NOW(), observacion_entrega=?
                           WHERE id=?")
                ->execute([user()['id'], $obs ?: 'El cliente no recibió el pedido', $id]);

            $det = $pdo->prepare('SELECT producto_id, cantidad FROM detalle_venta WHERE venta_id=?');
            $det->execute([$id]);
            $upd = $pdo->prepare('UPDATE productos SET stock=stock+? WHERE id=?');
            foreach ($det->fetchAll() as $d) { $upd->execute([$d['cantidad'], $d['producto_id']]); }

            $pdo->commit();
            flash('ok','Pedido #'.$id.' marcado como NO ENTREGADO. Los productos volvieron al inventario y no se registró ningún monto.');
        } else {
            throw new Exception('Debes elegir una de las dos opciones.');
        }
        redirect('index.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error',$e->getMessage());
        redirect('index.php');
    }
}

/* ---------- Pantalla con las dos opciones ---------- */
$st = $pdo->prepare("SELECT v.*, u.nombre, u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id
                     WHERE v.id=? AND v.estado IN ('pendiente','en_camino')");
$st->execute([$id]);
$v = $st->fetch();
if (!$v) { flash('error','Pedido no disponible para entrega.'); redirect('index.php'); }

$det = $pdo->prepare("SELECT d.cantidad, d.precio_unitario, p.nombre FROM detalle_venta d JOIN productos p ON p.id=d.producto_id WHERE d.venta_id=?");
$det->execute([$id]);
$items = $det->fetchAll();

$title = 'Entrega del pedido #'.$id;
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Entrega del pedido #<?= e($v['id']) ?></h1>
    <a class="btn secondary" href="index.php">Volver</a>
</div>
<div class="two">
    <div class="card">
        <h2>Datos del pedido</h2>
        <p><strong>Cliente:</strong> <?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'] : 'Cliente') ?></p>
        <p><strong>Dirección:</strong> <?= e($v['direccion_entrega']) ?></p>
        <p><strong>Referencia:</strong> <?= e($v['referencia_entrega'] ?: '—') ?></p>
        <p><strong>Teléfono:</strong> <?= e($v['telefono_contacto'] ?: '—') ?></p>
        <p class="price">Monto a cobrar: Bs <?= number_format($v['total'],2) ?></p>
    </div>
    <div class="card">
        <h2>Productos</h2>
        <table class="table">
            <tr><th>Producto</th><th>Cant.</th><th>Subtotal</th></tr>
            <?php foreach($items as $i): ?>
                <tr><td><?= e($i['nombre']) ?></td><td><?= e($i['cantidad']) ?></td><td>Bs <?= number_format($i['cantidad']*$i['precio_unitario'],2) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="card">
    <h2>¿Se entregó el pedido?</h2>
    <div class="opciones-entrega">
        <form method="post" class="opcion si" onsubmit="return confirm('Confirmar: PRODUCTO ENTREGADO. Se registrará el cobro de Bs <?= number_format($v['total'],2) ?>')">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="id" value="<?= e($v['id']) ?>">
            <input type="hidden" name="accion" value="entregado">
            <h3>✅ Producto entregado</h3>
            <p class="small">Se registra el monto de <strong>Bs <?= number_format($v['total'],2) ?></strong> como cobrado y el pedido pasa a “Entregada”.</p>
            <div class="field"><label>Observación (opcional)</label><input name="observacion" maxlength="255" placeholder="Entregado en puerta"></div>
            <button class="btn ok">Confirmar entrega</button>
        </form>

        <form method="post" class="opcion no" onsubmit="return confirm('Confirmar: NO ENTREGADO. Los productos volverán al inventario.')">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="id" value="<?= e($v['id']) ?>">
            <input type="hidden" name="accion" value="no_entregado">
            <h3>❌ No entregado</h3>
            <p class="small">No se registra ningún monto y <strong>los productos vuelven al stock</strong>.</p>
            <div class="field"><label>Motivo (opcional)</label><input name="observacion" maxlength="255" placeholder="Cliente ausente, dirección incorrecta…"></div>
            <button class="btn danger">Marcar como no entregado</button>
        </form>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
