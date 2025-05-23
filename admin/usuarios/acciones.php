<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Acceso no autorizado');
}

$action = $_POST['action'] ?? '';
$user_id = (int) ($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: index.php');
    exit;
}

switch ($action) {
    case 'delete':
        // Prevención: no puede eliminarse a sí mismo
        if ($user_id === $_SESSION['admin_id']) {
            header('Location: index.php');
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        break;

    case 'unblock':
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0, login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        break;

    case 'edit':
        // Para simplificar, puedes redirigir a un archivo editar.php,
        // o implementar un modal editable más adelante.
        header("Location: editar.php?id=$user_id");
        exit;

    default:
        // Acción no reconocida
        break;
}

header('Location: index.php');
exit;
