<?php require_once __DIR__.'/../config/config.php'; ?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= e($title??'LicoFast') ?></title>
        <link rel="stylesheet" href="<?= e(BASE_URL . 'assets/css/style.css') ?>">
    </head>
    <body class="rol-<?= e(rol_actual()) ?>">
        <header class="top">
            <a class="brand" href="<?= e(BASE_URL . (user() ? 'dashboard.php' : 'index.php')) ?>">
                <span class="brand-dot"></span>Lico<span>Fast</span>
            </a>
            <nav>
                <?php if(user()): ?>
                    <?php foreach (menu_rol() as $texto => $url): ?>
                        <a href="<?= e($url) ?>"><?= e($texto) ?></a>
                    <?php endforeach; ?>
                    <span class="chip-rol"><?=e(user()['nombre'])?> · <?=e(nombre_rol(rol_actual()))?></span>
                    <a class="salir" href="<?= e(BASE_URL . 'logout.php') ?>">Cerrar sesión</a>
                <?php else: ?>
                    <a href="<?= e(BASE_URL . 'login.php') ?>">Iniciar sesión</a>
                    <a href="<?= e(BASE_URL . 'registro.php') ?>">Crear cuenta</a>
                <?php endif;?>
            </nav>
        </header>
        <?php if (user()): ?>
            <div class="franja-rol"><span><?= e(nombre_rol(rol_actual())) ?></span> · Sistema de gestión LicoFast</div>
        <?php endif; ?>
        <main class="container">
            <?php if($m=get_flash('ok')):?>
                <div class="alert ok"><?=e($m)?></div>
            <?php endif;?>
            <?php if($m=get_flash('error')):?>
                <div class="alert err"><?=e($m)?></div>
            <?php endif;?>
