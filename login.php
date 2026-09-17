<?php require 'config/config.php';
if (user()) redirect('dashboard.php');
$title = 'Iniciar sesión'; 
require 'includes/header.php'; 
?>
<div class="formbox">
    <h2>Iniciar sesión</h2>
    <form method="post" action="login_procesar.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="field">
            <label>Usuario</label>
            <input name="usuario" required maxlength="50" autocomplete="username">
        </div>
        <div class="field">
            <label>Contraseña</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn">Iniciar sesión</button>
    </form>
    <p style="margin-top:16px;">
        ¿No tienes cuenta? <a href="registro.php">Crear una nueva cuenta</a>
    </p>
</div>
<?php require 'includes/footer.php'; ?>
