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
    <title>Productos</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/modales.css">
</head>
<body>
<div class="container">
    <h1>Productos</h1>

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

    <p><button onclick="document.getElementById('modal').style.display='block'">➕ Agregar producto</button></p>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Imagen</th>
            <th>Creado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?= (int) $p['id'] ?></td>
                <td><?= escape($p['name']) ?></td>
                <td>$<?= number_format($p['price'], 2) ?></td>
                <td><img src="../../public/<?= escape($p['image']) ?>" width="50"></td>
                <td><?= escape($p['created_at']) ?></td>
                <td class="actions">
                    <a href="editar.php?id=<?= $p['id'] ?>">Editar</a>
                    <form action="acciones.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar producto?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button style="background:none; border:none; cursor:pointer;">Borrar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="../dashboard.php">← Volver al Panel</a></p>
</div>

<!-- Modal de creación -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal').style.display='none'">&times;</span>
        <h2>+ Nuevo Producto</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="crear_producto" value="1">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <input type="text" name="name" placeholder="Nombre del producto" required>
            <textarea name="description" placeholder="Descripción (opcional)"></textarea>
            <input type="number" step="0.01" name="price" placeholder="Precio" required>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif">
            <button type="submit">Crear producto</button>
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
