<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/claude.php';
boot();
verify_csrf_token();
require_once __DIR__ . '/../includes/auth.php';
require_auth_api();

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

    // ── generate — usa el motor compartido (claude.php + agent_identity_block) ──
    case 'generate': {
        if (!claude_available()) {
            json_out(['ok' => false, 'error' => 'Configura tu API key de Claude en Ajustes.'], 400);
        }

        $type    = trim((string)($d['type']    ?? 'post_linkedin'));
        $context = trim((string)($d['context'] ?? ''));
        $lead_id = (int)($d['lead_id'] ?? 0);

        $typeLabels = [
            'post_linkedin'     => 'un post de LinkedIn de alto impacto',
            'mensaje_prospecto' => 'un mensaje de prospección inicial para LinkedIn o WhatsApp',
            'email_seguimiento' => 'un email de seguimiento profesional',
            'propuesta'         => 'un resumen ejecutivo de propuesta de valor',
            'articulo'          => 'un artículo de thought leadership',
            'newsletter'        => 'un fragmento de newsletter para líderes de L&D',
        ];
        $typeLabel = $typeLabels[$type] ?? 'contenido de ventas';

        // Datos del lead, si aplica.
        $leadInfo = '';
        if ($lead_id) {
            $ls = db()->prepare("SELECT * FROM leads WHERE id = ?");
            $ls->execute([$lead_id]);
            $lead = $ls->fetch();
            if ($lead) {
                $leadName = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));
                $leadInfo = "\n\nDATO DEL LEAD:\n"
                    . "Nombre: {$leadName}\n"
                    . "Cargo: "     . ($lead['role']     ?? '') . "\n"
                    . "Empresa: "   . ($lead['company']  ?? '') . "\n"
                    . "Industria: " . ($lead['industry'] ?? '') . "\n"
                    . "Ciudad: "    . ($lead['city']     ?? '') . "\n"
                    . "Etapa: "     . ($lead['stage']    ?? '') . "\n"
                    . "Notas: "     . ($lead['notes']    ?? '');
            }
        }

        // Identidad/voz del agente desde el motor compartido + formato de salida.
        $system = agent_identity_block()
            . "\n\nTu tarea: generar {$typeLabel}."
            . "\nResponde ÚNICAMENTE en JSON con este formato:"
            . "\n{\"title\":\"...\", \"body\":\"...\", \"hook\":\"...\", \"cta\":\"...\"}";

        $userPrompt = "Genera {$typeLabel}.";
        if ($context)  $userPrompt .= "\n\nCONTEXTO ADICIONAL:\n{$context}";
        if ($leadInfo) $userPrompt .= $leadInfo;

        $r = claude_call($system, $userPrompt, 1200, 0.7);
        if (!$r['ok']) json_out(['ok' => false, 'error' => $r['error']], 502);

        $parsed = extract_json($r['text']);
        if (!is_array($parsed)) {
            $parsed = ['title' => $typeLabel, 'body' => $r['text'], 'hook' => '', 'cta' => ''];
        }

        json_out([
            'ok'    => true,
            'title' => (string)($parsed['title'] ?? ''),
            'body'  => (string)($parsed['body']  ?? $r['text']),
            'hook'  => (string)($parsed['hook']  ?? ''),
            'cta'   => (string)($parsed['cta']   ?? ''),
            'type'  => $type,
        ]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
