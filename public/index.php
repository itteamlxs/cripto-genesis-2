<?php

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


$csrf_token = generate_csrf_token();

try {
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    http_response_code(500);
    exit('Error al obtener productos.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo Seguro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

</head>
<body>
    <h1>Catálogo de Productos</h1>
    <div class="catalog">
        <?php foreach ($products as $product): ?>
            <div class="product">
                <img src="<?= escape($product['image']) ?>" alt="<?= escape($product['name']) ?>">
                <h2><?= escape($product['name']) ?></h2>
                <p><?= escape($product['description']) ?></p>
                <p><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                <form action="cart.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <button type="submit">Agregar al carrito</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
