<?php require '../config/config.php';
require_role(['administrador']);
$title = 'Compras registradas';

$estado = $_GET['estado'] ?? '';
$estados = ['pendiente','en_camino','entregada','no_entregado','pagada'];
$sql = "SELECT v.*, u.nombre, u.apellido, c.usuario AS cajero, r.nombre AS rep_nombre, r.apellido AS rep_apellido
        FROM ventas v
        LEFT JOIN usuarios u ON u.id=v.usuario_id
        LEFT JOIN usuarios c ON c.id=v.cajero_id
        LEFT JOIN usuarios r ON r.id=v.repartidor_id";
$args = [];
if ($estado && in_array($estado,$estados,true)) { $sql .= " WHERE v.estado=?"; $args[] = $estado; }
$sql .= " ORDER BY v.id DESC";
$st = $pdo->prepare($sql); $st->execute($args); $ventas = $st->fetchAll();

$sumTotal   = array_sum(array_column($ventas,'total'));
$sumCobrado = array_sum(array_column($ventas,'monto_cobrado'));

require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Compras / Ventas del sistema</h1>
    <a class="btn secondary" href="index.php">Volver al panel</a>
</div>

<div class="three" style="margin-bottom:20px">
    <div class="stat"><div class="n"><?= count($ventas) ?></div><div class="t">Compras listadas</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($sumTotal,2) ?></div><div class="t">Total facturado</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($sumCobrado,2) ?></div><div class="t">Monto realmente cobrado</div></div>
</div>

<div class="card">
    <h2>Filtrar por estado</h2>
    <div class="actions" style="margin-top:0">
        <a class="btn <?= $estado?'secondary':'' ?>" href="compras.php">Todas</a>
        <?php foreach($estados as $es): ?>
            <a class="btn <?= $estado===$es?'':'secondary' ?>" href="compras.php?estado=<?= e($es) ?>"><?= e(etiqueta_estado($es)) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Detalle de compras</h2>
    <?php if(!$ventas): ?>
        <p class="small">No hay compras con ese filtro.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr>
                <th>#</th><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Dirección</th>
                <th>Total</th><th>Cobrado</th><th>Estado</th><th>Pago</th><th>Repartidor</th><th></th>
            </tr>
            <?php foreach($ventas as $v): ?>
                <tr>
                    <td>#<?= e($v['id']) ?></td>
                    <td class="small"><?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></td>
                    <td><?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'] : 'Venta mostrador') ?></td>
                    <td><?= e(ucfirst($v['tipo_venta'])) ?><?= $v['cajero'] ? '<br><span class="small">Cajero: '.e($v['cajero']).'</span>' : '' ?></td>
                    <td class="small"><?= e($v['direccion_entrega'] ?: '—') ?></td>
                    <td>Bs <?= number_format($v['total'],2) ?></td>
                    <td>Bs <?= number_format($v['monto_cobrado'],2) ?></td>
                    <td><span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span></td>
                    <td><span class="badge <?= e($v['estado_pago']) ?>"><?= e(etiqueta_pago($v['estado_pago'])) ?></span></td>
                    <td class="small"><?= e($v['rep_nombre'] ? $v['rep_nombre'].' '.$v['rep_apellido'] : '—') ?></td>
                    <td><a class="btn mini secondary" href="compra_detalle.php?id=<?= e($v['id']) ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
