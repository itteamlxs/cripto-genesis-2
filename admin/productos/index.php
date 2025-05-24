<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $nombre = trim($_POST['name']);
    $descripcion = trim($_POST['description']);
    $precio = (float) $_POST['price'];
    $imagen_ruta = 'images/default.jpg';

    if (strlen($nombre) < 3) {
        $errores[] = "El nombre del producto debe tener al menos 3 caracteres.";
    }

    if ($precio <= 0) {
        $errores[] = "El precio debe ser mayor a cero.";
    }

    // Procesar imagen
    if (!empty($_FILES['image']['name'])) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir la imagen. Código: " . $_FILES['image']['error'];
        } else {
            $nombre_imagen = basename($_FILES['image']['name']);
            $destino = __DIR__ . '/../../public/images/' . $nombre_imagen;
            $tipo = strtolower(pathinfo($destino, PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($tipo, $permitidos)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destino)) {
                    $imagen_ruta = 'images/' . $nombre_imagen;
                } else {
                    $errores[] = "Error al mover la imagen.";
                }
            } else {
                $errores[] = "Tipo de imagen no permitido. Solo JPG, PNG o GIF.";
            }
        }
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $precio, $imagen_ruta]);
        $mensaje = "Producto creado correctamente.";
    }
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$productos = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/global-admin.css">
</head>
<body>
    <div class="container">
        <h1>🛍️ Gestión de Productos</h1>

        <?php if ($mensaje): ?>
            <div class="success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="error">
                <?php foreach ($errores as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Acciones principales -->
        <div class="admin-actions">
            <button onclick="openModal()" class="admin-btn">
                ➕ Agregar Nuevo Producto
            </button>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?= count($productos) ?></h3>
                <p>Total Productos</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format(array_sum(array_column($productos, 'price')), 2) ?></h3>
                <p>Valor Total Inventario</p>
            </div>
        </div>

        <!-- Tabla de productos -->
        <div class="table-container">
            <?php if (empty($productos)): ?>
                <div class="empty-state">
                    <h3>📦 No hay productos registrados</h3>
                    <p>Comienza agregando tu primer producto usando el botón de arriba.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><strong>#<?= (int) $p['id'] ?></strong></td>
                                <td>
                                    <img src="../../public/<?= escape($p['image']) ?>" 
                                         alt="<?= escape($p['name']) ?>" 
                                         width="60" height="60" 
                                         style="object-fit: cover;">
                                </td>
                                <td><strong><?= escape($p['name']) ?></strong></td>
                                <td>
                                    <?php 
                                    $desc = escape($p['description']);
                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                    ?>
                                </td>
                                <td><span class="price">$<?= number_format($p['price'], 2) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="editar.php?id=<?= $p['id'] ?>" class="action-btn edit-btn" title="Editar producto">
                                        ✏️
                                    </a>
                                    <form action="acciones.php" method="POST" style="display:inline;" 
                                          onsubmit="return confirm('⚠️ ¿Estás seguro de eliminar este producto?\n\nEsta acción no se puede deshacer.');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Eliminar producto">
                                            🗑️
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Navegación -->
        <div class="navigation">
            <a href="../dashboard.php" class="back-link">
                ← Volver al Panel Principal
            </a>
        </div>
    </div>

    <!-- Modal de creación de producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>➕ Nuevo Producto</h2>
            
            <form method="POST" action="" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="crear_producto" value="1">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="form-group">
                    <label for="modal_name">Nombre del Producto</label>
                    <input type="text" id="modal_name" name="name" placeholder="Ej: Smartphone Samsung Galaxy" required>
                </div>

                <div class="form-group">
                    <label for="modal_description">Descripción</label>
                    <textarea id="modal_description" name="description" placeholder="Descripción detallada del producto (opcional)" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="modal_price">Precio ($)</label>
                    <input type="number" id="modal_price" step="0.01" name="price" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="modal_image">Imagen del Producto</label>
                    <input type="file" id="modal_image" name="image" accept=".jpg,.jpeg,.png,.gif">
                    <small style="color: #666; margin-top: 0.5rem; display: block;">
                        Formatos: JPG, PNG, GIF. Máximo 5MB. Si no seleccionas una imagen, se usará una predeterminada.
                    </small>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 1rem;">
                    💾 Crear Producto
                </button>
            </form>
        </div>
    </div>

    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .table-container {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            color: #999;
            margin-bottom: 1rem;
        }

        .price {
            font-weight: 700;
            color: #28a745;
            font-size: 1.1rem;
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 0.25rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background: #ffc107;
            color: #212529;
        }

        .edit-btn:hover {
            background: #e0a800;
            transform: scale(1.1);
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .navigation {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            .action-btn {
                padding: 0.375rem;
                font-size: 0.875rem;
            }
        }
    </style>

    <script>
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
            document.getElementById('productForm').reset();
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Validación en tiempo real del precio
        document.getElementById('modal_price').addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value <= 0) {
                this.setCustomValidity('El precio debe ser mayor a 0');
            } else {
                this.setCustomValidity('');
            }
        });

        // Previsualización de imagen (opcional)
        document.getElementById('modal_image').addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 5 * 1024 * 1024) { // 5MB
                alert('⚠️ La imagen es muy grande. El tamaño máximo es 5MB.');
                this.value = '';
            }
        });
    </script>
</body>
</html>