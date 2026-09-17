<?php require '../config/config.php';
require_role(['repartidor']);
$title = 'Entregas pendientes';

$orders = $pdo->query("SELECT v.*, u.nombre, u.apellido
                       FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id
                       WHERE v.estado IN ('pendiente','en_camino') AND v.tipo_venta='delivery'
                       ORDER BY v.id ASC")->fetchAll();

$mias = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(monto_cobrado),0) t FROM ventas WHERE repartidor_id=? AND estado='entregada'");
$mias->execute([user()['id']]);
$r = $mias->fetch();

require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Pedidos por entregar</h1>
    <a class="btn secondary" href="historial.php">Mi historial</a>
</div>
<p class="small">Estas son las compras con <strong>pago pendiente</strong>. Presiona “Entregar” para confirmar si el producto fue entregado o no.</p>

<div class="three" style="margin-bottom:20px">
    <div class="stat"><div class="n"><?= count($orders) ?></div><div class="t">Pedidos pendientes</div></div>
    <div class="stat"><div class="n"><?= e($r['c']) ?></div><div class="t">Entregas realizadas</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($r['t'],2) ?></div><div class="t">Monto que cobré</div></div>
</div>

<div class="card">
    <h2>Lista de entregas</h2>
    <?php if (!$orders): ?>
        <p class="small">No hay pedidos pendientes en este momento.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>Pedido</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th><th>Monto a cobrar</th><th>Estado</th><th></th></tr>
            <?php foreach ($orders as $v): ?>
                <tr>
                    <td>#<?= e($v['id']) ?><br><span class="small"><?= e(date('d/m H:i', strtotime($v['created_at']))) ?></span></td>
                    <td><?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'] : 'Cliente') ?></td>
                    <td class="small"><?= e($v['direccion_entrega']) ?><?= $v['referencia_entrega'] ? '<br>'.e($v['referencia_entrega']) : '' ?></td>
                    <td class="small"><?= e($v['telefono_contacto'] ?: '—') ?></td>
                    <td><strong>Bs <?= number_format($v['total'],2) ?></strong></td>
                    <td><span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span></td>
                    <td><a class="btn" href="entrega.php?id=<?= e($v['id']) ?>">Entregar</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
