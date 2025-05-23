<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';



if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Acceso no autorizado');
}

$action = $_POST['action'] ?? '';
$product_id = (int) ($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
}

header('Location: index.php');
exit;
