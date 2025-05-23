<?php
// includes/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';

function enviarCorreoConfirmacion($destinatario, $monto) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmacion de compra - Cripto Genesis';
        $mail->Body    = "
            <h2>¡Gracias por tu compra!</h2>
            <p>Hemos recibido tu pago correctamente.</p>
            <p><strong>Total pagado:</strong> \$" . number_format($monto, 2) . "</p>
            <p>En breve procesaremos tu pedido.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

function enviarFacturaPDF($pedido_id, $email) {
    global $pdo;

    // Obtener datos
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$pedido_id]);
    $productos = $stmt->fetchAll();

    require_once __DIR__ . '/factura.php';
    $html = generarFacturaHTML($pedido, $productos);

    // Crear PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfContent = $dompdf->output();

    // Enviar con PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Factura de tu compra #" . $pedido_id;
        $mail->Body    = "Gracias por tu compra.
                        <p>En breve procesaremos tu pedido.</p> 
                        Se adjunta la factura en PDF.";

        // Adjuntar PDF como string
        $mail->addStringAttachment($pdfContent, "factura_{$pedido_id}.pdf");

        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar factura: " . $mail->ErrorInfo);
    }
}
