<?php
/**
 * Bandeja de entrada unificada: ingesta de mensajes entrantes (WhatsApp/email),
 * generación de respuesta con la voz de Daniel + objeciones, y aprobación/envío.
 * El humano aprueba antes de que el clon responda a un prospecto real.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/outreach.php';   // trae claude, whapi, mailer, agent_identity_block

/** Identifica el lead por teléfono (whatsapp) o email (email). */
function inbox_find_lead(string $channel, string $from): ?array {
    $from = trim($from);
    if ($from === '') return null;
    if ($channel === 'whatsapp') {
        $digits = preg_replace('/\D+/', '', $from);
        if ($digits === '') return null;
        $tail = substr($digits, -10);   // últimos 10 dígitos (ignora lada país)
        $st = db()->prepare(
            "SELECT * FROM leads
             WHERE REPLACE(REPLACE(REPLACE(whatsapp_phone,' ',''),'-',''),'+','') LIKE ?
                OR REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+','') LIKE ?
             ORDER BY id LIMIT 1"
        );
        $st->execute(['%' . $tail, '%' . $tail]);
        return $st->fetch() ?: null;
    }
    $st = db()->prepare("SELECT * FROM leads WHERE LOWER(email) = ? LIMIT 1");
    $st->execute([strtolower($from)]);
    return $st->fetch() ?: null;
}

/** Heurística rápida: ¿el mensaje parece traer una objeción? (la IA igual la maneja). */
function inbox_detect_objection(string $text): bool {
    $t = mb_strtolower($text, 'UTF-8');
    $kw = ['caro','costoso','presupuesto','no es el momento','más adelante','mas adelante','ya tenemos',
        'internamente','no me interesa','manda info','mándame','informacion','información','lo reviso',
        'consultarlo','consultar','no tengo tiempo','otra plataforma','no usan','después','despues','ocupado'];
    foreach ($kw as $k) if (mb_strpos($t, $k) !== false) return true;
    return false;
}

/** Genera la respuesta IA al mensaje entrante (no envía). */
function inbox_generate_reply(array $lead, string $incoming, string $channel): array {
    if (!claude_available()) return ['ok' => false, 'error' => 'Falta API key de Claude.', 'reply' => '', 'subject' => ''];
    $channel = outreach_norm_channel($channel);
    $system  = agent_identity_block() . "\n\n" . outreach_channel_rules($channel)
        . "\n\nESTÁS EN UNA CONVERSACIÓN REAL respondiendo a un prospecto que te escribió. Sos Daniel Khan, una persona de carne y hueso — NO un bot ni un formulario. "
        . "Tu objetivo comercial: construir confianza, entender la necesidad real, aportar valor concreto y, cuando haya interés, proponer un paso suave (una llamada breve, mostrar un caso, agendar un espacio). "
        . "Si el mensaje trae una objeción, aplicá la regla de oro: validar, reinterpretar, conectar con valor de negocio y proponer un paso sin compromiso. "
        . "Escribí breve, cálido y humano, como en un chat real (no como un mail corporativo). Continuá el hilo con coherencia; no repitas lo que ya dijiste.\n"
        . "REGLAS DURAS DE SEGURIDAD COMERCIAL: NUNCA inventes precios, plazos, cifras, funcionalidades, integraciones ni casos de éxito. "
        . "Si no sabés algo, está fuera de tu alcance o te piden una cotización/condiciones, NO lo inventes: decí con naturalidad que lo confirmás con el equipo y le escribís a la brevedad. "
        . "No prometas nada que no puedas cumplir ni cierres condiciones comerciales por este canal.";
    // RAG: corpus de conocimiento del autor (documentos, casos, metodología 6E, SENSEI...)
    $kctx = knowledge_context($incoming . ' ' . ($lead['company'] ?? '') . ' ' . ($lead['industry'] ?? ''), 6);
    if ($kctx !== '') $system .= "\n\n" . $kctx;
    $lc = learning_context(3); if ($lc !== '') $system .= "\n\n" . $lc;
    $leadName = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));

    // Ficha CRM del prospecto: contexto comercial completo
    $ctx = "FICHA DEL PROSPECTO (contexto interno, NO lo recites):\n- Nombre: {$leadName}\n- Cargo: " . ($lead['role'] ?: '—')
         . "\n- Empresa: " . ($lead['company'] ?: '—') . "\n- Sector: " . ($lead['industry'] ?: '—')
         . "\n- Etapa en el pipeline: " . ($lead['stage'] ?: '—');
    if (!empty($lead['notes'])) $ctx .= "\n- Notas internas: " . mb_substr((string)$lead['notes'], 0, 400);
    try {
        $cq = db()->prepare("SELECT c.name, c.objective FROM campaign_leads cl JOIN campaigns c ON c.id = cl.campaign_id WHERE cl.lead_id = ? ORDER BY cl.id DESC LIMIT 1");
        $cq->execute([(int)$lead['id']]);
        if ($cr = $cq->fetch()) {
            $ctx .= "\n- Campaña: " . $cr['name'];
            if (!empty($cr['objective'])) $ctx .= "\n- Foco del primer contacto: " . mb_substr((string)$cr['objective'], 0, 500);
        }
    } catch (Throwable $e) {}

    $hist = outreach_lead_history((int)$lead['id'], 10);
    $user = $ctx . "\n";
    if ($hist) $user .= "\nHISTORIAL DE LA CONVERSACIÓN:\n{$hist}\n";
    $user .= "\nMENSAJE ENTRANTE DEL PROSPECTO:\n\"{$incoming}\"\n\n"
        . "Redactá la mejor respuesta en nombre de Daniel, coherente con el historial y el contexto. Respondé SOLO con JSON: {\"subject\":\"...\",\"body\":\"...\"}";
    $r = claude_call($system, $user, 1200, 0.7);
    if (!$r['ok']) return ['ok' => false, 'error' => $r['error'], 'reply' => '', 'subject' => ''];
    $p = extract_json($r['text']);
    $body = is_array($p) ? (string)($p['body'] ?? '') : trim($r['text']);
    $subj = is_array($p) ? (string)($p['subject'] ?? '') : '';
    return ['ok' => true, 'reply' => scrub_social_proof($body), 'subject' => scrub_social_proof($subj), 'error' => ''];
}

