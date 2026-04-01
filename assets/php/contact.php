<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Load Composer autoloader
$autoloader = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloader)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de configuración del servidor. Por favor escríbenos a info@koqoi.com']);
    exit;
}
require_once $autoloader;

// Load config
$config = require __DIR__ . '/config.php';

// Retrieve and sanitize inputs
$nombre       = trim(strip_tags($_POST['nombre']       ?? ''));
$email        = trim(strip_tags($_POST['email']        ?? ''));
$organizacion = trim(strip_tags($_POST['organizacion'] ?? ''));
$mensaje      = trim(strip_tags($_POST['mensaje']      ?? ''));

// Server-side validation
$errors = [];

if (mb_strlen($nombre) < 2) {
    $errors[] = 'El nombre es requerido.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El correo electrónico no es válido.';
}

if (mb_strlen($mensaje) < 10) {
    $errors[] = 'El mensaje es muy corto (mínimo 10 caracteres).';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Send via Gmail API using OAuth2 refresh token
try {
    $client = new Google\Client();
    $client->setApplicationName('Koqoi Mailer');
    $client->setClientId($config['oauth_client_id']);
    $client->setClientSecret($config['oauth_client_secret']);
    $client->addScope(Google\Service\Gmail::GMAIL_SEND);

    // Exchange the stored refresh token for a fresh access token
    $client->fetchAccessTokenWithRefreshToken($config['oauth_refresh_token']);

    $service = new Google\Service\Gmail($client);

    // Build RFC 2822 MIME message (multipart: HTML + plain text fallback)
    $org_line = empty($organizacion) ? '<em>No indicada</em>' : htmlspecialchars($organizacion, ENT_QUOTES, 'UTF-8');
    $nombre_h  = htmlspecialchars($nombre,  ENT_QUOTES, 'UTF-8');
    $email_h   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
    $mensaje_h = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));
    $fecha     = date('d/m/Y H:i') . ' UTC';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Inter,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <!-- Header -->
        <tr>
          <td style="background:#0f0f0f;padding:24px 32px;text-align:center;">
            <span style="font-size:22px;font-weight:800;color:#D4AF37;letter-spacing:0.05em;">KOQOI</span>
            <p style="margin:4px 0 0;color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:0.1em;text-transform:uppercase;">Nuevo mensaje de contacto</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:32px;">
            <p style="margin:0 0 24px;font-size:15px;color:#444;">Se ha recibido un nuevo mensaje a través del formulario de <strong>koqoi.com</strong>.</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e8e8;border-radius:6px;overflow:hidden;">
              <tr style="background:#fafafa;">
                <td style="padding:12px 16px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;width:140px;border-bottom:1px solid #e8e8e8;">Nombre</td>
                <td style="padding:12px 16px;font-size:15px;color:#1a1a1a;font-weight:600;border-bottom:1px solid #e8e8e8;">{$nombre_h}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e8e8e8;">Email</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e8e8e8;"><a href="mailto:{$email_h}" style="color:#D4AF37;font-size:15px;text-decoration:none;">{$email_h}</a></td>
              </tr>
              <tr style="background:#fafafa;">
                <td style="padding:12px 16px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e8e8e8;">Organización</td>
                <td style="padding:12px 16px;font-size:15px;color:#1a1a1a;border-bottom:1px solid #e8e8e8;">{$org_line}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;vertical-align:top;">Mensaje</td>
                <td style="padding:12px 16px;font-size:15px;color:#1a1a1a;line-height:1.7;">{$mensaje_h}</td>
              </tr>
            </table>
            <p style="margin:24px 0 0;font-size:13px;color:#aaa;">Recibido el {$fecha} · Responde directamente a este correo para contactar al remitente.</p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f9f9f9;border-top:1px solid #e8e8e8;padding:16px 32px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#bbb;">koqoi.com &mdash; Tecnología para organizaciones de salud</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    $plain  = "Nuevo mensaje de contacto - koqoi.com\n";
    $plain .= str_repeat('=', 40) . "\n";
    $plain .= "Nombre:       {$nombre}\n";
    $plain .= "Email:        {$email}\n";
    $plain .= "Organización: " . (empty($organizacion) ? 'No indicada' : $organizacion) . "\n";
    $plain .= "Fecha:        {$fecha}\n\n";
    $plain .= "Mensaje:\n{$mensaje}\n";

    $boundary = '----=_Part_' . md5(uniqid());
    $subject_encoded = '=?UTF-8?B?' . base64_encode('🔔 Nuevo contacto en Koqoi: ' . $nombre) . '?=';

    $mime  = "To: {$config['to_name']} <{$config['to_email']}>\r\n";
    $mime .= "From: {$config['from_name']} <{$config['from_email']}>\r\n";
    $mime .= "Reply-To: {$nombre} <{$email}>\r\n";
    $mime .= "Subject: {$subject_encoded}\r\n";
    $mime .= "X-Priority: 1\r\n";
    $mime .= "X-MSMail-Priority: High\r\n";
    $mime .= "Importance: High\r\n";
    $mime .= "MIME-Version: 1.0\r\n";
    $mime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $mime .= "\r\n";
    $mime .= "--{$boundary}\r\n";
    $mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mime .= $plain . "\r\n";
    $mime .= "--{$boundary}\r\n";
    $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mime .= $html . "\r\n";
    $mime .= "--{$boundary}--\r\n";
    $mime .= "\r\n";
    $mime .= $body_text;

    // Gmail API requires URL-safe base64 without padding
    $raw = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

    $gmailMessage = new Google\Service\Gmail\Message();
    $gmailMessage->setRaw($raw);

    $service->users_messages->send('me', $gmailMessage);

    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado! Nos pondremos en contacto contigo pronto.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo enviar el mensaje. Por favor escríbenos directamente a info@koqoi.com']);
}
