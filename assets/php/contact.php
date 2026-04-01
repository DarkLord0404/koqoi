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

    // Build RFC 2822 MIME message
    $org_line = empty($organizacion) ? 'No indicada' : $organizacion;
    $body_text  = "Has recibido un nuevo mensaje desde el formulario de contacto de koqoi.com\n\n";
    $body_text .= "Nombre:        {$nombre}\n";
    $body_text .= "Email:         {$email}\n";
    $body_text .= "Organización:  {$org_line}\n\n";
    $body_text .= "Mensaje:\n{$mensaje}\n";

    $subject_encoded = '=?UTF-8?B?' . base64_encode('Nuevo mensaje de contacto - Koqoi') . '?=';

    $mime  = "To: {$config['to_name']} <{$config['to_email']}>\r\n";
    $mime .= "From: {$config['from_name']} <{$config['from_email']}>\r\n";
    $mime .= "Reply-To: {$nombre} <{$email}>\r\n";
    $mime .= "Subject: {$subject_encoded}\r\n";
    $mime .= "MIME-Version: 1.0\r\n";
    $mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: 8bit\r\n";
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
