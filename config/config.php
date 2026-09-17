<?php
// Ruta base del proyecto. Se detecta automáticamente según la carpeta (LicoFast, LicoFast1, etc.).
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $parts = explode('/', trim($scriptDir, '/'));
    $base = !empty($parts[0]) ? '/' . $parts[0] . '/' : '/';
    define('BASE_URL', $base);
}
if (session_status()===PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax']);
    session_start();
}
require_once __DIR__.'/database.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function redirect($url){header('Location: '.$url); exit;}
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(){ if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(403);die('Solicitud no valida.');} }
function user(){return $_SESSION['user']??null;}
function require_login(){if(!user()) redirect(BASE_URL . 'login.php');}
function require_role($roles){require_login(); $u=user(); if(!$u || !in_array($u['rol']??'',(array)$roles,true)){http_response_code(403);die('Acceso denegado.');}}
function flash($key,$msg){$_SESSION['flash'][$key]=$msg;}
function get_flash($key){$m=$_SESSION['flash'][$key]??null; unset($_SESSION['flash'][$key]); return $m;}
function cart_count(){return array_sum($_SESSION['cart']??[]);}

/* ---------- Helpers de roles y estados ---------- */
function rol_actual(){ $u=user(); return $u['rol'] ?? 'invitado'; }

function nombre_rol(string $rol): string {
    return [
        'administrador' => 'Administrador',
        'cajero'        => 'Cajero',
        'cliente'       => 'Cliente',
        'repartidor'    => 'Repartidor (Delivery)',
        'empleado'      => 'Empleado',
    ][$rol] ?? ucfirst($rol);
}

function etiqueta_estado(string $estado): string {
    return [
        'pendiente'    => 'Pendiente de entrega',
        'pagada'       => 'Pagada (mostrador)',
        'en_camino'    => 'En camino',
        'entregada'    => 'Entregada',
        'no_entregado' => 'No entregado',
    ][$estado] ?? ucfirst($estado);
}

function etiqueta_pago(string $pago): string {
    return ['pendiente'=>'Pago pendiente','pagado'=>'Pagado','anulado'=>'Anulado'][$pago] ?? ucfirst($pago);
}

/* Devuelve el menú superior según el rol del usuario en sesión. */
function menu_rol(): array {
    switch (rol_actual()) {
        case 'administrador':
            return [
                'Panel'     => BASE_URL.'admin/index.php',
                'Productos' => BASE_URL.'admin/productos.php',
                'Usuarios'  => BASE_URL.'admin/usuarios.php',
                'Compras'   => BASE_URL.'admin/compras.php',
            ];
        case 'cajero':
            return [
                'Registrar venta' => BASE_URL.'cajero/index.php',
                'Mis ventas'      => BASE_URL.'cajero/ventas.php',
            ];
        case 'cliente':
            return [
                'Tienda'      => BASE_URL.'cliente/index.php',
                'Carrito'     => BASE_URL.'cliente/carrito.php',
                'Mis pedidos' => BASE_URL.'cliente/pedidos.php',
            ];
        case 'repartidor':
            return [
                'Entregas pendientes' => BASE_URL.'repartidor/index.php',
                'Historial'           => BASE_URL.'repartidor/historial.php',
            ];
        default:
            return [];
    }
}

function verify_password(string $password, string $stored): bool {
    if (str_starts_with($stored, 'PBKDF2-SHA256$')) {
        $p = explode('$', $stored);
        if (count($p) === 4) {
            $salt = base64_decode($p[2]);
            $h = hash_pbkdf2('sha256', $password, $salt, (int)$p[1], 32, true);
            return hash_equals(base64_decode($p[3]), $h);
        }
    }
    return password_verify($password, $stored);
}

function hash_password(string $password): string {
    $saltBytes = random_bytes(16);
    $saltB64 = base64_encode($saltBytes);
    $iterations = 120000;
    $hash = hash_pbkdf2('sha256', $password, $saltBytes, $iterations, 32, true);
    return 'PBKDF2-SHA256$' . $iterations . '$' . $saltB64 . '$' . base64_encode($hash);
}
