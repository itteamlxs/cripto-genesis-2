<?php
// cart.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

// 1. Validar token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Token CSRF inválido');
}

// 2. Validar y sanitizar ID del producto
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    http_response_code(400);
    exit('ID de producto inválido');
}

// 3. Verificar que el producto exista en la base de datos
$stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Producto no encontrado');
}

// 4. Inicializar carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 5. Agregar al carrito (sumar cantidad si ya existe)
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += 1;
} else {
    $_SESSION['cart'][$product_id] = [
        'id'       => $product['id'],
        'name'     => $product['name'],
        'price'    => $product['price'],
        'quantity' => 1
    ];
}

// 6. Redireccionar al catálogo
header('Location: index.php');
exit;
