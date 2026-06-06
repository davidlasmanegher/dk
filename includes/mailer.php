<?php
/**
 * Envío de email por SMTP (socket directo, sin dependencias) para el Agente DK.
 * Settings (ya sembrados en install.php): smtp_host, smtp_port, smtp_user, smtp_pass,
 * smtp_from_name, smtp_from_email. El modo seguro se infiere por puerto:
 * 465 = SSL implícito; 587/otros = STARTTLS; 25 = sin cifrado.
 */
require_once __DIR__ . '/db.php';

/** ¿SMTP configurado mínimamente? */
function mail_available(): bool {
    return trim((string)setting('smtp_host', ''))       !== ''
        && trim((string)setting('smtp_from_email', '')) !== '';
}

/**
 * Envía un email (texto plano + HTML opcional) por SMTP.
 * @return array{ok:bool, error:string}
 */
function mail_send(string $to, string $toName, string $subject, string $bodyText, string $bodyHtml = ''): array {
    $host     = trim((string)setting('smtp_host', ''));
    $port     = (int)setting('smtp_port', '587');
    $user     = trim((string)setting('smtp_user', ''));
    $pass     = (string)setting('smtp_pass', '');
    $from     = trim((string)setting('smtp_from_email', ''));
    $fromName = trim((string)setting('smtp_from_name', 'Daniel Khan · SISTEL'));

    if ($host === '' || $from === '') {
        return ['ok' => false, 'error' => 'SMTP no configurado (faltan host o email de origen) en Ajustes.'];
    }
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Email destino inválido.'];
    }

    $ssl  = ($port === 465);
    $addr = ($ssl ? 'ssl://' : '') . $host;
    $sock = @fsockopen($addr, $port, $errno, $errstr, 15);
    if (!$sock) return ['ok' => false, 'error' => "No se pudo conectar al SMTP: {$errstr} ({$errno})"];
    stream_set_timeout($sock, 15);

    $read = function () use ($sock): string {
        $out = '';
        while (($line = fgets($sock, 515)) !== false) {
            $out .= $line;
            if (preg_match('/^\d{3} /', $line)) break;
        }
        return $out;
    };
    $cmd  = function (string $c) use ($sock, $read): string {
        fwrite($sock, $c . "\r\n");
        return $read();
    };
    $code = fn (string $resp): int => (int)substr(ltrim($resp), 0, 3);

    $greet = $read();
    if ($code($greet) !== 220) { fclose($sock); return ['ok' => false, 'error' => 'El SMTP no saludó (220): ' . trim($greet)]; }

    $ehlo = $cmd('EHLO dk-agent');
    if ($code($ehlo) !== 250) { fclose($sock); return ['ok' => false, 'error' => 'EHLO rechazado: ' . trim($ehlo)]; }

    if (!$ssl && $port !== 25) {
        $tls = $cmd('STARTTLS');
        if ($code($tls) !== 220) { fclose($sock); return ['ok' => false, 'error' => 'STARTTLS rechazado: ' . trim($tls)]; }
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($sock); return ['ok' => false, 'error' => 'No se pudo iniciar TLS con el SMTP.'];
        }
        $cmd('EHLO dk-agent');
    }

    if ($user !== '') {
        $auth = $cmd('AUTH LOGIN');
        if ($code($auth) !== 334) { fclose($sock); return ['ok' => false, 'error' => 'AUTH LOGIN no soportado: ' . trim($auth)]; }
        $u = $cmd(base64_encode($user));
        if ($code($u) !== 334) { fclose($sock); return ['ok' => false, 'error' => 'Usuario SMTP rechazado.']; }
        $pw = $cmd(base64_encode($pass));
        if ($code($pw) !== 235) { fclose($sock); return ['ok' => false, 'error' => 'Autenticación SMTP fallida (revisa usuario/contraseña).']; }
    }

    $mf = $cmd("MAIL FROM:<{$from}>");
    if ($code($mf) !== 250) { fclose($sock); return ['ok' => false, 'error' => 'MAIL FROM rechazado: ' . trim($mf)]; }
    $rc = $cmd("RCPT TO:<{$to}>");
    if ($code($rc) !== 250 && $code($rc) !== 251) { fclose($sock); return ['ok' => false, 'error' => 'RCPT TO rechazado: ' . trim($rc)]; }
    $dt = $cmd('DATA');
    if ($code($dt) !== 354) { fclose($sock); return ['ok' => false, 'error' => 'DATA rechazado: ' . trim($dt)]; }

    // Construcción del mensaje (multipart/alternative si hay HTML).
    $boundary = 'dk_' . bin2hex(random_bytes(8));
    $encSubj  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encFrom  = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
    $encTo    = ($toName !== '' ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '') . '<' . $to . '>';

    $headers = [
        'Date: ' . date('r'),
        "From: {$encFrom}",
        "To: {$encTo}",
        "Subject: {$encSubj}",
        "Message-ID: <{$boundary}@dk-agent>",
        'MIME-Version: 1.0',
    ];

    if ($bodyHtml !== '') {
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $msg  = implode("\r\n", $headers) . "\r\n\r\n";
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= _mail_dotstuff($bodyText) . "\r\n";
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= _mail_dotstuff($bodyHtml) . "\r\n";
        $msg .= "--{$boundary}--\r\n";
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $msg  = implode("\r\n", $headers) . "\r\n\r\n";
        $msg .= _mail_dotstuff($bodyText) . "\r\n";
    }
    $msg .= '.';  // fin del DATA

    $send = $cmd($msg);
    if ($code($send) !== 250) { fclose($sock); return ['ok' => false, 'error' => 'El servidor no aceptó el mensaje: ' . trim($send)]; }

    $cmd('QUIT');
    fclose($sock);
    return ['ok' => true, 'error' => ''];
}

/** Dot-stuffing y normalización CRLF (RFC 5321): líneas que arrancan con "." se duplican. */
function _mail_dotstuff(string $body): string {
    $body = str_replace(["\r\n", "\r", "\n"], "\r\n", $body);
    return preg_replace('/^\./m', '..', $body);
}
