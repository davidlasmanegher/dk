<?php
/**
 * Motor de IA del Agente DK — cliente de la API de Anthropic (Claude) +
 * armado del system prompt desde el perfil del agente (agent_profile).
 *
 * A diferencia del Estudio de Contenido, acá NO hay brands/skills/strategies:
 * el "cerebro" es la fila única agent_profile (id=1), la voz de Daniel Khan.
 */
require_once __DIR__ . '/db.php';

const CLAUDE_API_URL     = 'https://api.anthropic.com/v1/messages';
const CLAUDE_API_VERSION = '2023-06-01';

/** Modelo configurado (fallback a Sonnet 4.6 si el setting está vacío). */
function claude_model(): string {
    $m = trim((string)setting('claude_model', ''));
    return $m !== '' ? $m : 'claude-sonnet-4-6';
}

/** ¿Hay API key de Claude cargada? */
function claude_available(): bool {
    return strlen(trim((string)setting('claude_api_key', ''))) > 10;
}

/**
 * Petición HTTP base a la API de Mensajes.
 * @return array{ok:bool, http:int, data:?array, error:string}
 */
function claude_http(array $payload): array {
    $key = trim((string)setting('claude_api_key', ''));
    if (strlen($key) < 10) {
        return ['ok' => false, 'http' => 0, 'data' => null,
                'error' => 'No hay API key de Claude. Cárgala en Ajustes.'];
    }

    $ch = curl_init(CLAUDE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: ' . CLAUDE_API_VERSION,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 180,
    ]);

    $res   = curl_exec($ch);
    $errno = curl_errno($ch);
    $cerr  = curl_error($ch);
    $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'http' => 0, 'data' => null,
                'error' => 'Error de red al contactar la API: ' . $cerr];
    }

    $data = json_decode($res, true);
    if ($http !== 200) {
        $msg = $data['error']['message'] ?? ('La API respondió con código ' . $http);
        return ['ok' => false, 'http' => $http, 'data' => $data, 'error' => $msg];
    }
    return ['ok' => true, 'http' => 200, 'data' => $data, 'error' => ''];
}

/** Concatena los bloques de texto de una respuesta. */
function claude_extract_text(array $data): string {
    $text = '';
    if (!empty($data['content']) && is_array($data['content'])) {
        foreach ($data['content'] as $block) {
            if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        }
    }
    return trim($text);
}

/**
 * Llamada simple, sin herramientas.
 * @return array{ok:bool, text:string, error:string}
 */
function claude_call(string $system, string $userText, int $maxTokens = 2000, float $temperature = 0.7): array {
    $r = claude_http([
        'model'       => claude_model(),
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature,
        'system'      => $system,
        'messages'    => [['role' => 'user', 'content' => $userText]],
    ]);
    if (!$r['ok']) return ['ok' => false, 'text' => '', 'error' => $r['error']];
    $text = claude_extract_text($r['data']);
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'La API no devolvió texto.'];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}

/**
 * Llamada con búsqueda web en vivo (tool web_search_20250305).
 * Útil para investigar empresas y prospectos del mercado mexicano.
 * @return array{ok:bool, text:string, sources:array, searched:bool, error:string, tool_unavailable:bool}
 */
