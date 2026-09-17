<?php require '../config/config.php';
require_role(['cajero']);
$title = 'Mis ventas';
$st = $pdo->prepare("SELECT v.*, u.nombre, u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.cajero_id=? ORDER BY v.id DESC");
$st->execute([user()['id']]);
$sales = $st->fetchAll();
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Compras registradas por mí</h1>
    <a class="btn" href="index.php">+ Registrar compra</a>
</div>
<div class="card">
    <h2>Historial de caja</h2>
    <?php if(!$sales): ?>
        <p class="small">Todavía no registraste ninguna compra.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Pago</th></tr>
            <?php foreach($sales as $s): ?>
                <tr>
                    <td>#<?= e($s['id']) ?></td>
                    <td class="small"><?= e(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
                    <td><?= e($s['nombre'] ? $s['nombre'].' '.$s['apellido'] : 'Venta mostrador') ?></td>
                    <td>Bs <?= number_format($s['total'],2) ?></td>
                    <td><span class="badge <?= e($s['estado']) ?>"><?= e(etiqueta_estado($s['estado'])) ?></span></td>
                    <td><span class="badge <?= e($s['estado_pago']) ?>"><?= e(etiqueta_pago($s['estado_pago'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
