<?php require '../config/config.php';
require_role(['administrador']);

$id = (int)($_GET['id'] ?? 0);
$p = ['nombre' => '', 'descripcion' => '', 'precio' => '', 'stock' => 0, 'activo' => 1];

if ($id) {
    $st = $pdo->prepare('SELECT * FROM productos WHERE id=?');
    $st->execute([$id]);
    $found = $st->fetch();
    if ($found) {
        $p = $found;
    }
}

if (isset($_GET['delete']) && $id) {
    try {
        $pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]);
        flash('ok', 'Producto eliminado correctamente.');
    } catch (PDOException $e) {
        // Si tiene ventas asociadas no se puede eliminar por clave foránea; se desactiva
        $pdo->prepare('UPDATE productos SET activo=0 WHERE id=?')->execute([$id]);
        flash('ok', 'El producto tiene ventas asociadas, por lo que fue desactivado del catálogo.');
    }
    redirect('productos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['nombre'] ?? '');
    $desc = trim($_POST['descripcion'] ?? '');
    $price = max(0, (float)($_POST['precio'] ?? 0));
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $active = isset($_POST['activo']) ? 1 : 0;

    if (!$name) {
        flash('error', 'El nombre del producto es obligatorio.');
        redirect('producto.php' . ($id ? "?id=$id" : ''));
    }

    if ($id) {
        $st = $pdo->prepare('UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,activo=? WHERE id=?');
        $st->execute([$name, $desc, $price, $stock, $active, $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO productos(nombre,descripcion,precio,stock,activo) VALUES(?,?,?,?,?)');
        $st->execute([$name, $desc, $price, $stock, $active]);
    }
    flash('ok', 'Producto guardado correctamente.');
    redirect('productos.php');
}

$title = 'Producto';
require '../includes/header.php';
?>
<div class="formbox">
    <h2><?= $id ? 'Editar' : 'Nuevo' ?> producto</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="field">
            <label>Nombre</label>
            <input name="nombre" required value="<?= e($p['nombre']) ?>">
        </div>
        <div class="field">
            <label>Descripción</label>
            <textarea name="descripcion"><?= e($p['descripcion']) ?></textarea>
        </div>
        <div class="two">
            <div class="field">
                <label>Precio (Bs)</label>
                <input type="number" step="0.01" min="0" name="precio" required value="<?= e($p['precio']) ?>">
            </div>
            <div class="field">
                <label>Stock</label>
                <input type="number" min="0" name="stock" required value="<?= e($p['stock']) ?>">
            </div>
        </div>
        <label>
            <input type="checkbox" name="activo" <?= !empty($p['activo']) ? 'checked' : '' ?>> Activo
        </label>
        <div class="actions">
            <button class="btn">Guardar</button>
            <a class="btn secondary" href="productos.php">Cancelar</a>
        </div>
    </form>
</div>
<?php require '../includes/footer.php'; ?>
