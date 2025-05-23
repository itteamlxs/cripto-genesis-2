<?php
// admin/reportes/generador.php (NUEVO con campos cruzados entre orders + order_items)

$campos_disponibles = [
    'customer_name' => ['tabla' => 'orders', 'alias' => 'Nombre del Cliente'],
    'customer_email' => ['tabla' => 'orders', 'alias' => 'Correo'],
    'created_at' => ['tabla' => 'orders', 'alias' => 'Fecha de Compra'],
    'total_amount' => ['tabla' => 'orders', 'alias' => 'Monto Total'],
    'product_name' => ['tabla' => 'order_items', 'alias' => 'Producto'],
    'quantity' => ['tabla' => 'order_items', 'alias' => 'Cantidad'],
    'price' => ['tabla' => 'order_items', 'alias' => 'Precio'],
    'order_id' => ['tabla' => 'order_items', 'alias' => 'ID de Orden']
];
?>
<div class="modal" id="modalReporte">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modalReporte').style.display='none'">&times;</span>
        <h2>Generar Reporte Unificado</h2>

        <form id="form-reporte" action="reportes/procesar_reporte.php" method="POST">
            <p><strong>Selecciona las columnas a exportar:</strong></p>
            <div class="columnas-wrapper">
                <?php foreach ($campos_disponibles as $campo => $info): ?>
                    <label>
                        <input type="checkbox" name="columnas[]" value="<?= $campo ?>">
                        <?= htmlspecialchars($info['alias']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <label for="desde">Desde:</label>
            <input type="date" name="desde" required>

            <label for="hasta">Hasta:</label>
            <input type="date" name="hasta" required>

            <button type="submit">Descargar CSV</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('form-reporte').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input[name=\"columnas[]\"]:checked');
        const desde = document.querySelector('input[name=\"desde\"]');
        const hasta = document.querySelector('input[name=\"hasta\"]');
    
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('⚠️ Debes seleccionar al menos una columna para generar el reporte.');
            return;
        }
    
        if (!desde.value || !hasta.value) {
            e.preventDefault();
            alert('⚠️ Debes seleccionar un rango de fechas.');
            return;
        }
    });
</script>

