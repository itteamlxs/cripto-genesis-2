<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$errores = [];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errores[] = 'CSRF token inválido.';
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];
    $image = $product['image']; // Default to existing image

    if (strlen($name) < 3) {
        $errores[] = "El nombre debe tener al menos 3 caracteres.";
    }

    if ($price <= 0) {
        $errores[] = "El precio debe ser mayor a 0.";
    }

    if (!empty($_FILES['image']['name'])) {
        $nombre_imagen = basename($_FILES['image']['name']);
        $destino = __DIR__ . '/../../public/images/' . $nombre_imagen;
        $tipo = strtolower(pathinfo($destino, PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($tipo, $permitidos)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destino)) {
                $image = 'images/' . $nombre_imagen;
            } else {
                $errores[] = "Error al subir la imagen.";
            }
        } else {
            $errores[] = "Solo se permiten imágenes JPG, PNG o GIF.";
        }
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $image, $id]);
        $mensaje = "Producto actualizado correctamente.";

        // refrescar
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/global-admin.css">
</head>
<body>
    <div class="container">
        <h1>✏️ Editar Producto</h1>

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

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="form-group">
                    <label for="name">Nombre del Producto</label>
                    <input type="text" id="name" name="name" value="<?= escape($product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" placeholder="Descripción del producto (opcional)"><?= escape($product['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Precio ($)</label>
                    <input type="number" id="price" step="0.01" name="price" value="<?= escape($product['price']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Imagen Actual</label>
                    <div class="current-image">
                        <img src="../../public/<?= escape($product['image']) ?>" alt="Imagen actual" style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Cambiar Imagen</label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif">
                    <small style="color: #666; margin-top: 0.5rem; display: block;">
                        Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.
                    </small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Cambios
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        ❌ Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="navigation">
            <a href="index.php" class="back-link">
                ← Volver a Productos
            </a>
        </div>
    </div>

    <style>
        .form-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .current-image {
            margin-top: 0.5rem;
            padding: 1rem;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            text-align: center;
            background: #fff;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .form-actions .btn {
            flex: 1;
            min-width: 150px;
        }

        .navigation {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>