<?php require '../config/config.php';
require_role(['administrador']);
$title = 'Usuarios y roles';
$filtro = $_GET['rol'] ?? '';
$roles = ['cliente','cajero','repartidor','administrador','empleado'];
if ($filtro && in_array($filtro,$roles,true)) {
    $st = $pdo->prepare("SELECT id,nombre,apellido,ci,usuario,rol,cargo,telefono,activo FROM usuarios WHERE rol=? ORDER BY id DESC");
    $st->execute([$filtro]);
    $users = $st->fetchAll();
} else {
    $users = $pdo->query("SELECT id,nombre,apellido,ci,usuario,rol,cargo,telefono,activo FROM usuarios ORDER BY id DESC")->fetchAll();
}
require '../includes/header.php';
?>
<div class="seccion-titulo">
    <h1>Usuarios y asignación de roles</h1>
    <a class="btn" href="usuario.php">+ Nuevo usuario</a>
</div>
<p class="small">Aquí el administrador da de alta y asigna el rol de <strong>Cajero</strong>, <strong>Repartidor (Delivery)</strong>, <strong>Cliente</strong> o <strong>Administrador</strong>.</p>

<div class="card">
    <h2>Filtrar por rol</h2>
    <div class="actions" style="margin-top:0">
        <a class="btn <?= $filtro?'secondary':'' ?>" href="usuarios.php">Todos</a>
        <?php foreach($roles as $r): ?>
            <a class="btn <?= $filtro===$r?'':'secondary' ?>" href="usuarios.php?rol=<?= e($r) ?>"><?= e(nombre_rol($r)) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Listado de usuarios</h2>
    <div class="tabla-scroll">
    <table class="table">
        <tr><th>#</th><th>Nombre</th><th>CI</th><th>Usuario</th><th>Rol</th><th>Cargo</th><th>Acciones</th></tr>
        <?php foreach($users as $u): ?>
            <tr>
                <td><?= e($u['id']) ?></td>
                <td>
                    <?= e($u['nombre'].' '.$u['apellido']) ?>
                    <?= empty($u['activo']) ? ' <span class="badge inactivo">Inactivo</span>' : '' ?>
                </td>
                <td><?= e($u['ci']) ?></td>
                <td><?= e($u['usuario']) ?></td>
                <td><span class="badge rol"><?= e(nombre_rol($u['rol'])) ?></span></td>
                <td class="small"><?= e($u['cargo']) ?></td>
                <td>
                    <a class="btn mini secondary" href="usuario.php?id=<?= e($u['id']) ?>">Editar</a>
                    <?php if ((int)$u['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                        <a class="btn mini danger" href="usuario.php?id=<?= e($u['id']) ?>&delete=1" onclick="return confirm('¿Eliminar o desactivar este usuario?')">Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
