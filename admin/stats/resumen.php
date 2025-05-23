<?php
// dashboard.php ya tiene session_start y verificación

require_once __DIR__ . '/../../includes/db.php';



// Totales
$total_pedidos = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_ventas = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn();

// Pedidos por día
$stmt = $pdo->query("
    SELECT DATE(created_at) as fecha, COUNT(*) as pedidos
    FROM orders
    GROUP BY fecha
    ORDER BY fecha DESC
    LIMIT 7
");

$fechas = [];
$valores = [];

foreach ($stmt as $row) {
    $fechas[] = $row['fecha'];
    $valores[] = $row['pedidos'];
}
?>

<link rel="stylesheet" href="stats/stats.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="stats-container">
    <div class="stats-card">
        <h4>Total de Pedidos</h4>
        <p><strong><?= (int)$total_pedidos ?></strong></p>
    </div>
    <div class="stats-card">
        <h4>Total de Ventas</h4>
        <p><strong>$<?= number_format($total_ventas, 2) ?></strong></p>
    </div>
</div>

<div class="chart-box">
    <canvas id="pedidosChart"></canvas>
</div>

<script>
const ctx = document.getElementById('pedidosChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_reverse($fechas)) ?>,
        datasets: [{
            label: 'Pedidos por día',
            data: <?= json_encode(array_reverse($valores)) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: '#36A2EB',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
