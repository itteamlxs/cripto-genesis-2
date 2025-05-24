<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query("
    SELECT o.id, o.customer_email, o.total_amount, o.created_at,
           s.status, 
           (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS items_count
    FROM orders o
    LEFT JOIN order_status s ON o.id = s.order_id
    ORDER BY o.created_at ASC
");

$pedidos = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/global-admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <span class="page-icon">📦</span>
                Gestión de Pedidos
            </h1>
            <div class="page-stats">
                <span class="stat-badge">Total: <?= count($pedidos) ?></span>
            </div>
        </div>

        <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3 class="empty-title">No hay pedidos</h3>
                <p class="empty-description">Aún no se han realizado pedidos en la tienda.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead class="table-header">
                            <tr class="table-row header-row">
                                <th class="table-cell header-cell">ID</th>
                                <th class="table-cell header-cell">Cliente</th>
                                <th class="table-cell header-cell">Monto</th>
                                <th class="table-cell header-cell">Items</th>
                                <th class="table-cell header-cell">Fecha</th>
                                <th class="table-cell header-cell">Estado</th>
                                <th class="table-cell header-cell actions-header">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="table-row data-row">
                                    <td class="table-cell order-id">#<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="table-cell customer-info">
                                        <div class="customer-email"><?= escape($p['customer_email']) ?></div>
                                    </td>
                                    <td class="table-cell amount">
                                        <span class="price">$<?= number_format($p['total_amount'], 2) ?></span>
                                    </td>
                                    <td class="table-cell items-count">
                                        <span class="badge items-badge"><?= (int)$p['items_count'] ?></span>
                                    </td>
                                    <td class="table-cell date">
                                        <time class="order-date"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></time>
                                    </td>
                                    <td class="table-cell status">
                                        <?php 
                                        $status = $p['status'] ?? 'pendiente';
                                        $statusClass = $status === 'enviado' ? 'status-sent' : 'status-pending';
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $status === 'enviado' ? '✅ Enviado' : '⏳ Pendiente' ?>
                                        </span>
                                    </td>
                                    <td class="table-cell actions">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?= $p['id'] ?>" class="btn btn-view" title="Ver detalles">
                                                <span class="btn-icon">👁️</span>
                                                <span class="btn-text">Ver</span>
                                            </a>
                                            
                                            <?php if (($p['status'] ?? 'pendiente') === 'pendiente'): ?>
                                                <form action="acciones.php" method="POST" class="inline-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="action" value="marcar_enviado">
                                                    <input type="hidden" name="order_id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-send" title="Marcar como enviado" 
                                                            onclick="return confirm('¿Confirmar que el pedido ha sido enviado?')">
                                                        <span class="btn-icon">🚚</span>
                                                        <span class="btn-text">Enviar</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="page-footer">
            <a href="../dashboard.php" class="btn btn-back">
                <span class="btn-icon">←</span>
                <span class="btn-text">Volver al Panel</span>
            </a>
        </div>
    </div>
</body>
</html>