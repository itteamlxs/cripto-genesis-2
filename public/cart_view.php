<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


// Generar token CSRF
$csrf_token = generate_csrf_token();

// Obtener carrito
$cart = $_SESSION['cart'] ?? [];

// Calcular total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

</head>
<body>
    <h1>🛒 Carrito de Compras</h1>

    <?php if (empty($cart)): ?>
        <p>Tu carrito está vacío. <a href="index.php">Volver al catálogo</a></p>
    <?php else: ?>
        <form action="update_cart.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $item): ?>
                        <tr>
                            <td><?= escape($item['name']) ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                <input type="number" name="quantities[<?= $id ?>]" value="<?= (int)$item['quantity'] ?>" min="1">
                            </td>
                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            <td>
                                <input type="checkbox" name="remove[]" value="<?= $id ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><strong>Total: $<?= number_format($total, 2) ?></strong></p>

            <button type="submit">Actualizar Carrito</button>
            <a href="checkout.php"><button type="button">Proceder al Pago</button></a>
        </form>
        <p><a href="index.php">← Seguir comprando</a></p>
    <?php endif; ?>
    
</body>
</html>
