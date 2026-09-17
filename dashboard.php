<?php require 'config/config.php';
require_login();
$u = user();
switch ($u['rol'] ?? '') {
    case 'cliente':
        redirect(BASE_URL . 'cliente/index.php');
    case 'administrador':
        redirect(BASE_URL . 'admin/index.php');
    case 'cajero':
        redirect(BASE_URL . 'cajero/index.php');
    case 'repartidor':
        redirect(BASE_URL . 'repartidor/index.php');
    case 'empleado':
        redirect(BASE_URL . 'index.php');
    default:
        flash('error', 'Rol de usuario no válido.');
        redirect(BASE_URL . 'logout.php');
}