/**
 * Ingesta de un mensaje entrante (desde webhook de WhatsApp o IMAP).
 * Identifica el lead, registra la actividad y prepara la respuesta sugerida.
 * @return array{ok:bool, id?:int, lead_id?:?int, dup?:bool, error?:string}
 */
function inbox_ingest(string $channel, string $from, string $body, string $externalId = '', string $subject = ''): array {
    $body = trim($body);
    if ($body === '') return ['ok' => false, 'error' => 'Mensaje vacío.'];
    $pdo = db();

    if ($externalId !== '') {
        $st = $pdo->prepare("SELECT id FROM inbox_messages WHERE channel = ? AND external_id = ? LIMIT 1");
        $st->execute([$channel, $externalId]);
        if ($st->fetch()) return ['ok' => true, 'dup' => true];
    }

    $lead    = inbox_find_lead($channel, $from);
    $lead_id = $lead['id'] ?? null;

    if ($lead_id) {
        $pdo->prepare("INSERT INTO lead_activities (lead_id, type, subject, body, direction, status)
                       VALUES (?, ?, ?, ?, 'in', 'received')")
            ->execute([$lead_id, $channel, $subject, $body]);
    }

    $draft = ''; $subjOut = $subject;
    $hasObj = inbox_detect_objection($body) ? 1 : 0;
    if ($lead) {
        $g = inbox_generate_reply($lead, $body, $channel);
        if ($g['ok']) { $draft = $g['reply']; if ($subjOut === '') $subjOut = $g['subject']; }
    }

    $ins = $pdo->prepare(
        "INSERT INTO inbox_messages (lead_id, channel, external_id, from_addr, subject, body, reply_draft, has_objection, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')"
    );
    $ins->execute([$lead_id, $channel, ($externalId ?: null), $from, $subjOut, $body, $draft, $hasObj]);
    $newId = (int)$pdo->lastInsertId();

    // AUTO-REPLY autónomo: si está activado y hay un borrador válido para un contacto
    // identificado, Daniel responde solo (sin esperar aprobación). Los desconocidos quedan pendientes.
    if ($lead_id && $draft !== '' && (string)setting('inbox_autoreply', '0') === '1') {
        $ar = inbox_approve($newId);
        return ['ok' => true, 'id' => $newId, 'lead_id' => $lead_id, 'auto_replied' => !empty($ar['ok']), 'auto_error' => ($ar['error'] ?? '')];
    }
    return ['ok' => true, 'id' => $newId, 'lead_id' => $lead_id];
}

/** Aprueba y envía la respuesta (con edición opcional del cuerpo). */
function inbox_approve(int $id, ?string $editedBody = null): array {
    $pdo = db();
    $st  = $pdo->prepare("SELECT * FROM inbox_messages WHERE id = ? AND status = 'pendiente'");
    $st->execute([$id]);
    $m = $st->fetch();
    if (!$m) return ['ok' => false, 'error' => 'Mensaje no encontrado o ya gestionado.'];
    if (!$m['lead_id']) return ['ok' => false, 'error' => 'Sin lead asociado; vinculá el contacto antes de responder.'];

    $lead = outreach_get_lead((int)$m['lead_id']);
    if (!$lead) return ['ok' => false, 'error' => 'Lead inexistente.'];

    $body = $editedBody !== null ? trim($editedBody) : (string)$m['reply_draft'];
    if ($body === '') return ['ok' => false, 'error' => 'La respuesta está vacía.'];

    $res = outreach_send_message($lead, (string)$m['channel'], (string)$m['subject'], $body);
    if (!$res['ok']) return ['ok' => false, 'error' => $res['error']];

    $pdo->prepare("UPDATE inbox_messages SET status = 'respondido', reply_draft = ?, replied_at = NOW() WHERE id = ?")
        ->execute([$body, $id]);
    learning_record('inbox', (int)$m['lead_id'], (string)$m['channel'], (string)$m['body'], (string)$m['reply_draft'], $body);
    return ['ok' => true, 'channel' => $res['channel']];
}

/** Descarta un mensaje pendiente. */
function inbox_discard(int $id): array {
    db()->prepare("UPDATE inbox_messages SET status = 'descartado' WHERE id = ? AND status = 'pendiente'")->execute([$id]);
    return ['ok' => true];
}

/** Regenera la respuesta sugerida de un mensaje pendiente. */
function inbox_regenerate(int $id): array {
    $pdo = db();
    $st  = $pdo->prepare("SELECT * FROM inbox_messages WHERE id = ? AND status = 'pendiente'");
    $st->execute([$id]);
    $m = $st->fetch();
    if (!$m || !$m['lead_id']) return ['ok' => false, 'error' => 'Sin lead asociado.'];
    $lead = outreach_get_lead((int)$m['lead_id']);
    if (!$lead) return ['ok' => false, 'error' => 'Lead inexistente.'];
    $g = inbox_generate_reply($lead, (string)$m['body'], (string)$m['channel']);
    if (!$g['ok']) return ['ok' => false, 'error' => $g['error']];
    $pdo->prepare("UPDATE inbox_messages SET reply_draft = ? WHERE id = ?")->execute([$g['reply'], $id]);
    return ['ok' => true, 'reply' => $g['reply']];
}
