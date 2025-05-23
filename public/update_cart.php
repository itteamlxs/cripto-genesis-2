<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Token CSRF inválido');
}

$cart = $_SESSION['cart'] ?? [];

// Actualizar cantidades
if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $id => $qty) {
        $id = (int)$id;
        $qty = (int)$qty;
        if ($qty > 0 && isset($cart[$id])) {
            $cart[$id]['quantity'] = $qty;
        }
    }
}

// Eliminar productos
if (isset($_POST['remove']) && is_array($_POST['remove'])) {
    foreach ($_POST['remove'] as $id) {
        $id = (int)$id;
        unset($cart[$id]);
    }
}

$_SESSION['cart'] = $cart;

header('Location: cart_view.php');
exit;
