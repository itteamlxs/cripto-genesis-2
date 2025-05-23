<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


// Vaciar el carrito
unset($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Exitoso</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>¡Gracias por tu compra!</h1>
    <p>Tu pago ha sido procesado correctamente por Stripe.</p>
    <p>Recibirás un correo de confirmación con los detalles.</p>

    <p><a href="index.php">← Volver al inicio</a></p>
</body>
</html>
