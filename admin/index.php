<?php require '../config/config.php';
require_role(['administrador']);
$title = 'Panel del administrador';

$totProductos = (int)$pdo->query('SELECT COUNT(*) FROM productos WHERE activo=1')->fetchColumn();
$totUsuarios  = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo=1')->fetchColumn();
$totVentas    = (int)$pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$pendientes   = (int)$pdo->query("SELECT COUNT(*) FROM ventas WHERE estado IN ('pendiente','en_camino')")->fetchColumn();
$cobrado      = (float)$pdo->query("SELECT COALESCE(SUM(monto_cobrado),0) FROM ventas WHERE estado_pago='pagado'")->fetchColumn();

$porRol = $pdo->query("SELECT rol, COUNT(*) c FROM usuarios WHERE activo=1 GROUP BY rol")->fetchAll();
$ultimas = $pdo->query("SELECT v.*, u.nombre, u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id ORDER BY v.id DESC LIMIT 8")->fetchAll();
$bajoStock = $pdo->query("SELECT * FROM productos WHERE activo=1 AND stock<=5 ORDER BY stock ASC")->fetchAll();

require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Panel del administrador</h1>
    <div class="actions" style="margin:0">
        <a class="btn" href="producto.php">+ Nuevo producto</a>
        <a class="btn" href="usuario.php">+ Nuevo usuario</a>
        <a class="btn secondary" href="compras.php">Ver compras</a>
    </div>
</div>
<p class="small">Como administrador puedes registrar productos, crear usuarios con rol de <strong>cajero</strong>, <strong>repartidor (delivery)</strong>, <strong>cliente</strong> o <strong>administrador</strong>, y revisar todas las compras del sistema.</p>

<div class="three" style="margin-bottom:22px">
    <div class="stat"><div class="n"><?= $totProductos ?></div><div class="t">Productos activos</div></div>
    <div class="stat"><div class="n"><?= $totUsuarios ?></div><div class="t">Usuarios activos</div></div>
    <div class="stat"><div class="n"><?= $totVentas ?></div><div class="t">Compras registradas</div></div>
    <div class="stat"><div class="n"><?= $pendientes ?></div><div class="t">Entregas pendientes</div></div>
    <div class="stat"><div class="n">Bs <?= number_format($cobrado,2) ?></div><div class="t">Monto cobrado</div></div>
</div>

<div class="two">
    <div class="card">
        <h2>Usuarios por rol</h2>
        <table class="table">
            <tr><th>Rol</th><th>Cantidad</th></tr>
            <?php foreach($porRol as $r): ?>
                <tr>
                    <td><span class="badge rol"><?= e(nombre_rol($r['rol'])) ?></span></td>
                    <td><?= e($r['c']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="actions"><a class="btn" href="usuarios.php">Administrar usuarios y roles</a></div>
    </div>
    <div class="card">
        <h2>Stock bajo (5 o menos)</h2>
        <?php if(!$bajoStock): ?>
            <p class="small">Todos los productos tienen stock suficiente.</p>
        <?php else: ?>
            <table class="table">
                <tr><th>Producto</th><th>Stock</th><th></th></tr>
                <?php foreach($bajoStock as $p): ?>
                    <tr>
                        <td><?= e($p['nombre']) ?></td>
                        <td><?= e($p['stock']) ?></td>
                        <td><a class="btn mini" href="producto.php?id=<?= e($p['id']) ?>">Reponer</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <div class="actions"><a class="btn" href="productos.php">Administrar productos</a></div>
    </div>
</div>

<div class="card">
    <h2>Últimas compras</h2>
    <?php if(!$ultimas): ?>
        <p class="small">Todavía no hay compras registradas.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="table">
            <tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Total</th><th>Estado</th><th>Pago</th><th></th></tr>
            <?php foreach($ultimas as $v): ?>
                <tr>
                    <td>#<?= e($v['id']) ?></td>
                    <td><?= e($v['nombre'] ? $v['nombre'].' '.$v['apellido'] : 'Venta mostrador') ?></td>
                    <td><?= e(ucfirst($v['tipo_venta'])) ?></td>
                    <td>Bs <?= number_format($v['total'],2) ?></td>
                    <td><span class="badge <?= e($v['estado']) ?>"><?= e(etiqueta_estado($v['estado'])) ?></span></td>
                    <td><span class="badge <?= e($v['estado_pago']) ?>"><?= e(etiqueta_pago($v['estado_pago'])) ?></span></td>
                    <td><a class="btn mini secondary" href="compra_detalle.php?id=<?= e($v['id']) ?>">Detalle</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <div class="actions"><a class="btn" href="compras.php">Ver todas las compras</a></div>
    <?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
