<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


// Tráfico por hora (0 a 23)
$stmt = $pdo->query("
    SELECT HOUR(created_at) as hora, COUNT(*) as pedidos
    FROM orders
    GROUP BY hora
    ORDER BY hora ASC
");
$horas = $stmt->fetchAll();

$labels_horas = [];
$valores_horas = [];
foreach ($horas as $row) {
    $labels_horas[] = $row['hora'] . ":00";
    $valores_horas[] = $row['pedidos'];
}

// Pedidos por día de la semana (0 = Domingo)
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$stmt = $pdo->query("
    SELECT DAYOFWEEK(created_at) - 1 as dia, COUNT(*) as total
    FROM orders
    GROUP BY dia
    ORDER BY dia
");
$semanal = $stmt->fetchAll();
?>

<link rel="stylesheet" href="stats/stats.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="stats-container" style="flex-direction: column;">

    <!-- Tráfico por Hora -->
    <div class="stats-card" style="width: 100%;">
        <h4>⏰ Pedidos por Hora</h4>
        <canvas id="traficoHoras"></canvas>
    </div>

    <!-- Pedidos por Día -->
    <div class="stats-card" style="width: 100%;">
        <h4>📅 Pedidos por Día de la Semana</h4>
        <table>
            <thead>
                <tr><th>Día</th><th>Pedidos</th></tr>
            </thead>
            <tbody>
                <?php foreach ($semanal as $row): ?>
                    <tr>
                        <td><?= $dias[$row['dia']] ?></td>
                        <td><?= (int)$row['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const ctxHoras = document.getElementById('traficoHoras').getContext('2d');
new Chart(ctxHoras, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_horas) ?>,
        datasets: [{
            label: 'Pedidos por hora',
            data: <?= json_encode($valores_horas) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
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
