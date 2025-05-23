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
    <title>Editar producto</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="container">
    <h1>✏️ Editar Producto</h1>

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

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <label>Nombre:</label>
        <input type="text" name="name" value="<?= escape($product['name']) ?>" required>

        <label>Descripción:</label>
        <textarea name="description"><?= escape($product['description']) ?></textarea>

        <label>Precio:</label>
        <input type="number" step="0.01" name="price" value="<?= escape($product['price']) ?>" required>

        <label>Imagen actual:</label><br>
        <img src="../../public/<?= escape($product['image']) ?>" width="100"><br><br>

        <label>Cambiar imagen:</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif">

        <button type="submit">Guardar cambios</button>
    </form>

    <p><a href="index.php">← Volver a productos</a></p>
</div>
</body>
</html>
