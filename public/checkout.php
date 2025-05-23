<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


use Stripe\Stripe;
use Stripe\Checkout\Session;

// Obtener carrito
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: cart_view.php');
    exit;
}

// Configurar Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Preparar items para Stripe
$line_items = [];
$total = 0;

foreach ($cart as $item) {
    $line_items[] = [
        'price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => $item['name']],
            'unit_amount' => (int)($item['price'] * 100),
        ],
        'quantity' => $item['quantity'],
    ];
    $total += $item['price'] * $item['quantity'];
}

// Crear sesión de Stripe con recolección de datos
$checkout_session = Session::create([
    'payment_method_types' => ['card'],
    'line_items' => $line_items,
    'mode' => 'payment',
    'customer_creation' => 'always',
    'phone_number_collection' => ['enabled' => true],
    'billing_address_collection' => 'required',
    'success_url' => 'http://localhost/cripto-genesis2/public/success.php',
    'cancel_url' => 'http://localhost/cripto-genesis2/public/cart_view.php',
]);

// Guardar productos en order_cart temporalmente
$stmt = $pdo->prepare("
    INSERT INTO order_cart (order_id, product_id, product_name, quantity, price, image)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($cart as $item) {
    $stmt->execute([
        0,
        $item['id'],
        $item['name'],
        $item['quantity'],
        $item['price'],
        $item['image'] ?? 'images/default.jpg'
    ]);
}

// Redirigir al checkout
header("Location: " . $checkout_session->url);
exit;
