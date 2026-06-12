<?php
/**
 * Cliente de WhatsApp vía Whapi.Cloud para el Agente DK.
 * Settings (ya sembrados en install.php): whapi_token, whapi_instance_url, whapi_owner_phone.
 */
require_once __DIR__ . '/db.php';

/** ¿Whapi está configurado? */
function whapi_available(): bool {
    $t = trim((string)setting('whapi_token', ''));
    $b = trim((string)setting('whapi_instance_url', ''));
    return strlen($t) > 10 && $b !== '';
}

/** Headers comunes para llamadas a Whapi. */
function whapi_headers(): array {
    return [
        'Authorization: Bearer ' . trim((string)setting('whapi_token', '')),
        'Content-Type: application/json',
        'Accept: application/json',
    ];
}

/**
 * Llamada HTTP base a Whapi.
 * @return array{ok:bool, http:int, data:?array, error:string}
 */
function whapi_http(string $method, string $path, array $payload = []): array {
    if (!whapi_available()) {
        return ['ok' => false, 'http' => 0, 'data' => null,
                'error' => 'WhatsApp (Whapi) no configurado. Carga el token y la URL en Ajustes.'];
    }
    $base = rtrim((string)setting('whapi_instance_url', 'https://gate.whapi.cloud'), '/');
    $url  = $base . '/' . ltrim($path, '/');

    $ch   = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => whapi_headers(),
        CURLOPT_TIMEOUT        => 60,
    ];
    if (!empty($payload)) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);

    $res   = curl_exec($ch);
    $errno = curl_errno($ch);
    $cerr  = curl_error($ch);
    $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'http' => 0, 'data' => null,
                'error' => 'Error de red al contactar Whapi: ' . $cerr];
    }

    $data = json_decode($res, true);
    if ($http < 200 || $http >= 300) {
        $msg = $data['error']['message'] ?? $data['error'] ?? "Whapi respondió HTTP {$http}";
        if (is_array($msg)) $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        return ['ok' => false, 'http' => $http, 'data' => $data, 'error' => (string)$msg];
    }
    return ['ok' => true, 'http' => $http, 'data' => $data, 'error' => ''];
}

/** Normaliza un teléfono a solo dígitos (E.164 sin "+"). */
function whapi_normalize_phone(string $phone): string {
    return preg_replace('/\D+/', '', $phone);
}

/**
 * Envía un mensaje de texto por WhatsApp.
 * @return array{ok:bool, message_id:string, error:string}
 */
function whapi_send_text(string $phone, string $text): array {
    $clean = whapi_normalize_phone($phone);
    if ($clean === '') return ['ok' => false, 'message_id' => '', 'error' => 'Teléfono vacío o inválido.'];

    $r = whapi_http('POST', 'messages/text', ['to' => $clean, 'body' => $text]);
    if (!$r['ok']) return ['ok' => false, 'message_id' => '', 'error' => $r['error']];

    $msgId = $r['data']['message']['id'] ?? $r['data']['id'] ?? '';
    return ['ok' => true, 'message_id' => (string)$msgId, 'error' => ''];
}

/**
 * Notifica por WhatsApp a los administradores (users con notify=1 y teléfono cargado).
 * Best-effort: no rompe el flujo si falla.
 * @return array{sent:int, total:int, error:string}
 */
function notify_admins(string $text, string $excludePhone = ''): array {
    try {
        $admins = db()->query("SELECT name, phone FROM users WHERE notify = 1 AND phone <> ''")->fetchAll();
    } catch (Throwable $e) {
        return ['sent' => 0, 'total' => 0, 'error' => 'falta columna phone/notify'];
    }
    if (!whapi_available()) return ['sent' => 0, 'total' => count($admins), 'error' => 'whapi no disponible'];
    $exTail = substr(preg_replace('/\D+/', '', $excludePhone), -10);   // no auto-notificar al remitente
    $sent = 0; $tot = 0;
    foreach ($admins as $a) {
        if ($exTail !== '' && substr(preg_replace('/\D+/', '', (string)$a['phone']), -10) === $exTail) continue;
        $tot++;
        $r = whapi_send_text((string)$a['phone'], $text);
        if (!empty($r['ok'])) $sent++;
    }
    return ['sent' => $sent, 'total' => $tot, 'error' => ''];
}
