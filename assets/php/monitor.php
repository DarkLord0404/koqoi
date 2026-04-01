<?php
// ============================================================
// Monitor diario del formulario de contacto — Koqoi
// Cron: 0 8 * * * php /var/www/html/assets/php/monitor.php
// ============================================================

require_once __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$url     = 'https://koqoi.com/assets/php/contact.php';
$payload = http_build_query([
    'nombre'       => 'Monitor Koqoi',
    'email'        => $config['to_email'],
    'organizacion' => 'Sistema de monitoreo',
    'mensaje'      => 'Prueba automática diaria del formulario de contacto. Si recibes este mensaje, el formulario está funcionando correctamente.',
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$fecha = date('Y-m-d H:i:s') . ' UTC';

if ($curlError) {
    $status  = 'ERROR';
    $detalle = "cURL error: {$curlError}";
} else {
    $data    = json_decode($response, true);
    $status  = ($httpCode === 200 && !empty($data['success'])) ? 'OK' : 'FAIL';
    $detalle = "HTTP {$httpCode} — " . ($response ?: '(sin respuesta)');
}

// Log local
$logFile = __DIR__ . '/../../logs/monitor.log';
@mkdir(dirname($logFile), 0750, true);
file_put_contents($logFile, "[{$fecha}] [{$status}] {$detalle}\n", FILE_APPEND | LOCK_EX);

// Si falla, enviar alerta por email via Gmail API
if ($status !== 'OK') {
    try {
        $client = new Google\Client();
        $client->setApplicationName('Koqoi Monitor');
        $client->setClientId($config['oauth_client_id']);
        $client->setClientSecret($config['oauth_client_secret']);
        $client->addScope(Google\Service\Gmail::GMAIL_SEND);
        $client->fetchAccessTokenWithRefreshToken($config['oauth_refresh_token']);

        $service = new Google\Service\Gmail($client);

        $subject = '=?UTF-8?B?' . base64_encode('⚠️ ALERTA: Formulario Koqoi no responde') . '?=';
        $body    = "ALERTA — Monitor Koqoi\n\n";
        $body   .= "Fecha:     {$fecha}\n";
        $body   .= "Estado:    {$status}\n";
        $body   .= "Detalle:   {$detalle}\n\n";
        $body   .= "El formulario de contacto en koqoi.com no está respondiendo correctamente.\n";
        $body   .= "Revisa el servidor de inmediato.\n";

        $mime  = "To: {$config['to_name']} <{$config['to_email']}>\r\n";
        $mime .= "From: {$config['from_name']} <{$config['from_email']}>\r\n";
        $mime .= "Subject: {$subject}\r\n";
        $mime .= "X-Priority: 1\r\nX-MSMail-Priority: High\r\nImportance: High\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $mime .= $body;

        $raw = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
        $msg = new Google\Service\Gmail\Message();
        $msg->setRaw($raw);
        $service->users_messages->send('me', $msg);

    } catch (Exception $e) {
        file_put_contents($logFile, "[{$fecha}] [ALERT_FAIL] No se pudo enviar alerta: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    }
}

echo "[{$fecha}] [{$status}] {$detalle}\n";
exit($status === 'OK' ? 0 : 1);
