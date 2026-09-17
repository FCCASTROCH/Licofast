<?php require 'config/config.php'; 
if (user()) redirect('dashboard.php');
$title = 'Crear cuenta';
require 'includes/header.php'; 
?>
<div class="formbox">
    <h2>Nueva cuenta</h2>
    <p class="small">Los datos se solicitan para validar que el comprador sea mayor de 18 años.</p>
    <form method="post" action="registro_procesar.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="two">
            <div class="field">
                <label>Nombre</label>
                <input name="nombre" required maxlength="80">
            </div>
            <div class="field">
                <label>Apellido</label>
                <input name="apellido" required maxlength="80">
            </div>
        </div>
        <div class="two">
            <div class="field">
                <label>CI</label>
                <input name="ci" required maxlength="30">
            </div>
            <div class="field">
                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" required>
            </div>
        </div>
        <div class="field">
            <label>Teléfono (para la entrega)</label>
            <input name="telefono" maxlength="30">
        </div>
        <div class="field">
            <label>Usuario</label>
            <input name="usuario" required maxlength="50" autocomplete="username">
        </div>
        <div class="field">
            <label>Contraseña</label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field">
            <label>
                <input type="checkbox" name="terminos" value="1" required> Acepto los términos y condiciones de LicoFast.
            </label>
        </div>
        <button class="btn">Registrar cuenta</button>
    </form>
    <p style="margin-top:16px;">
        ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
    </p>
</div>
<?php require 'includes/footer.php'; ?>
