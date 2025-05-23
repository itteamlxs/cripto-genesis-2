<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';



if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener pedido
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    echo "Pedido no encontrado.";
    exit;
}

// Obtener productos
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Pedido</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="container">
    <h1>Pedido #<?= $pedido['id'] ?></h1>
    <p><strong>Nombre:</strong> <?= escape($pedido['customer_name']) ?></p>
    <p><strong>Correo:</strong> <?= escape($pedido['customer_email']) ?></p>
    <p><strong>Monto total:</strong> $<?= number_format($pedido['total_amount'], 2) ?></p>
    <p><strong>Direccion:</strong> <?= escape($pedido['address']) ?></p>
    <p><strong>Contacto:</strong> <?= escape($pedido['phone']) ?></p>
    <p><strong>Fecha:</strong> <?= escape($pedido['created_at']) ?></p>

    <h3>🛒 Productos:</h3>
    <?php if (count($productos) === 0): ?>
        <p>No hay productos asociados.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $item): ?>
                <tr>
                    <td><?= escape($item['product_name']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php">← Volver a pedidos</a></p>
</div>
</body>
</html>
