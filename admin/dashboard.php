<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$usuario = escape($_SESSION['admin_username'] ?? 'Administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/login-dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-tab="dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../public/index.php" class="nav-item" data-tab="nav-item">
                <i class="fas fa-store"></i>
                <span>Store</span>
            </a>
            <a href="productos/index.php" class="nav-item">
                <i class="fas fa-box"></i>
                <span>Productos</span>
            </a>
            <a href="usuarios/index.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
            <a href="pedidos/index.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Pedidos</span>
            </a>
            <a href="#" class="nav-item" onclick="openReportModal(); return false;">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Bienvenido, <?= $usuario ?></h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?= $usuario ?></span>
                </div>
            </div>
        </header>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card card-primary">
                <div class="card-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="card-content">
                    <h3>Productos</h3>
                    <p>Gestionar inventario</p>
                </div>
                <a href="productos/index.php" class="card-link"></a>
            </div>

            <div class="card card-success">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Usuarios</h3>
                    <p>Administrar usuarios</p>
                </div>
                <a href="usuarios/index.php" class="card-link"></a>
            </div>

            <div class="card card-warning">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-content">
                    <h3>Pedidos</h3>
                    <p>Gestionar pedidos</p>
                </div>
                <a href="pedidos/index.php" class="card-link"></a>
            </div>

            <div class="card card-info">
                <div class="card-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="card-content">
                    <h3>Reportes</h3>
                    <p>Análisis y estadísticas</p>
                </div>
                <a href="#" class="card-link" onclick="openReportModal(); return false;"></a>
            </div>
        </div>

        <!-- Stats Tabs -->
        <div class="stats-container">
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="pedidos">
                    <i class="fas fa-shopping-cart"></i>
                    Pedidos
                </button>
                <button class="tab-btn" data-tab="ranking">
                    <i class="fas fa-trophy"></i>
                    Ranking
                </button>
                <button class="tab-btn" data-tab="resumen">
                    <i class="fas fa-chart-pie"></i>
                    Resumen
                </button>
                <button class="tab-btn" data-tab="trafico">
                    <i class="fas fa-eye"></i>
                    Tráfico
                </button>
            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="pedidos">
                    <div class="panel-header">
                        <h3><i class="fas fa-shopping-cart"></i> Estadísticas de Pedidos</h3>
                    </div>
                    <div class="panel-body">
                        <?php include __DIR__ . '/stats/pedidos.php'; ?>
                    </div>
                </div>

                <div class="tab-panel" id="ranking">
                    <div class="panel-header">
                        <h3><i class="fas fa-trophy"></i> Ranking de Productos</h3>
                    </div>
                    <div class="panel-body">
                        <?php include __DIR__ . '/stats/ranking.php'; ?>
                    </div>
                </div>

                <div class="tab-panel" id="resumen">
                    <div class="panel-header">
                        <h3><i class="fas fa-chart-pie"></i> Resumen General</h3>
                    </div>
                    <div class="panel-body">
                        <?php include __DIR__ . '/stats/resumen.php'; ?>
                    </div>
                </div>

                <div class="tab-panel" id="trafico">
                    <div class="panel-header">
                        <h3><i class="fas fa-eye"></i> Análisis de Tráfico</h3>
                    </div>
                    <div class="panel-body">
                        <?php include __DIR__ . '/stats/trafico.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Reportes -->
    <div id="modalReporte" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReportModal()">&times;</span>
            <h2>📊 Generar Reporte Unificado</h2>

            <form id="form-reporte" action="reportes/procesar_reporte.php" method="POST">
                <p><strong>Selecciona las columnas a exportar:</strong></p>
                <div class="columnas-wrapper">
                    <?php
                    $campos_disponibles = [
                        'customer_name' => ['tabla' => 'orders', 'alias' => 'Nombre del Cliente'],
                        'customer_email' => ['tabla' => 'orders', 'alias' => 'Correo'],
                        'created_at' => ['tabla' => 'orders', 'alias' => 'Fecha de Compra'],
                        'total_amount' => ['tabla' => 'orders', 'alias' => 'Monto Total'],
                        'product_name' => ['tabla' => 'order_items', 'alias' => 'Producto'],
                        'quantity' => ['tabla' => 'order_items', 'alias' => 'Cantidad'],
                        'price' => ['tabla' => 'order_items', 'alias' => 'Precio'],
                        'order_id' => ['tabla' => 'order_items', 'alias' => 'ID de Orden']
                    ];
                    
                    foreach ($campos_disponibles as $campo => $info): 
                    ?>
                        <label>
                            <input type="checkbox" name="columnas[]" value="<?= $campo ?>">
                            <?= htmlspecialchars($info['alias']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <label for="desde">Desde:</label>
                <input type="date" name="desde" required>

                <label for="hasta">Hasta:</label>
                <input type="date" name="hasta" required>

                <button type="submit">📥 Descargar CSV</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        }

        // Tab Navigation
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                
                // Remove active class from all tabs and panels
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding panel
                btn.classList.add('active');
                document.getElementById(tab).classList.add('active');
            });
        });

        // Modal Functions
        function openReportModal() {
            const modal = document.getElementById('modalReporte');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Animate modal appearance
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }

        function closeReportModal() {
            const modal = document.getElementById('modalReporte');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('modalReporte').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportModal();
            }
        });

        // Form validation for reports
        document.getElementById('form-reporte').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="columnas[]"]:checked');
            const desde = document.querySelector('input[name="desde"]');
            const hasta = document.querySelector('input[name="hasta"]');
        
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('⚠️ Debes seleccionar al menos una columna para generar el reporte.');
                return;
            }
        
            if (!desde.value || !hasta.value) {
                e.preventDefault();
                alert('⚠️ Debes seleccionar un rango de fechas.');
                return;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Generando...';
            submitBtn.disabled = true;

            // Reset button after a delay (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Responsive sidebar
        function handleResize() {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.add('collapsed');
                document.querySelector('.main-content').classList.add('expanded');
            }
        }

        window.addEventListener('resize', handleResize);
        window.addEventListener('load', handleResize);

        // Add loading animation to cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('click', function() {
                if (!this.querySelector('.card-link').onclick) {
                    this.classList.add('loading');
                    setTimeout(() => {
                        this.classList.remove('loading');
                    }, 300);
                }
            });
        });

        // Initialize date inputs with default values
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            
            document.querySelector('input[name="hasta"]').value = today.toISOString().split('T')[0];
            document.querySelector('input[name="desde"]').value = lastMonth.toISOString().split('T')[0];
        });
    </script>

    <?php require_once __DIR__ . '/../includes/auditoria.php'; ?>
</body>
</html>