function claude_call_web(string $system, string $userText, int $maxTokens = 4000, int $maxUses = 5): array {
    $r = claude_http([
        'model'      => claude_model(),
        'max_tokens' => $maxTokens,
        'system'     => $system,
        'messages'   => [['role' => 'user', 'content' => $userText]],
        'tools'      => [[
            'type'     => 'web_search_20250305',
            'name'     => 'web_search',
            'max_uses' => max(1, min(8, $maxUses)),
        ]],
    ]);

    if (!$r['ok']) {
        $unavailable = ($r['http'] === 400)
            || stripos($r['error'], 'web_search') !== false
            || stripos($r['error'], 'tool') !== false;
        return ['ok' => false, 'text' => '', 'sources' => [], 'searched' => false,
                'error' => $r['error'], 'tool_unavailable' => $unavailable];
    }

    $data     = $r['data'];
    $text     = claude_extract_text($data);
    $sources  = [];
    $searched = false;

    if (!empty($data['content']) && is_array($data['content'])) {
        foreach ($data['content'] as $block) {
            $type = $block['type'] ?? '';
            if ($type === 'text' && !empty($block['citations'])) {
                foreach ($block['citations'] as $cit) {
                    if (!empty($cit['url'])) {
                        $sources[$cit['url']] = ['title' => $cit['title'] ?? $cit['url'], 'url' => $cit['url']];
                    }
                }
            } elseif ($type === 'web_search_tool_result' && !empty($block['content']) && is_array($block['content'])) {
                $searched = true;
                foreach ($block['content'] as $rr) {
                    if (!empty($rr['url'])) $sources[$rr['url']] = ['title' => $rr['title'] ?? $rr['url'], 'url' => $rr['url']];
                }
            } elseif ($type === 'server_tool_use') {
                $searched = true;
            }
        }
    }

    return ['ok' => true, 'text' => $text, 'sources' => array_values($sources),
            'searched' => $searched, 'error' => '', 'tool_unavailable' => false];
}

/** Extrae el primer bloque JSON de una respuesta (tolera fences y texto extra). */
function extract_json(string $text) {
    $text = trim($text);
    $text = preg_replace('/^```(json)?/i', '', $text);
    $text = preg_replace('/```$/', '', trim($text));
    $text = trim($text);
    $decoded = json_decode($text, true);
    if (is_array($decoded)) return $decoded;
    $start = strcspn($text, '{[');
    if ($start < strlen($text)) {
        $sub     = substr($text, $start);
        $decoded = json_decode($sub, true);
        if (is_array($decoded)) return $decoded;
    }
    return null;
}

// ── El perfil del agente como "cerebro" ──────────────────────────────────────

/** Carga la fila única del perfil del agente (id=1), cacheada en memoria. */
function agent_profile(): array {
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = db()->query("SELECT * FROM agent_profile WHERE id = 1")->fetch() ?: [];
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache;
}

/**
 * Bloque de identidad/voz del agente para inyectar en cualquier system prompt.
 * Reemplaza a brand_context/system_prompt_for del Estudio: acá el cerebro es agent_profile.
 */
function agent_identity_block(): string {
    $p       = agent_profile();
    $name    = $p['name']         ?? 'Daniel Khan';
    $role    = $p['role']         ?? 'Director de Desarrollo de Negocios LATAM';
    $company = $p['company']      ?? 'SISTEL';
    $market  = $p['market_focus'] ?? 'México';
    $target  = trim((string)($p['target_market']       ?? ''));
    $vp      = trim((string)($p['value_proposition']   ?? ''));
    $cs      = trim((string)($p['communication_style'] ?? ''));
    $op      = trim((string)($p['objections_playbook'] ?? ''));

    $b  = "Eres el asistente digital de {$name}, {$role} en {$company}.\n";
    $b .= "Mercado foco: {$market}.\n";
    if ($target) $b .= "\nCLIENTE IDEAL:\n{$target}\n";
    if ($vp)     $b .= "\nPROPUESTA DE VALOR:\n{$vp}\n";
    if ($cs)     $b .= "\nESTILO DE COMUNICACIÓN (imítalo con fidelidad):\n{$cs}\n";
    if ($op)     $b .= "\nMANEJO DE OBJECIONES (úsalo como referencia cuando aplique):\n{$op}\n";
    $b .= "\nReglas: escribe en nombre de {$name}, en primera persona. Tono consultivo, directo y ";
    $b .= "orientado a resultados de negocio. Nunca vendas características: conecta con el problema del cliente. ";
    $b .= "Usa español natural de {$market}. No inventes datos, cifras ni casos que no se te hayan dado.";
    return $b;
}
