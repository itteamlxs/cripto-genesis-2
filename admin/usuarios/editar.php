<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errores[] = "CSRF token inválido.";
    }

    $email = trim($_POST['email']);
    $new_password = $_POST['password'] ?? '';
    $desbloquear = isset($_POST['desbloquear']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo inválido.";
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $errores[] = "El correo ya está en uso por otro usuario.";
    }

    if (!empty($new_password)) {
        if (strlen($new_password) < 9 ||
            !preg_match('/[A-Z]/', $new_password) ||
            !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) ||
            !preg_match('/[\W_]/', $new_password)) {
            $errores[] = "La nueva contraseña debe tener al menos 9 caracteres, una mayúscula, una minúscula, un número y un símbolo.";
        }
    }

    if (empty($errores)) {
        $query = "UPDATE users SET email = ?";
        $params = [$email];

        if (!empty($new_password)) {
            $query .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        if ($desbloquear) {
            $query .= ", is_blocked = 0, login_attempts = 0";
        }

        $query .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $mensaje = "Usuario actualizado correctamente.";
        // Actualizar datos localmente
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="container">
    <h1>Editar Administrador</h1>

    <?php if ($mensaje): ?>
        <div class="success"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="error">
            <?php foreach ($errores as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <label>Correo electrónico:</label>
        <input type="email" name="email" value="<?= escape($user['email']) ?>" required>

        <label>Nueva contraseña (opcional):</label>
        <input type="password" name="password" placeholder="Dejar vacío para mantener la actual">

        <?php if ((int)$user['is_blocked'] === 1): ?>
            <label style="margin-top:10px;">
                <input type="checkbox" name="desbloquear" value="1">
                Desbloquear este usuario
            </label>
        <?php endif; ?>

        <button type="submit">Guardar cambios</button>
    </form>

    <p><a href="index.php">← Volver</a></p>
</div>
</body>
</html>
