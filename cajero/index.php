<?php require '../config/config.php';
require_role(['cajero']);
$title = 'Caja · Registrar compra';
require '../includes/header.php';
$products = $pdo->query('SELECT id,nombre,precio,stock FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre')->fetchAll();
$clientes = $pdo->query("SELECT id,nombre,apellido,ci FROM usuarios WHERE rol='cliente' AND activo=1 ORDER BY nombre")->fetchAll();
$hoy = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE cajero_id=? AND DATE(created_at)=CURDATE()");
$hoy->execute([user()['id']]);
$res = $hoy->fetch();
?>
<div class="seccion-titulo">
    <h1>Caja de LicoFast</h1>
    <a class="btn secondary" href="ventas.php">Ver mis ventas</a>
</div>
<p class="small">Como cajero tu única función es <strong>registrar las compras</strong> que se realizan en el mostrador. El cobro queda registrado al instante.</p>

<div class="three" style="margin-bottom:20px">
    <div class="stat"><div class="n"><?= e($res['c']) ?></div><div class="t">Ventas de hoy</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($res['t'],2) ?></div><div class="t">Cobrado hoy</div></div>
</div>

<div class="card">
    <h2>Registrar nueva compra</h2>
    <?php if(!$products): ?>
        <p class="small">No hay productos con stock disponible. Avisa al administrador.</p>
    <?php else: ?>
    <form method="post" action="venta.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="field">
            <label>Cliente (opcional)</label>
            <select name="cliente_id">
                <option value="">Venta de mostrador (sin cliente registrado)</option>
                <?php foreach($clientes as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['nombre'].' '.$c['apellido'].' — CI '.$c['ci']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Cantidad</th></tr>
            <?php foreach($products as $p): ?>
                <tr>
                    <td><?= e($p['nombre']) ?></td>
                    <td>Bs <?= number_format($p['precio'],2) ?></td>
                    <td><?= e($p['stock']) ?></td>
                    <td><input type="number" name="qty[<?= e($p['id']) ?>]" min="0" max="<?= e($p['stock']) ?>" value="0" style="width:90px;padding:8px;border:1px solid #cfc7b8;border-radius:8px"></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <div class="actions"><button class="btn">Registrar compra y cobrar</button></div>
    </form>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
