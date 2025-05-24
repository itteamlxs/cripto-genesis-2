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

// Obtener estado del pedido
$stmt = $pdo->prepare("SELECT status FROM order_status WHERE order_id = ?");
$stmt->execute([$order_id]);
$status_result = $stmt->fetch();
$status = $status_result['status'] ?? 'pendiente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= str_pad($pedido['id'], 4, '0', STR_PAD_LEFT) ?> - Detalles</title>
    <link rel="stylesheet" href="../assets/css/global-admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <span class="page-icon">📋</span>
                Detalles del Pedido #<?= str_pad($pedido['id'], 4, '0', STR_PAD_LEFT) ?>
            </h1>
            <div class="order-status-header">
                <?php 
                $statusClass = $status === 'enviado' ? 'status-sent' : 'status-pending';
                ?>
                <span class="status-badge large <?= $statusClass ?>">
                    <?= $status === 'enviado' ? '✅ Enviado' : '⏳ Pendiente' ?>
                </span>
            </div>
        </div>

        <div class="order-details">
            <!-- Información del Cliente -->
            <div class="details-section customer-section">
                <h2 class="section-title">
                    <span class="section-icon">👤</span>
                    Información del Cliente
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <label class="detail-label">Nombre:</label>
                        <span class="detail-value"><?= escape($pedido['customer_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Correo electrónico:</label>
                        <span class="detail-value email"><?= escape($pedido['customer_email']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Teléfono:</label>
                        <span class="detail-value phone"><?= escape($pedido['phone']) ?></span>
                    </div>
                    <div class="detail-item full-width">
                        <label class="detail-label">Dirección de envío:</label>
                        <span class="detail-value address"><?= escape($pedido['address']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Información del Pedido -->
            <div class="details-section order-section">
                <h2 class="section-title">
                    <span class="section-icon">📦</span>
                    Información del Pedido
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <label class="detail-label">Total:</label>
                        <span class="detail-value total-amount">$<?= number_format($pedido['total_amount'], 2) ?></span>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Fecha:</label>
                        <span class="detail-value date"><?= date('d/m/Y H:i:s', strtotime($pedido['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Productos del Pedido -->
            <div class="details-section products-section">
                <h2 class="section-title">
                    <span class="section-icon">🛒</span>
                    Productos del Pedido
                </h2>
                
                <?php if (empty($productos)): ?>
                    <div class="empty-products">
                        <p class="no-products-message">No hay productos asociados a este pedido.</p>
                    </div>
                <?php else: ?>
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead class="table-header">
                                <tr class="table-row header-row">
                                    <th class="table-cell header-cell">Producto</th>
                                    <th class="table-cell header-cell">Cantidad</th>
                                    <th class="table-cell header-cell">Precio Unitario</th>
                                    <th class="table-cell header-cell">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <?php 
                                $total_calculado = 0;
                                foreach ($productos as $item): 
                                    $subtotal = $item['quantity'] * $item['price'];
                                    $total_calculado += $subtotal;
                                ?>
                                    <tr class="table-row product-row">
                                        <td class="table-cell product-name">
                                            <span class="product-title"><?= escape($item['product_name']) ?></span>
                                        </td>
                                        <td class="table-cell quantity">
                                            <span class="quantity-badge"><?= (int) $item['quantity'] ?></span>
                                        </td>
                                        <td class="table-cell unit-price">
                                            <span class="price">$<?= number_format($item['price'], 2) ?></span>
                                        </td>
                                        <td class="table-cell subtotal">
                                            <span class="price subtotal-amount">$<?= number_format($subtotal, 2) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-footer">
                                <tr class="table-row total-row">
                                    <td colspan="3" class="table-cell total-label">
                                        <strong>Total del Pedido:</strong>
                                    </td>
                                    <td class="table-cell total-amount">
                                        <strong class="final-total">$<?= number_format($pedido['total_amount'], 2) ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-footer">
            <div class="footer-actions">
                <a href="index.php" class="btn btn-back">
                    <span class="btn-icon">←</span>
                    <span class="btn-text">Volver a Pedidos</span>
                </a>
                
                <div class="footer-actions-right">
                    <button onclick="window.print()" class="btn btn-print">
                        <span class="btn-icon">🖨️</span>
                        <span class="btn-text">Imprimir</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .page-footer, .btn {
                display: none !important;
            }
            .admin-container {
                box-shadow: none !important;
            }
        }
    </style>
</body>
</html>