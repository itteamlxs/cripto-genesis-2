<?php
function logAdminAction($accion) {
    global $pdo;

    $admin_id = $_SESSION['admin_id'] ?? null;
    $admin_username = $_SESSION['admin_username'] ?? 'desconocido';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'sin IP';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';

    $stmt = $pdo->prepare("
        INSERT INTO AU_administradores (admin_id, admin_username, accion, ip, navegador)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$admin_id, $admin_username, $accion, $ip, $navegador]);
}

function logClienteAction($accion, $email = null) {
    global $pdo;

    $session_id = session_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'sin IP';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';

    $stmt = $pdo->prepare("
        INSERT INTO AU_clientes_tienda (session_id, customer_email, accion, ip, navegador)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$session_id, $email, $accion, $ip, $navegador]);
}
