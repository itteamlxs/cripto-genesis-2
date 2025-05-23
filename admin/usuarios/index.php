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

$mensaje = '';
$errores = [];

// Procesar mensajes de sesión
if (isset($_SESSION['success_message'])) {
    $mensaje = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $errores[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Procesamiento del formulario para crear nuevo admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_admin'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errores[] = "Token CSRF inválido.";
    } else {
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
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, login_attempts, is_blocked) VALUES (?, ?, ?, 0, 0)");
                $stmt->execute([$username, $email, $hash]);
                $mensaje = "Usuario creado exitosamente.";
            } catch (PDOException $e) {
                error_log("Error al crear usuario: " . $e->getMessage());
                $errores[] = "Error interno al crear el usuario.";
            }
        }
    }
}

// Obtener lista de usuarios con paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter === 'blocked') {
    $where_conditions[] = "is_blocked = 1";
} elseif ($status_filter === 'active') {
    $where_conditions[] = "is_blocked = 0";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de registros
$count_query = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Obtener usuarios de la página actual
$query = "SELECT id, username, email, login_attempts, is_blocked, created_at FROM users $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-usuarios.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <h1>👥 Administrar Usuarios</h1>
            <p class="subtitle">Gestión completa de administradores del sistema</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> → <span>Usuarios</span>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="alert alert-error">
                ❌ Se encontraron errores:
                <?php foreach ($errores as $error): ?>
                    <br>• <?= htmlspecialchars($error) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Controles superiores -->
        <div class="admin-card">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <!-- Búsqueda y filtros -->
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="🔍 Buscar por usuario o email..." 
                            value="<?= htmlspecialchars($search) ?>"
                            style="min-width: 250px;"
                        >
                        <select name="status" class="form-control" style="width: auto;">
                            <option value="">Todos los estados</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Activos</option>
                            <option value="blocked" <?= $status_filter === 'blocked' ? 'selected' : '' ?>>Bloqueados</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filtrar</button>
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <a href="index.php" class="btn btn-link">Limpiar</a>
                        <?php endif; ?>
                    </form>

                    <!-- Botón crear -->
                    <button onclick="openModal()" class="btn btn-primary">
                        ➕ Nuevo Administrador
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="admin-card">
                <div class="card-body" style="text-align: center;">
                    <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <?= count($users) ?>
                    </h3>
                    <p style="color: var(--text-secondary);">Usuarios en esta página</p>
                </div>
            </div>
            <div class="admin-card">
                <div class="card-body" style="text-align: center;">
                    <h3 style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <?= $total_users ?>
                    </h3>
                    <p style="color: var(--text-secondary);">Total de usuarios</p>
                </div>
            </div>
            <div class="admin-card">
                <div class="card-body" style="text-align: center;">
                    <h3 style="color: var(--danger-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <?php
                        $blocked_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_blocked = 1");
                        echo $blocked_stmt->fetchColumn();
                        ?>
                    </h3>
                    <p style="color: var(--text-secondary);">Usuarios bloqueados</p>
                </div>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Lista de Administradores</h2>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Intentos</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                    🔍 No se encontraron usuarios con los filtros aplicados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="<?= $user['is_blocked'] ? 'user-blocked' : 'user-active' ?>">
                                    <td><strong><?= (int)$user['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                            <span style="color: var(--primary-color); font-size: 0.75rem;">(Tú)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="<?= $user['login_attempts'] > 0 ? 'status-blocked' : 'status-active' ?>">
                                            <?= (int)$user['login_attempts'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_blocked']): ?>
                                            <span class="status-blocked">🔒 Bloqueado</span>
                                        <?php else: ?>
                                            <span class="status-active">✅ Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td class="actions">
                                        <!-- Editar -->
                                        <a href="editar.php?id=<?= $user['id'] ?>" 
                                           class="action-btn action-edit" 
                                           title="Editar usuario">
                                            ✏️
                                        </a>

                                        <!-- Eliminar (no puede eliminarse a sí mismo) -->
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <form action="acciones.php" method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('⚠️ ¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="action-btn action-delete" title="Eliminar usuario">
                                                    🗑️
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Desbloquear/Bloquear -->
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <?php if ($user['is_blocked']): ?>
                                                <form action="acciones.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="unblock">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="action-btn action-unlock" title="Desbloquear usuario">
                                                        🔓
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="acciones.php" method="POST" style="display:inline;"
                                                      onsubmit="return confirm('¿Bloquear este usuario?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="block">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="action-btn action-delete" title="Bloquear usuario">
                                                        🔒
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card">
                <div class="card-body">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                               class="btn btn-secondary">← Anterior</a>
                        <?php endif; ?>

                        <span>Página <?= $page ?> de <?= $total_pages ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                               class="btn btn-secondary">Siguiente →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navegación -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../dashboard.php" class="btn btn-link">← Volver al Panel Principal</a>
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Nuevo Administrador</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="createForm">
                    <input type="hidden" name="crear_admin" value="1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group">
                        <label for="new_username">Nombre de Usuario</label>
                        <input 
                            type="text" 
                            id="new_username"
                            name="username" 
                            class="form-control" 
                            placeholder="Nombre de usuario único" 
                            required
                            minlength="3"
                            maxlength="50"
                            pattern="[a-zA-Z0-9_]{3,50}"
                            title="Solo letras, números y guiones bajos. Entre 3 y 50 caracteres."
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="new_email">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="new_email"
                            name="email" 
                            class="form-control" 
                            placeholder="correo@ejemplo.com" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Contraseña</label>
                        <input 
                            type="password" 
                            id="new_password"
                            name="password" 
                            class="form-control" 
                            placeholder="Contraseña segura" 
                            required
                            minlength="9"
                        >
                        <small style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                            Mínimo 9 caracteres con mayúscula, minúscula, número y símbolo
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            ✅ Crear Usuario
                        </button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('createModal').style.display = 'block';
            document.getElementById('new_username').focus();
        }

        function closeModal() {
            document.getElementById('createModal').style.display = 'none';
            document.getElementById('createForm').reset();
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Validación del formulario
        document.getElementById('createForm').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[\W_]/.test(password);
            const minLength = password.length >= 9;
            
            if (!hasUpper || !hasLower || !hasNumber || !hasSymbol || !minLength) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 9 caracteres, una mayúscula, una minúscula, un número y un símbolo.');
                return false;
            }
        });

        // Escapar para cerrar modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-cerrar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>