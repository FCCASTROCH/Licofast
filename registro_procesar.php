<?php require 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('registro.php');
}
check_csrf();

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$ci = trim($_POST['ci'] ?? '');
$fn = $_POST['fecha_nacimiento'] ?? '';
$u = trim($_POST['usuario'] ?? '');
$p = $_POST['password'] ?? '';
$tel = trim($_POST['telefono'] ?? '');

if (!$nombre || !$apellido || !$ci || !$fn || !$u || strlen($p) < 8 || empty($_POST['terminos'])) {
    flash('error', 'Completa todos los campos, usa una contraseña de al menos 8 caracteres y acepta los términos.');
    redirect('registro.php');
}

try {
    $dob = new DateTime($fn);
    $today = new DateTime('today');
    if ($dob > $today) {
        $age = 0;
    } else {
        $age = $dob->diff($today)->y;
    }
} catch (Exception $e) {
    $age = 0;
} 

if ($age < 18) {
    flash('error', 'Debes tener 18 años o más para registrarte.');
    redirect('registro.php');
} 

$st = $pdo->prepare('SELECT id FROM usuarios WHERE usuario=? OR ci=?');
$st->execute([$u, $ci]);
if ($st->fetch()) {
    flash('error', 'El nombre de usuario o CI ya está registrado.');
    redirect('registro.php');
}

$hash = hash_password($p);
try {
    $st = $pdo->prepare("INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,cargo,telefono,acepta_terminos,activo) VALUES(?,?,?,?,?,?,?,?,?,?,1,1)");
    $st->execute([$nombre, $apellido, $ci, $fn, $age, $u, $hash, 'cliente', 'Cliente', $tel]);
    flash('ok', 'Cuenta creada correctamente. Ahora puedes iniciar sesión.');
    redirect('login.php');
} catch (PDOException $e) {
    flash('error', 'Error al registrar la cuenta: datos duplicados o inválidos.');
    redirect('registro.php');
}

