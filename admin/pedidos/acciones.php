<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Acceso no autorizado');
}

$action = $_POST['action'] ?? '';
$order_id = (int) ($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: index.php');
    exit;
}

if ($action === 'marcar_enviado') {
    // Insertar o actualizar estado
    $stmt = $pdo->prepare("SELECT id FROM order_status WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $existe = $stmt->fetch();

    if ($existe) {
        $stmt = $pdo->prepare("UPDATE order_status SET status = 'enviado' WHERE order_id = ?");
        $stmt->execute([$order_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO order_status (order_id, status) VALUES (?, 'enviado')");
        $stmt->execute([$order_id]);
    }
}

header('Location: index.php');
exit;
