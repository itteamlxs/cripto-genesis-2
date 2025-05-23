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
    <title>Pedidos</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
</head>
<body>
<div class="container">
    <h1>Pedidos</h1>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Correo</th>
            <th>Monto</th>
            <th>Productos</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= escape($p['customer_email']) ?></td>
                <td>$<?= number_format($p['total_amount'], 2) ?></td>
                <td><?= (int)$p['items_count'] ?></td>
                <td><?= escape($p['created_at']) ?></td>
                <td><?= escape($p['status'] ?? 'pendiente') ?></td>
                <td class="actions">
                    <a href="ver.php?id=<?= $p['id'] ?>">Ver</a>

                    <?php if (($p['status'] ?? 'pendiente') === 'pendiente'): ?>
                        <form action="acciones.php" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="marcar_enviado">
                            <input type="hidden" name="order_id" value="<?= $p['id'] ?>">
                            <button type="submit">Enviar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="../dashboard.php">← Volver al panel</a></p>
</div>
</body>
</html>