<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
boot();
verify_csrf_token();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    // ── list_content ──────────────────────────────────────────────────────────
    case 'list_content': {
        $type   = trim((string)($d['type']   ?? ''));
        $status = trim((string)($d['status'] ?? ''));
        $limit  = max(1, min(200, (int)($d['limit'] ?? 50)));
        $offset = max(0, (int)($d['offset'] ?? 0));

        $where  = ['1=1'];
        $params = [];
        if ($type   !== '') { $where[] = 'type = ?';   $params[] = $type; }
        if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }

        $sql = "SELECT id, type, title, body, status, platform, lead_id, created_at
                FROM content_pieces WHERE " . implode(' AND ', $where) . "
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $st = db()->prepare($sql);
        $st->execute($params);
        json_out(['ok' => true, 'pieces' => $st->fetchAll()]);
    }

    // ── get_content ───────────────────────────────────────────────────────────
    case 'get_content': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        $st = db()->prepare("SELECT * FROM content_pieces WHERE id = ?");
        $st->execute([$id]);
        $piece = $st->fetch();
        if (!$piece) json_out(['ok' => false, 'error' => 'No encontrado.'], 404);
        json_out(['ok' => true, 'piece' => $piece]);
    }

    // ── save_content ──────────────────────────────────────────────────────────
    case 'save_content': {
        $id      = (int)($d['id'] ?? 0);
        $fields  = [
            'type'     => trim((string)($d['type']     ?? 'post_linkedin')),
            'title'    => trim((string)($d['title']    ?? '')),
            'body'     => trim((string)($d['body']     ?? '')),
            'hook'     => trim((string)($d['hook']     ?? '')),
            'cta'      => trim((string)($d['cta']      ?? '')),
            'status'   => trim((string)($d['status']   ?? 'borrador')),
            'platform' => trim((string)($d['platform'] ?? 'linkedin')),
            'lead_id'  => $d['lead_id'] ? (int)$d['lead_id'] : null,
        ];

        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
            $st  = db()->prepare("UPDATE content_pieces SET $set, updated_at = NOW() WHERE id = :id");
            $fields['id'] = $id;
            $st->execute($fields);
            json_out(['ok' => true, 'id' => $id]);
        } else {
            $cols = implode(', ', array_keys($fields));
            $ph   = ':' . implode(', :', array_keys($fields));
            $st   = db()->prepare("INSERT INTO content_pieces ($cols) VALUES ($ph)");
            $st->execute($fields);
            json_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
        }
    }

    // ── delete_content ────────────────────────────────────────────────────────
    case 'delete_content': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        db()->prepare("DELETE FROM content_pieces WHERE id = ?")->execute([$id]);
        json_out(['ok' => true]);
    }

    // ── generate ─────────────────────────────────────────────────────────────
    case 'generate': {
        $api_key = setting('claude_api_key', '');
        if (!$api_key || strlen(trim($api_key)) < 10) {
            json_out(['ok' => false, 'error' => 'Configura tu API key de Claude en Ajustes.'], 400);
        }

        $type    = trim((string)($d['type']    ?? 'post_linkedin'));
        $context = trim((string)($d['context'] ?? ''));
        $lead_id = (int)($d['lead_id'] ?? 0);

        // Cargar perfil del agente
        $profile = db()->query("SELECT * FROM agent_profile WHERE id = 1")->fetch() ?: [];
        $name    = $profile['name']    ?? 'Daniel Khan';
        $role    = $profile['role']    ?? 'Director de Desarrollo de Negocios LATAM';
        $company = $profile['company'] ?? 'SISTEL';
        $model   = $profile['market_focus'] ?? 'México';
        $vp      = $profile['value_proposition']   ?? '';
        $cs      = $profile['communication_style'] ?? '';
        $op      = $profile['objections_playbook'] ?? '';

        // Datos del lead si aplica
        $leadInfo = '';
        if ($lead_id) {
            $ls = db()->prepare("SELECT * FROM leads WHERE id = ?");
            $ls->execute([$lead_id]);
            $lead = $ls->fetch();
            if ($lead) {
                $leadName = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));
                $leadInfo = "\n\nDATO DEL LEAD:\n"
                    . "Nombre: {$leadName}\n"
                    . "Cargo: " . ($lead['role']     ?? '') . "\n"
                    . "Empresa: " . ($lead['company']  ?? '') . "\n"
                    . "Industria: " . ($lead['industry'] ?? '') . "\n"
                    . "Ciudad: " . ($lead['city']     ?? '') . "\n"
                    . "Etapa: " . ($lead['stage']    ?? '') . "\n"
                    . "Notas: " . ($lead['notes']    ?? '');
            }
        }

        // System prompt según tipo
        $typeLabels = [
            'post_linkedin'      => 'un post de LinkedIn de alto impacto',
            'mensaje_prospecto'  => 'un mensaje de prospección inicial para LinkedIn o WhatsApp',
            'email_seguimiento'  => 'un email de seguimiento profesional',
            'propuesta'          => 'un resumen ejecutivo de propuesta de valor',
            'articulo'           => 'un artículo de thought leadership',
            'newsletter'         => 'un fragmento de newsletter para líderes de L&D',
        ];
        $typeLabel = $typeLabels[$type] ?? 'contenido de ventas';

        $systemPrompt = <<<TXT
Eres el asistente digital de {$name}, {$role} en {$company}.
Mercado: {$model}.

PROPUESTA DE VALOR:
{$vp}

ESTILO DE COMUNICACIÓN:
{$cs}

MANEJO DE OBJECIONES (usar como referencia cuando sea relevante):
{$op}

Tu tarea: generar {$typeLabel} en nombre de {$name}.
El contenido debe sonar auténtico a su voz: consultivo, directo, orientado a resultados de negocio.
Nunca vendas características; conecta siempre con el problema del cliente.
Idioma: español (México).

Responde ÚNICAMENTE en JSON con este formato:
{"title":"...", "body":"...", "hook":"...", "cta":"..."}
TXT;

        $userPrompt = "Genera {$typeLabel}.";
        if ($context) $userPrompt .= "\n\nCONTEXTO ADICIONAL:\n{$context}";
        if ($leadInfo) $userPrompt .= $leadInfo;

        // Llamar a Claude
        $model_id = setting('claude_model', 'claude-sonnet-4-5');
        $payload  = json_encode([
            'model'      => $model_id,
            'max_tokens' => 1200,
            'system'     => $systemPrompt,
            'messages'   => [['role' => 'user', 'content' => $userPrompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) json_out(['ok' => false, 'error' => "Error de red: {$err}"], 500);

        $data = json_decode($resp, true);
        if (empty($data['content'][0]['text'])) {
            $msg = $data['error']['message'] ?? 'Respuesta vacía de Claude.';
            json_out(['ok' => false, 'error' => $msg], 500);
        }

        $raw  = trim($data['content'][0]['text']);
        // Extraer JSON del texto
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $parsed = json_decode($m[0], true);
        } else {
            $parsed = null;
        }

        if (!is_array($parsed)) {
            // Si Claude no devuelve JSON válido, envolver en estructura genérica
            $parsed = ['title' => $typeLabel, 'body' => $raw, 'hook' => '', 'cta' => ''];
        }

        json_out([
            'ok'    => true,
            'title' => $parsed['title'] ?? '',
            'body'  => $parsed['body']  ?? $raw,
            'hook'  => $parsed['hook']  ?? '',
            'cta'   => $parsed['cta']   ?? '',
            'type'  => $type,
        ]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
