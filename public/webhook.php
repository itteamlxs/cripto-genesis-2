<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/auditoria.php'; // o la ruta que aplique


use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

// Configurar Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

// Leer evento
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (UnexpectedValueException $e) {
    http_response_code(400);
    exit('Payload inválido');
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit('Firma inválida');
}

// Manejar evento
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $stripe_session_id = $session->id;
    $customer_name = $session->customer_details->name ?? null;
    $email = $session->customer_details->email ?? null;
    $phone = $session->customer_details->phone ?? null;
    $address = $session->customer_details->address ?? null;

    $amount = $session->amount_total / 100;

    // Construir dirección
    $full_address = $address ? (
        ($address->line1 ?? '') . ', ' .
        ($address->city ?? '') . ', ' .
        ($address->postal_code ?? '') . ', ' .
        ($address->country ?? '')
    ) : null;

    try {
        // Insertar orden
        $stmt = $pdo->prepare("
            INSERT INTO orders (stripe_session_id, customer_email, total_amount, phone, address, customer_name)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $stripe_session_id,
            $email,
            $amount,
            $phone,
            $full_address,
            $customer_name
        ]);
        $order_id = $pdo->lastInsertId();

        // Insertar estado pendiente
        $stmt = $pdo->prepare("INSERT INTO order_status (order_id, status) VALUES (?, 'pendiente')");
        $stmt->execute([$order_id]);

    } catch (Exception $e) {
        error_log("Error al guardar orden o estado: " . $e->getMessage());
        http_response_code(500);
        exit('Error al guardar orden');
    }

    // Transferir productos desde order_cart a order_items
    try {
        $cart = $pdo->query("SELECT * FROM order_cart")->fetchAll();

        $stmt_item = $pdo->prepare("
            INSERT INTO order_items (order_id, product_name, quantity, price, image)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($cart as $item) {
            $stmt_item->execute([
                $order_id,
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['image']
            ]);
        }

        $pdo->exec("DELETE FROM order_cart");
    } catch (Exception $e) {
        error_log("Error al mover productos a order_items: " . $e->getMessage());
        http_response_code(500);
        exit('Error al guardar productos');
    }

    // Enviar correo
    if ($email) {
        enviarCorreoConfirmacion($email, $amount);
    }
    if ($order_id && $email) {
        enviarFacturaPDF($order_id, $email);
    }
    
    http_response_code(200);
    echo '✅ Orden completada';
    exit;
}

http_response_code(200);
echo 'Evento no manejado';
