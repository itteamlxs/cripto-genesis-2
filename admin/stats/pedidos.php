<?php
require_once __DIR__ . '/../../includes/db.php';



// Obtener pedidos pendientes y enviados
$pendientes = $pdo->prepare("
    SELECT o.id, o.customer_name, o.customer_email, o.phone, o.address, o.total_amount, s.status,
        (SELECT GROUP_CONCAT(CONCAT(oi.product_name, ' x', oi.quantity) SEPARATOR ', ')
         FROM order_items oi WHERE oi.order_id = o.id) AS productos
    FROM orders o
    LEFT JOIN order_status s ON o.id = s.order_id
    WHERE s.status = 'pendiente'
    ORDER BY o.created_at DESC
");
$pendientes->execute();

$enviados = $pdo->prepare("
    SELECT o.id, o.customer_name, o.customer_email, o.phone, o.address, o.total_amount, s.status,
        (SELECT GROUP_CONCAT(CONCAT(oi.product_name, ' x', oi.quantity) SEPARATOR ', ')
         FROM order_items oi WHERE oi.order_id = o.id) AS productos
    FROM orders o
    LEFT JOIN order_status s ON o.id = s.order_id
    WHERE s.status = 'enviado'
    ORDER BY o.created_at DESC
");
$enviados->execute();
?>

<link rel="stylesheet" href="stats/stats.css">

<div class="stats-container">
    <div class="stats-card" style="flex: 1 1 100%;">
        <h4>Pedidos Pendientes</h4>
        <?php foreach ($pendientes as $p): ?>
            <div style="margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: .5rem;">
                <strong>Cliente:</strong> <?= htmlspecialchars($p['customer_name']) ?><br>
                <strong>Cliente email:</strong> <?= htmlspecialchars($p['customer_email']) ?><br>
                <strong>Teléfono:</strong> <?= htmlspecialchars($p['phone']) ?><br>
                <strong>Dirección:</strong> <?= htmlspecialchars($p['address']) ?><br>
                <strong>Monto:</strong> $<?= number_format($p['total_amount'], 2) ?><br>
                <strong>Productos:</strong> <?= htmlspecialchars($p['productos']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="stats-card" style="flex: 1 1 100%;">
        <h4>Pedidos Enviados</h4>
        <?php foreach ($enviados as $p): ?>
            <div style="margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: .5rem;">
                <strong>Cliente:</strong> <?= htmlspecialchars($p['customer_name']) ?><br>
                <strong>Cliente email:</strong> <?= htmlspecialchars($p['customer_email']) ?><br>
                <strong>Teléfono:</strong> <?= htmlspecialchars($p['phone']) ?><br>
                <strong>Dirección:</strong> <?= htmlspecialchars($p['address']) ?><br>
                <strong>Monto:</strong> $<?= number_format($p['total_amount'], 2) ?><br>
                <strong>Productos:</strong> <?= htmlspecialchars($p['productos']) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
