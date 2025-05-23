<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


$cart = $_SESSION['cart'] ?? [];
$email = $_SESSION['stripe_email'] ?? null;

if (empty($cart) || !$email) {
    echo 'No hay productos o email.';
    exit;
}

// Buscar la última orden de este usuario (email)
$stmt = $pdo->prepare("SELECT id FROM orders WHERE customer_email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$email]);
$order = $stmt->fetch();

if (!$order) {
    echo 'No se encontró la orden.';
    exit;
}

$order_id = $order['id'];

// Insertar productos en order_items
foreach ($cart as $product) {
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_name, quantity, price, image)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_id,
        $product['name'],
        $product['quantity'],
        $product['price'],
        'images/default.jpg' // Se puede actualizar a la imagen del producto
    ]);
}

// Opcional: Borra el carrito si todo salió bien
unset($_SESSION['cart']);

echo 'Productos registrados con éxito.';
