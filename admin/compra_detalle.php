<?php require '../config/config.php';
require_role(['administrador']);
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT v.*, u.nombre, u.apellido, u.usuario, c.usuario AS cajero, r.nombre AS rep_nombre, r.apellido AS rep_apellido
                     FROM ventas v
                     LEFT JOIN usuarios u ON u.id=v.usuario_id
                     LEFT JOIN usuarios c ON c.id=v.cajero_id
                     LEFT JOIN usuarios r ON r.id=v.repartidor_id
                     WHERE v.id=?");
$st->execute([$id]);
$v = $st->fetch();
if (!$v) { flash('error','Compra no encontrada.'); redirect('compras.php'); }

$st = $pdo->prepare("SELECT d.*, p.nombre FROM detalle_venta d JOIN productos p ON p.id=d.producto_id WHERE d.venta_id=?");
$st->execute([$id]);
$items = $st->fetchAll();

$title = 'Compra #'.$id;
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Compra #<?= e($v['id']) ?></h1>
    <a class="btn secondary" href="compras.php">Volver a compras</a>
</div>
<div class="two">
    <div class="card">
        <h2>Datos de la compra</h2>
        <p><strong>Cliente:</strong> <?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'].' ('.$v['usuario'].')' : 'Venta mostrador') ?></p>
        <p><strong>Tipo:</strong> <?= e(ucfirst($v['tipo_venta'])) ?></p>
        <?php if($v['cajero']): ?><p><strong>Cajero:</strong> <?= e($v['cajero']) ?></p><?php endif; ?>
        <p><strong>Fecha:</strong> <?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></p>
        <p><strong>Estado:</strong> <span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span></p>
        <p><strong>Pago:</strong> <span class="badge <?= e($v['estado_pago']) ?>"><?= e(etiqueta_pago($v['estado_pago'])) ?></span></p>
        <p><strong>Total:</strong> Bs <?= number_format($v['total'],2) ?></p>
        <p><strong>Monto cobrado:</strong> Bs <?= number_format($v['monto_cobrado'],2) ?></p>
    </div>
    <div class="card">
        <h2>Entrega</h2>
        <p><strong>Dirección:</strong> <?= e($v['direccion_entrega'] ?: '—') ?></p>
        <p><strong>Referencia:</strong> <?= e($v['referencia_entrega'] ?: '—') ?></p>
        <p><strong>Teléfono:</strong> <?= e($v['telefono_contacto'] ?: '—') ?></p>
        <p><strong>Repartidor:</strong> <?= e($v['rep_nombre'] ? $v['rep_nombre'].' '.$v['rep_apellido'] : '—') ?></p>
        <p><strong>Fecha de entrega:</strong> <?= e($v['fecha_entrega'] ? date('d/m/Y H:i', strtotime($v['fecha_entrega'])) : '—') ?></p>
        <p><strong>Observación:</strong> <?= e($v['observacion_entrega'] ?: '—') ?></p>
    </div>
</div>
<div class="card">
    <h2>Productos comprados</h2>
    <table class="table">
        <tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr>
        <?php foreach($items as $i): ?>
            <tr>
                <td><?= e($i['nombre']) ?></td>
                <td><?= e($i['cantidad']) ?></td>
                <td>Bs <?= number_format($i['precio_unitario'],2) ?></td>
                <td>Bs <?= number_format($i['cantidad']*$i['precio_unitario'],2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><th colspan="3">TOTAL</th><th>Bs <?= number_format($v['total'],2) ?></th></tr>
    </table>
</div>
<?php require '../includes/footer.php'; ?>
