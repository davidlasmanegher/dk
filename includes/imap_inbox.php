<?php
/**
 * Lectura de correo entrante por IMAP, para alimentar la bandeja (inbox).
 * Usa la extensión IMAP de PHP si está disponible. Settings: imap_host/port/user/pass.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/inbox.php';

/** ¿IMAP utilizable? (extensión presente + configurado). */
function imap_inbox_available(): bool {
    return function_exists('imap_open')
        && trim((string)setting('imap_host', '')) !== ''
        && trim((string)setting('imap_user', '')) !== '';
}

/** Extrae el texto del cuerpo (prefiere texto plano). */
function imap_inbox_body($imap, int $num): string {
    $body = imap_fetchbody($imap, $num, '1.1');
    if (trim($body) === '') $body = imap_fetchbody($imap, $num, '1');
    if (trim($body) === '') $body = imap_body($imap, $num);
    $body = quoted_printable_decode((string)$body);
    return trim(strip_tags($body));
}

/**
 * Lee los correos no leídos del INBOX y los ingesta. Marca como leídos los procesados.
 * @return array{ok:bool, processed:int, error?:string}
 */
function imap_inbox_fetch(int $max = 30): array {
    if (!function_exists('imap_open')) {
        return ['ok' => false, 'processed' => 0, 'error' => 'La extensión IMAP de PHP no está disponible en el servidor.'];
    }
    $host = trim((string)setting('imap_host', ''));
    $port = (int)setting('imap_port', '993');
    $user = trim((string)setting('imap_user', ''));
    $pass = (string)setting('imap_pass', '');
    if ($host === '' || $user === '') {
        return ['ok' => false, 'processed' => 0, 'error' => 'IMAP no configurado en Ajustes.'];
    }

    $mailbox = '{' . $host . ':' . $port . '/imap/ssl}INBOX';
    $imap = @imap_open($mailbox, $user, $pass);
    if (!$imap) {
        return ['ok' => false, 'processed' => 0, 'error' => 'No se pudo conectar al IMAP: ' . imap_last_error()];
    }

    $ids = imap_search($imap, 'UNSEEN') ?: [];
    $processed = 0;
    foreach (array_slice($ids, 0, $max) as $num) {
        $ov = imap_fetch_overview($imap, (string)$num, 0);
        $ov = $ov[0] ?? null;
        if (!$ov) continue;
        $from = '';
        if (preg_match('/[\w\.\-\+]+@[\w\.\-]+/', $ov->from ?? '', $mm)) $from = strtolower($mm[0]);
        $subject = isset($ov->subject) ? imap_utf8($ov->subject) : '';
        $msgId   = $ov->message_id ?? ('imap_' . $num);
        $body    = imap_inbox_body($imap, (int)$num);
        inbox_ingest('email', $from, $body, $msgId, $subject);
        imap_setflag_full($imap, (string)$num, "\\Seen");
        $processed++;
    }
    imap_close($imap);
    return ['ok' => true, 'processed' => $processed];
}
