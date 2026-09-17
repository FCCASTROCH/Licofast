<?php require '../config/config.php';
require_role(['cliente']);
$title = 'Mis pedidos';
$st = $pdo->prepare("SELECT v.*, r.nombre AS rep_nombre, r.apellido AS rep_apellido
                     FROM ventas v LEFT JOIN usuarios r ON r.id=v.repartidor_id
                     WHERE v.usuario_id=? ORDER BY v.id DESC");
$st->execute([user()['id']]);
$ventas = $st->fetchAll();

$det = $pdo->prepare("SELECT d.cantidad, d.precio_unitario, p.nombre FROM detalle_venta d JOIN productos p ON p.id=d.producto_id WHERE d.venta_id=?");
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Mis pedidos</h1>
    <a class="btn" href="index.php">Ir a la tienda</a>
</div>
<?php if(!$ventas): ?>
    <div class="card"><p class="small">Aún no realizaste ninguna compra.</p></div>
<?php else: foreach($ventas as $v): $det->execute([$v['id']]); $items = $det->fetchAll(); ?>
    <div class="card">
        <h2>Pedido #<?= e($v['id']) ?> · <?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></h2>
        <p>
            <span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span>
            <span class="badge <?= e($v['estado_pago']) ?>"><?= e(etiqueta_pago($v['estado_pago'])) ?></span>
        </p>
        <p class="small"><strong>Entrega en:</strong> <?= e($v['direccion_entrega'] ?: 'Compra en mostrador') ?>
            <?= $v['referencia_entrega'] ? ' · '.e($v['referencia_entrega']) : '' ?></p>
        <?php if($v['rep_nombre']): ?>
            <p class="small"><strong>Repartidor:</strong> <?= e($v['rep_nombre'].' '.$v['rep_apellido']) ?></p>
        <?php endif; ?>
        <?php if($v['observacion_entrega']): ?>
            <p class="small"><strong>Observación:</strong> <?= e($v['observacion_entrega']) ?></p>
        <?php endif; ?>
        <table class="table">
            <tr><th>Producto</th><th>Cant.</th><th>Subtotal</th></tr>
            <?php foreach($items as $i): ?>
                <tr><td><?= e($i['nombre']) ?></td><td><?= e($i['cantidad']) ?></td><td>Bs <?= number_format($i['cantidad']*$i['precio_unitario'],2) ?></td></tr>
            <?php endforeach; ?>
            <tr><th colspan="2">TOTAL</th><th>Bs <?= number_format($v['total'],2) ?></th></tr>
        </table>
    </div>
<?php endforeach; endif; ?>
<?php require '../includes/footer.php'; ?>
