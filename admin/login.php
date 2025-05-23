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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo</title>
    <link rel="stylesheet" href="assets/css/login-dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
</head>
<body class="login-page">
    <div class="login-background">
        <div class="login-overlay"></div>
        
        <!-- Animated Background Elements -->
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="shape shape-5"></div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Acceso Administrativo</h1>
                <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="alert-content">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="form-group">
                    <div class="input-container">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="email"
                            placeholder="Correo electrónico" 
                            required
                            class="form-input"
                            autocomplete="email"
                        >
                        <label for="email" class="input-label">Correo electrónico</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            placeholder="Contraseña" 
                            required
                            class="form-input"
                            autocomplete="current-password"
                        >
                        <label for="password" class="input-label">Contraseña</label>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <div class="remember-forgot">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            Recordar sesión
                        </label>
                        <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <span class="btn-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <div class="security-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Acceso seguro protegido con CSRF</span>
                </div>
            </div>
        </div>

        <!-- Login Info Panel -->
        <div class="login-info">
            <div class="info-content">
                <h2>Panel de Administración</h2>
                <div class="info-features">
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <h3>Analytics Avanzados</h3>
                        <p>Monitorea el rendimiento de tu plataforma en tiempo real</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users-cog"></i>
                        <h3>Gestión Completa</h3>
                        <p>Administra usuarios, productos y pedidos desde un solo lugar</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Seguridad Máxima</h3>
                        <p>Protección avanzada con autenticación y control de acceso</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.classList.add('loading');
            
            // Disable the button to prevent double submission
            loginBtn.disabled = true;
            
            // Re-enable after 3 seconds in case of slow response
            setTimeout(() => {
                loginBtn.disabled = false;
                loginBtn.classList.remove('loading');
            }, 3000);
        });

        // Input focus effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
            
            // Check if input has value on page load
            if (input.value !== '') {
                input.parentElement.classList.add('focused');
            }
        });

        // Floating shapes animation
        function createFloatingShapes() {
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const duration = 10 + (index * 2);
                const delay = index * 0.5;
                shape.style.animationDuration = `${duration}s`;
                shape.style.animationDelay = `${delay}s`;
            });
        }

        // Initialize animations
        window.addEventListener('load', () => {
            createFloatingShapes();
            
            // Add entrance animation to login card
            setTimeout(() => {
                document.querySelector('.login-card').classList.add('animate-in');
                document.querySelector('.login-info').classList.add('animate-in');
            }, 100);
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                const form = document.getElementById('loginForm');
                const inputs = form.querySelectorAll('input[required]');
                const currentIndex = Array.from(inputs).indexOf(e.target);
                
                if (currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    form.submit();
                }
            }
        });

        // Error message auto-hide
        const errorAlert = document.querySelector('.alert-error');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.opacity = '0';
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>