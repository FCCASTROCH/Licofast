<?php require '../config/config.php';
require_role(['administrador']);
$id = (int)($_GET['id'] ?? 0);
$u = ['nombre'=>'','apellido'=>'','ci'=>'','usuario'=>'','rol'=>'cajero','cargo'=>'','telefono'=>'','fecha_nacimiento'=>'','activo'=>1];

if ($id) {
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE id=?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) { flash('error','Usuario no encontrado.'); redirect('usuarios.php'); }
    $u = $found;
}

if (isset($_GET['delete']) && $id) {
    if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
        flash('error','No puedes eliminar tu propia cuenta en sesión activa.');
        redirect('usuarios.php');
    }
    try {
        $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
        flash('ok','Usuario eliminado correctamente.');
    } catch (PDOException $e) {
        $pdo->prepare('UPDATE usuarios SET activo=0 WHERE id=?')->execute([$id]);
        flash('ok','El usuario tiene compras asociadas, por lo que fue desactivado.');
    }
    redirect('usuarios.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name  = trim($_POST['nombre'] ?? '');
    $last  = trim($_POST['apellido'] ?? '');
    $ci    = trim($_POST['ci'] ?? '');
    $usr   = trim($_POST['usuario'] ?? '');
    $rol   = in_array($_POST['rol'] ?? '', ['cliente','cajero','administrador','repartidor','empleado'], true) ? $_POST['rol'] : 'cliente';
    $cargo = trim($_POST['cargo'] ?? '') ?: nombre_rol($rol);
    $tel   = trim($_POST['telefono'] ?? '');
    $fn    = $_POST['fecha_nacimiento'] ?? '2000-01-01';
    $active= isset($_POST['activo']) ? 1 : 0;
    $pw    = $_POST['password'] ?? '';

    try { $edad = (new DateTime($fn))->diff(new DateTime('today'))->y; } catch (Throwable $e) { $edad = 18; $fn = '2000-01-01'; }

    if (!$name || !$last || !$ci || !$usr) {
        flash('error','Completa todos los campos obligatorios.');
        redirect('usuario.php'.($id ? "?id=$id" : ''));
    }
    if ($rol === 'cliente' && $edad < 18) {
        flash('error','Un cliente debe tener 18 años o más.');
        redirect('usuario.php'.($id ? "?id=$id" : ''));
    }

    $checkSql = 'SELECT id FROM usuarios WHERE (usuario=? OR ci=?)'.($id ? ' AND id != ?' : '');
    $chk = $pdo->prepare($checkSql);
    $chk->execute($id ? [$usr,$ci,$id] : [$usr,$ci]);
    if ($chk->fetch()) {
        flash('error','El nombre de usuario o CI ya se encuentra registrado.');
        redirect('usuario.php'.($id ? "?id=$id" : ''));
    }

    if ($id) {
        $sql  = 'UPDATE usuarios SET nombre=?,apellido=?,ci=?,usuario=?,rol=?,cargo=?,telefono=?,fecha_nacimiento=?,edad=?,activo=?';
        $args = [$name,$last,$ci,$usr,$rol,$cargo,$tel,$fn,$edad,$active];
        if ($pw !== '') {
            if (strlen($pw) < 8) { flash('error','La nueva contraseña debe tener al menos 8 caracteres.'); redirect("usuario.php?id=$id"); }
            $sql .= ',password=?'; $args[] = hash_password($pw);
        }
        $sql .= ' WHERE id=?'; $args[] = $id;
        $pdo->prepare($sql)->execute($args);
    } else {
        if (strlen($pw) < 8) { flash('error','La contraseña debe tener al menos 8 caracteres.'); redirect('usuario.php'); }
        $pdo->prepare('INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,cargo,telefono,acepta_terminos,activo) VALUES(?,?,?,?,?,?,?,?,?,?,1,?)')
            ->execute([$name,$last,$ci,$fn,$edad,$usr,hash_password($pw),$rol,$cargo,$tel,$active]);
    }
    flash('ok','Usuario guardado con el rol '.nombre_rol($rol).'.');
    redirect('usuarios.php');
}

$title = 'Usuario';
require '../includes/header.php';
?>
<div class="formbox">
    <h2><?= $id ? 'Editar' : 'Nuevo' ?> usuario</h2>
    <p class="small">Selecciona el rol para definir qué podrá hacer la persona: el <strong>cajero</strong> registra ventas, el <strong>repartidor</strong> entrega pedidos y el <strong>cliente</strong> compra en la tienda.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="two">
            <div class="field"><label>Nombre *</label><input name="nombre" required value="<?= e($u['nombre']) ?>"></div>
            <div class="field"><label>Apellido *</label><input name="apellido" required value="<?= e($u['apellido']) ?>"></div>
        </div>
        <div class="two">
            <div class="field"><label>CI *</label><input name="ci" required value="<?= e($u['ci']) ?>"></div>
            <div class="field"><label>Teléfono</label><input name="telefono" value="<?= e($u['telefono'] ?? '') ?>"></div>
        </div>
        <div class="two">
            <div class="field"><label>Usuario *</label><input name="usuario" required value="<?= e($u['usuario']) ?>"></div>
            <div class="field"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" value="<?= e($u['fecha_nacimiento'] ?: '2000-01-01') ?>"></div>
        </div>
        <div class="field">
            <label>Rol asignado *</label>
            <select name="rol">
                <option value="cliente"       <?= $u['rol']=='cliente'?'selected':'' ?>>Cliente</option>
                <option value="cajero"        <?= $u['rol']=='cajero'?'selected':'' ?>>Cajero</option>
                <option value="repartidor"    <?= $u['rol']=='repartidor'?'selected':'' ?>>Repartidor (Delivery)</option>
                <option value="administrador" <?= $u['rol']=='administrador'?'selected':'' ?>>Administrador</option>
                <option value="empleado"      <?= $u['rol']=='empleado'?'selected':'' ?>>Empleado</option>
            </select>
        </div>
        <div class="field"><label>Cargo / función</label><input name="cargo" value="<?= e($u['cargo'] ?? '') ?>" placeholder="Cajero de turno, delivery zona norte, etc."></div>
        <div class="field">
            <label>Contraseña <?= $id ? '(dejar vacía para conservar)' : '*' ?></label>
            <input type="password" name="password" <?= $id ? '' : 'required' ?> minlength="8">
        </div>
        <label><input type="checkbox" name="activo" <?= !empty($u['activo'])?'checked':'' ?>> Usuario activo</label>
        <div class="actions">
            <button class="btn">Guardar usuario</button>
            <a class="btn secondary" href="usuarios.php">Cancelar</a>
        </div>
    </form>
</div>
<?php require '../includes/footer.php'; ?>
