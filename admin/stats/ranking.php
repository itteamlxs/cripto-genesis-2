<?php
require_once __DIR__ . '/../../includes/db.php';



// Producto más vendido
$stmt = $pdo->query("
    SELECT product_name, SUM(quantity) as total
    FROM order_items
    GROUP BY product_name
    ORDER BY total DESC
    LIMIT 1
");
$mas_vendido = $stmt->fetch();

// Producto menos vendido
$stmt = $pdo->query("
    SELECT product_name, SUM(quantity) as total
    FROM order_items
    GROUP BY product_name
    ORDER BY total ASC
    LIMIT 1
");
$menos_vendido = $stmt->fetch();

// Horas con más tráfico
$stmt = $pdo->query("
    SELECT HOUR(created_at) as hora, COUNT(*) as total
    FROM orders
    GROUP BY hora
    ORDER BY total DESC
");
$horas = $stmt->fetchAll();

$hora_mas = $horas[0];
$hora_menos = end($horas);
?>

<link rel="stylesheet" href="stats/stats.css">

<div class="stats-container">
    <div class="stats-card">
        <h4>🔝 Producto Más Vendido</h4>
        <p><strong><?= htmlspecialchars($mas_vendido['product_name']) ?></strong> (<?= $mas_vendido['total'] ?> ventas)</p>
    </div>

    <div class="stats-card">
        <h4>🔻 Producto Menos Vendido</h4>
        <p><strong><?= htmlspecialchars($menos_vendido['product_name']) ?></strong> (<?= $menos_vendido['total'] ?> ventas)</p>
    </div>

    <div class="stats-card">
        <h4>🕒 Hora Pico</h4>
        <p><strong><?= $hora_mas['hora'] ?>:00</strong> (<?= $hora_mas['total'] ?> pedidos)</p>
    </div>

    <div class="stats-card">
        <h4>🕓 Hora con Menos Tráfico</h4>
        <p><strong><?= $hora_menos['hora'] ?>:00</strong> (<?= $hora_menos['total'] ?> pedidos)</p>
    </div>
</div>
