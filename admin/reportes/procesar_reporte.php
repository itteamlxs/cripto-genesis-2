<?php
// admin/reportes/procesar_reporte.php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


$campos_disponibles = [
    'customer_name' => ['tabla' => 'o', 'alias' => 'Nombre del Cliente'],
    'customer_email' => ['tabla' => 'o', 'alias' => 'Correo'],
    'created_at' => ['tabla' => 'o', 'alias' => 'Fecha de Compra'],
    'total_amount' => ['tabla' => 'o', 'alias' => 'Monto Total'],
    'product_name' => ['tabla' => 'oi', 'alias' => 'Producto'],
    'quantity' => ['tabla' => 'oi', 'alias' => 'Cantidad'],
    'price' => ['tabla' => 'oi', 'alias' => 'Precio'],
    'order_id' => ['tabla' => 'oi', 'alias' => 'ID de Orden']
];

$columnas = $_POST['columnas'] ?? [];
$desde = $_POST['desde'] ?? null;
$hasta = $_POST['hasta'] ?? null;

if (empty($columnas) || !$desde || !$hasta) {
    exit('Faltan campos requeridos.');
}

$columnas_sql = [];
$cabeceras = [];

foreach ($columnas as $col) {
    if (!isset($campos_disponibles[$col])) continue;
    $columnas_sql[] = $campos_disponibles[$col]['tabla'] . "." . $col;
    $cabeceras[] = $campos_disponibles[$col]['alias'];
}

if (empty($columnas_sql)) {
    exit('Columnas inválidas.');
}

$sql = "SELECT " . implode(", ", $columnas_sql) . "
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$desde . " 00:00:00", $hasta . " 23:59:59"]);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reporte_personalizado.csv"');

$salida = fopen('php://output', 'w');
fputcsv($salida, $cabeceras);

foreach ($datos as $fila) {
    // Separar campos en 2 líneas si hay más de 4
    $linea1 = array_slice($fila, 0, 4);
    $linea2 = array_slice($fila, 4);
    fputcsv($salida, array_values($linea1));
    if (!empty($linea2)) {
        fputcsv($salida, array_values($linea2));
    }
}

fclose($salida);
exit;
