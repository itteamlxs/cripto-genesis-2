<?php
session_start();

// Carrito
$cart = $_SESSION['cart'] ?? [];
$cart_count = 0;
foreach ($cart as $item) {
    $cart_count += $item['quantity'];
}

// Login
$is_logged_in = $_SESSION['admin_logged_in'] ?? false;
?>

<link rel="stylesheet" href="../public/assets/css/nav_bar.css">

<div class="navbar">
    <div class="brand">CriptoGenesis</div>
    <div class="toggle" onclick="document.querySelector('.navbar .menu').classList.toggle('show')">☰</div>
    <div class="menu">
        <a href="/cripto-genesis2/public/index.php">Inicio</a>
        <a href="/cripto-genesis2/public/cart_view.php">Carrito (<?= $cart_count ?>)</a>

        <?php if ($is_logged_in): ?>
            <a href="/cripto-genesis2/admin/dashboard.php">Panel Admin</a>
            <a href="/cripto-genesis2/admin/logout.php">Logout</a>
        <?php else: ?>
            <a href="/cripto-genesis2/admin/login.php">Login</a>
        <?php endif; ?>
    </div>
</div>
