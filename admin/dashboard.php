<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$usuario = escape($_SESSION['admin_username'] ?? 'Administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/tab.css">
    <link rel="stylesheet" href="assets/css/reportes.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
</head>
<body>
<div class="container">
    <h1>Bienvenido, <?= $usuario ?></h1>

    <div class="admin-actions">
        <a href="productos/index.php" class="admin-btn">🛍️ Gestionar Productos</a>
        <a href="usuarios/index.php" class="admin-btn">👤 Administrar Usuarios</a>
        <a href="pedidos/index.php" class="admin-btn">📦 Ver Pedidos</a>
        <a href="#" class="admin-btn" onclick="document.getElementById('modalReporte').style.display='block'; return false;">📊 Reportes Dinámicos</a>
        <p><a href="logout.php" class="admin-btn">Cerrar sesión</a></p>
    </div>

    <!-- Tabs -->
    <div class="tab-container">
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="pedidos">Pedidos</button>
            <button class="tab-btn" data-tab="ranking">Ranking</button>
            <button class="tab-btn" data-tab="resumen">Resumen</button>
            <button class="tab-btn" data-tab="trafico">Tráfico</button>
        </div>

        <div class="tab-panel active" id="pedidos">
            <?php include __DIR__ . '/stats/pedidos.php'; ?>
        </div>
        <div class="tab-panel" id="ranking">
            <?php include __DIR__ . '/stats/ranking.php'; ?>
        </div>
        <div class="tab-panel" id="resumen">
            <?php include __DIR__ . '/stats/resumen.php'; ?>
        </div>
        <div class="tab-panel" id="trafico">
            <?php include __DIR__ . '/stats/trafico.php'; ?>
        </div>
    </div>

    <script>
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(tab).classList.add('active');
        });
    });
    </script>

</div>
<?php include __DIR__ . '/reportes/generador.php'; ?>
<?php require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique?>
</body>
</html>
