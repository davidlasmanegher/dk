<?php
/**
 * Webhook de WhatsApp (Whapi): recibe mensajes entrantes server-to-server.
 * NO usa sesión; se protege con un token en la URL (?token=...) comparado con
 * el setting whapi_webhook_token. Configurar la URL del webhook en la cuenta Whapi:
 *   https://www.sisteltools.com/dk/api/whapi_webhook.php?token=EL_TOKEN
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/inbox.php';
@set_time_limit(0);

$token = trim((string)setting('whapi_webhook_token', ''));
$given = (string)($_GET['token'] ?? '');
if ($token === '' || !hash_equals($token, $given)) {
    http_response_code(403);
    echo 'forbidden';
    exit;
}

$d    = json_in();
$msgs = $d['messages'] ?? [];
$processed = 0;
foreach ($msgs as $m) {
    if (!empty($m['from_me'])) continue;                 // ignorar mensajes salientes
    $type = $m['type'] ?? '';
    if ($type === 'text')      $text = (string)($m['text']['body'] ?? '');
    elseif ($type === 'image') $text = (string)($m['image']['caption'] ?? '[imagen]');
    else                       $text = '[' . $type . ']';
    $from = (string)($m['from'] ?? '');
    $id   = (string)($m['id'] ?? '');
    if (trim($text) === '' || $from === '') continue;
    inbox_ingest('whatsapp', $from, $text, $id);
    $processed++;
}

json_out(['ok' => true, 'processed' => $processed]);
