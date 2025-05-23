<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';




$errors = [];

// Si ya está logueado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Token CSRF inválido.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Todos los campos son obligatorios.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Usuario no encontrado.';
        } elseif ((int)$user['is_blocked'] === 1) {
            $errors[] = 'Cuenta bloqueada. Contacta a otro administrador.';
        } elseif (!password_verify($password, $user['password'])) {
            $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?")
                ->execute([$user['id']]);

            if ($user['login_attempts'] + 1 >= 3) {
                $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?")
                    ->execute([$user['id']]);
                $errors[] = 'Cuenta bloqueada por múltiples intentos fallidos.';
            } else {
                $restantes = 3 - ($user['login_attempts'] + 1);
                $errors[] = "Contraseña incorrecta. Intentos restantes: $restantes";
            }
        } else {
            $pdo->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?")
                ->execute([$user['id']]);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'] ?? 'Admin';

            header('Location: dashboard.php');
            exit;
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link rel="stylesheet" href="assets/css/style_login.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
</head>
<body>
<div class="container">
    <h1>Login Administrador</h1>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar sesión</button>
    </form>
</div>
</body>
</html>
