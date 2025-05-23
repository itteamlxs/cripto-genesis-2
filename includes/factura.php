<?php
function generarFacturaHTML($pedido, $productos) {
    ob_start();
    ?>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; }
    </style>
    <h1>Factura - Pedido #<?= $pedido['id'] ?></h1>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['customer_name']) ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($pedido['customer_email']) ?></p>
    <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['address']) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['phone']) ?></p>
    <p><strong>Fecha:</strong> <?= $pedido['created_at'] ?></p>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'], 2) ?></td>
                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Total: $<?= number_format($pedido['total_amount'], 2) ?></h3>

    <div class="footer">Gracias por tu compra. Esta factura ha sido generada automáticamente.</div>
    <?php
    return ob_get_clean();
}
