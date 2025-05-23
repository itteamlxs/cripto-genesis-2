<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Obtener y validar ID del usuario
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error_message'] = 'ID de usuario inválido.';
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'Usuario no encontrado.';
    header('Location: index.php');
    exit;
}

$mensaje = '';
$errores = [];

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errores[] = "Token CSRF inválido.";
    }

    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $new_password = $_POST['password'] ?? '';
    $desbloquear = isset($_POST['desbloquear']);

    // Validaciones
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errores[] = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico inválido.";
    }

    // Verificar duplicados
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $stmt->execute([$email, $username, $id]);
    if ($stmt->fetch()) {
        $errores[] = "El correo o nombre de usuario ya está en uso por otro usuario.";
    }

    // Validar nueva contraseña si se proporciona
    if (!empty($new_password)) {
        if (strlen($new_password) < 9 ||
            !preg_match('/[A-Z]/', $new_password) ||
            !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) ||
            !preg_match('/[\W_]/', $new_password)) {
            $errores[] = "La nueva contraseña debe tener al menos 9 caracteres, una mayúscula, una minúscula, un número y un símbolo.";
        }
    }

    // Actualizar usuario si no hay errores
    if (empty($errores)) {
        try {
            $query = "UPDATE users SET email = ?, username = ?";
            $params = [$email, $username];

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
            
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            $errores[] = "Error interno al actualizar el usuario.";
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-usuarios.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <h1>✏️ Editar Usuario</h1>
            <p class="subtitle">Modificar información del administrador</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> → 
            <a href="index.php">Usuarios</a> → 
            <span>Editar</span>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="alert alert-error">
                ❌ Se encontraron los siguientes errores:
                <?php foreach ($errores as $error): ?>
                    <br>• <?= htmlspecialchars($error) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Edición -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Información del Usuario</h2>
            </div>
            <div class="card-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="form-group">
                        <label for="username">Nombre de Usuario</label>
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            class="form-control" 
                            value="<?= htmlspecialchars($user['username']) ?>" 
                            required
                            maxlength="50"
                            pattern="[a-zA-Z0-9_]{3,50}"
                            title="Solo letras, números y guiones bajos. Entre 3 y 50 caracteres."
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            class="form-control" 
                            value="<?= htmlspecialchars($user['email']) ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Nueva Contraseña</label>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-control" 
                            placeholder="Dejar vacío para mantener la contraseña actual"
                            minlength="9"
                        >
                        <small style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                            Mínimo 9 caracteres con mayúscula, minúscula, número y símbolo
                        </small>
                    </div>

                    <?php if ((int)$user['is_blocked'] === 1): ?>
                        <div class="checkbox-group">
                            <input type="checkbox" id="desbloquear" name="desbloquear" value="1">
                            <label for="desbloquear">🔓 Desbloquear este usuario</label>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">
                            💾 Guardar Cambios
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            ← Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Información del Sistema</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>ID:</strong> <?= (int)$user['id'] ?>
                    </div>
                    <div>
                        <strong>Creado:</strong> <?= htmlspecialchars($user['created_at']) ?>
                    </div>
                    <div>
                        <strong>Intentos de login:</strong> 
                        <span class="<?= $user['login_attempts'] > 0 ? 'status-blocked' : 'status-active' ?>">
                            <?= (int)$user['login_attempts'] ?>
                        </span>
                    </div>
                    <div>
                        <strong>Estado:</strong> 
                        <span class="<?= $user['is_blocked'] ? 'status-blocked' : 'status-active' ?>">
                            <?= $user['is_blocked'] ? '🔒 Bloqueado' : '✅ Activo' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación del formulario en tiempo real
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if (password.length > 0) {
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSymbol = /[\W_]/.test(password);
                const minLength = password.length >= 9;
                
                if (!hasUpper || !hasLower || !hasNumber || !hasSymbol || !minLength) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 9 caracteres, una mayúscula, una minúscula, un número y un símbolo.');
                    return false;
                }
            }
        });

        // Confirmación antes de enviar
        document.getElementById('editForm').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que deseas guardar los cambios?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>