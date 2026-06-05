<?php
/**
 * Helpers compartidos: escape, JSON, CSRF, formato.
 */

/** Escape HTML. */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Lee el body JSON de una petición. */
function json_in(): array {
    $raw = file_get_contents('php://input');
    $d   = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

/** Respuesta JSON para endpoints AJAX. */
function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Recorta texto largo. */
function truncate(string $s, int $n = 140): string {
    $s = trim(preg_replace('/\s+/', ' ', $s));
    return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
}

/** Formatea fecha en español sin depender de locale. */
function fecha_es(?string $datetime): string {
    if (!$datetime) return '—';
    $ts = strtotime($datetime);
    if (!$ts) return '—';
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return date('j', $ts) . ' ' . $meses[(int)date('n', $ts)] . ' ' . date('Y · H:i', $ts);
}

// ── CSRF ─────────────────────────────────────────────────────────────────────

/** Devuelve (creando si hace falta) el token CSRF de la sesión. */
function generate_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF enviado en header X-CSRF-Token o campo `_csrf`.
 * Llama a json_out con 403 y termina si falla.
 */
function verify_csrf_token(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected) return; // primera petición antes de que exista token
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '') {
        $body = json_decode(file_get_contents('php://input') ?: '', true);
        if (is_array($body)) $sent = (string)($body['_csrf'] ?? '');
    }
    if (!hash_equals($expected, (string)$sent)) {
        json_out(['ok' => false, 'error' => 'CSRF token inválido o ausente.'], 403);
    }
}

/** Stages del pipeline de leads. */
function lead_stages(): array {
    return [
        'prospecto'   => ['label' => 'Prospecto',    'badge' => 'bg-slate-100 text-slate-600 ring-slate-200'],
        'contactado'  => ['label' => 'Contactado',   'badge' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        'interesado'  => ['label' => 'Interesado',   'badge' => 'bg-violet-50 text-violet-700 ring-violet-200'],
        'propuesta'   => ['label' => 'Propuesta',    'badge' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'negociacion' => ['label' => 'Negociación',  'badge' => 'bg-orange-50 text-orange-700 ring-orange-200'],
        'ganado'      => ['label' => 'Ganado',       'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'perdido'     => ['label' => 'Perdido',      'badge' => 'bg-red-50 text-red-600 ring-red-200'],
        'pausado'     => ['label' => 'Pausado',      'badge' => 'bg-slate-100 text-slate-400 ring-slate-200'],
    ];
}

function stage_label(string $s): string {
    $m = lead_stages();
    return $m[$s]['label'] ?? ucfirst($s);
}

function stage_badge(string $s): string {
    $m = lead_stages();
    return $m[$s]['badge'] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

/** ¿Está configurada la API key de Claude? */
function has_api_key(): bool {
    $k = setting('claude_api_key', '');
    return is_string($k) && strlen(trim((string)$k)) > 10;
}
