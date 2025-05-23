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

$mensaje = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validaciones
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errores[] = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no es válido.";
    }

    if (strlen($password) < 9 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)) {
        $errores[] = "La contraseña debe tener al menos 9 caracteres, una mayúscula, una minúscula, un número y un símbolo.";
    }

    // Validación duplicados
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        $errores[] = "El correo o nombre de usuario ya está en uso.";
    }

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, login_attempts, is_blocked) VALUES (?, ?, ?, 0, 0)");
        $stmt->execute([$username, $email, $hash]);
        $mensaje = "Usuario creado exitosamente.";
    }
}

$stmt = $pdo->query("SELECT id, username, email, login_attempts, is_blocked, created_at FROM users ORDER BY id DESC");
$admins = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/modales.css">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
</head>
<body>
<div class="container">
    <h1>Lista de Administradores</h1>

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

    <p><button onclick="document.getElementById('modal').style.display='block'"> + Crear nuevo admin</button></p>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Intentos</th>
            <th>Bloqueado</th>
            <th>Creado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= (int)$admin['id'] ?></td>
                <td><?= escape($admin['username']) ?></td>
                <td><?= escape($admin['email']) ?></td>
                <td><?= (int)$admin['login_attempts'] ?></td>
                <td><?= $admin['is_blocked'] ? 'Sí' : 'No' ?></td>
                <td><?= escape($admin['created_at']) ?></td>
                <td class="actions">
                    <a href="editar.php?id=<?= $admin['id'] ?>">✏️</a>

                    <form action="acciones.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este admin?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                        <button style="background:none; border:none; cursor:pointer;">🗑️</button>
                    </form>

                    <?php if ($admin['is_blocked']): ?>
                        <form action="acciones.php" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="unblock">
                            <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                            <button style="background:none; border:none; cursor:pointer;">🔓</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="../dashboard.php">← Volver al Panel</a></p>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal').style.display='none'">&times;</span>
        <h2>Nuevo Administrador</h2>
        <form method="POST" action="">
            <input type="hidden" name="crear_admin" value="1">
            <input type="text" name="username" placeholder="Nombre de usuario" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña segura" required>
            <button type="submit">Crear</button>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) {
        if (e.target.id === 'modal') {
            document.getElementById('modal').style.display = 'none';
        }
    }
</script>
</body>
</html>
