<?php
$title = 'LicoFast';
require 'includes/header.php';
?>
<section class="hero">
    <h1>🍷 Bienvenido a LicoFast</h1>
    <p>Compra y gestiona bebidas alcohólicas de forma rápida y responsable.</p>
    <p class="small">Debes ser mayor de 18 años para crear una cuenta y comprar.</p>
    <div class="actions" style="justify-content:center">
    <?php if (user()): ?>
        <a class="btn" href="dashboard.php">Ir a mi panel</a>
    <?php else: ?>
        <a class="btn" href="login.php">Iniciar sesión</a>
        <a class="btn secondary" href="registro.php">Crear nueva cuenta</a>
    <?php endif; ?>
    </div>
</section>

<div class="grid">
    <div class="card"><h2>👤 Administrador</h2><p class="small">Registra productos, crea usuarios y les asigna el rol de cajero, repartidor o cliente, y revisa todas las compras del sistema.</p></div>
    <div class="card"><h2>💵 Cajero</h2><p class="small">Inicia sesión y su única tarea es registrar las compras que se realizan en el mostrador.</p></div>
    <div class="card"><h2>🛒 Cliente</h2><p class="small">Mira el catálogo, agrega productos al carrito y registra el lugar de entrega. El pago queda pendiente hasta recibir el pedido.</p></div>
    <div class="card"><h2>🛵 Repartidor (Delivery)</h2><p class="small">Ve los pedidos pendientes y confirma si el producto fue entregado o no entregado; si no se entrega, los productos vuelven al inventario.</p></div>
</div>
<?php require 'includes/footer.php'; ?>
