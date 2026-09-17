<?php require '../config/config.php';
require_role(['repartidor']);
$title = 'Historial de entregas';
$st = $pdo->prepare("SELECT v.*, u.nombre, u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id
                     WHERE v.repartidor_id=? ORDER BY v.id DESC");
$st->execute([user()['id']]);
$ventas = $st->fetchAll();
$cobrado = 0; foreach($ventas as $v){ $cobrado += (float)$v['monto_cobrado']; }
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Mi historial de entregas</h1>
    <a class="btn" href="index.php">Entregas pendientes</a>
</div>
<div class="three" style="margin-bottom:20px">
    <div class="stat"><div class="n"><?= count($ventas) ?></div><div class="t">Pedidos atendidos</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($cobrado,2) ?></div><div class="t">Monto total cobrado</div></div>
</div>
<div class="card">
    <h2>Detalle</h2>
    <?php if(!$ventas): ?>
        <p class="small">Todavía no registraste entregas.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>#</th><th>Cliente</th><th>Dirección</th><th>Resultado</th><th>Monto cobrado</th><th>Fecha</th><th>Observación</th></tr>
            <?php foreach($ventas as $v): ?>
                <tr>
                    <td>#<?= e($v['id']) ?></td>
                    <td><?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'] : 'Cliente') ?></td>
                    <td class="small"><?= e($v['direccion_entrega']) ?></td>
                    <td><span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span></td>
                    <td>Bs <?= number_format($v['monto_cobrado'],2) ?></td>
                    <td class="small"><?= e($v['fecha_entrega'] ? date('d/m/Y H:i', strtotime($v['fecha_entrega'])) : '—') ?></td>
                    <td class="small"><?= e($v['observacion_entrega'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
