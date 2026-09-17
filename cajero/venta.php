<?php require '../config/config.php';
require_role(['cajero']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
check_csrf();

$qty = $_POST['qty'] ?? [];
$cliente = (int)($_POST['cliente_id'] ?? 0) ?: null;

$pdo->beginTransaction();
try {
    if ($cliente) {
        $chk = $pdo->prepare("SELECT id FROM usuarios WHERE id=? AND rol='cliente'");
        $chk->execute([$cliente]);
        if (!$chk->fetch()) throw new Exception('El cliente seleccionado no existe.');
    }

    $total = 0; $rows = [];
    $st = $pdo->prepare('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE');
    foreach ($qty as $id => $q) {
        $q = (int)$q;
        if ($q <= 0) continue;
        $st->execute([(int)$id]);
        $p = $st->fetch();
        if (!$p || $p['stock'] < $q) throw new Exception('Stock insuficiente para '.($p ? $p['nombre'] : 'el producto seleccionado').'.');
        $rows[] = [$p,$q];
        $total += $p['precio'] * $q;
    }
    if (!$rows) throw new Exception('Selecciona al menos un producto con cantidad mayor a 0.');

    $pdo->prepare("INSERT INTO ventas(usuario_id,cajero_id,tipo_venta,total,monto_cobrado,estado,estado_pago,fecha_entrega)
                   VALUES(?,?,'mostrador',?,?,'pagada','pagado',NOW())")
        ->execute([$cliente, user()['id'], $total, $total]);
    $vid = $pdo->lastInsertId();

    $ins = $pdo->prepare('INSERT INTO detalle_venta(venta_id,producto_id,cantidad,precio_unitario) VALUES(?,?,?,?)');
    $upd = $pdo->prepare('UPDATE productos SET stock=stock-? WHERE id=?');
    foreach ($rows as [$p,$q]) { $ins->execute([$vid,$p['id'],$q,$p['precio']]); $upd->execute([$q,$p['id']]); }

    $pdo->commit();
    flash('ok','Compra #'.$vid.' registrada y cobrada. Total: Bs '.number_format($total,2));
    redirect('ventas.php');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error',$e->getMessage());
    redirect('index.php');
}
