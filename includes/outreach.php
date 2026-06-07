<?php
/**
 * Lógica reutilizable de outreach: generar borrador + enviar mensaje a un lead.
 * La comparten el endpoint api/outreach.php (1:1 manual) y el runner del agente
 * (includes/agent.php), para no duplicar la generación ni el envío.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/claude.php';
require_once __DIR__ . '/whapi.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/knowledge.php';
require_once __DIR__ . '/learning.php';

/** Canal válido normalizado (email | whatsapp). */
function outreach_norm_channel(string $c): string {
    return in_array($c, ['email', 'whatsapp'], true) ? $c : 'email';
}

/** Carga un lead por id. */
function outreach_get_lead(int $id): ?array {
    $st = db()->prepare("SELECT * FROM leads WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/**
 * Genera un borrador (subject/body) para un lead por un canal. NO envía.
 * @return array{ok:bool, subject:string, body:string, error:string}
 */
function outreach_generate_draft(array $lead, string $channel, string $goal = '', string $context = ''): array {
    if (!claude_available()) {
        return ['ok' => false, 'subject' => '', 'body' => '', 'error' => 'Falta la API key de Claude (Ajustes).'];
    }
    $channel  = outreach_norm_channel($channel);
    $system   = agent_identity_block() . "\n\n" . outreach_channel_rules($channel);
    $kq = trim(($lead['company'] ?? '') . ' ' . ($lead['role'] ?? '') . ' ' . $goal . ' ' . $context);
    if ($kq !== '') { $kctx = knowledge_context($kq, 4); if ($kctx !== '') $system .= "\n\n" . $kctx; }
    $lc = learning_context(3); if ($lc !== '') $system .= "\n\n" . $lc;
    $leadName = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));

    $user  = "Redacta el mensaje para este prospecto.\n\nPROSPECTO:\n";
    $user .= "- Nombre: {$leadName}\n";
    $user .= "- Cargo: "     . ($lead['role']     ?: '—') . "\n";
    $user .= "- Empresa: "   . ($lead['company']  ?: '—') . "\n";
    $user .= "- Industria: " . ($lead['industry'] ?: '—') . "\n";
    $user .= "- Ubicación: " . trim(($lead['city'] ?? '') . ', ' . ($lead['country'] ?? ''), ', ') . "\n";
    $user .= "- Etapa: "     . ($lead['stage']    ?: '—') . "\n";
    if (!empty($lead['notes'])) $user .= "- Notas: " . $lead['notes'] . "\n";

    $hist = outreach_lead_history((int)$lead['id'], 6);
    if ($hist)    $user .= "\nHISTORIAL RECIENTE (lo más nuevo primero):\n{$hist}\n";
    if ($goal)    $user .= "\nOBJETIVO DE ESTE MENSAJE: {$goal}\n";
    if ($context) $user .= "\nCONTEXTO ADICIONAL: {$context}\n";
    $user .= "\nResponde SOLO con JSON válido, sin texto extra.";

    $r = claude_call($system, $user, 1500, 0.7);
    if (!$r['ok']) return ['ok' => false, 'subject' => '', 'body' => '', 'error' => $r['error']];

    $parsed = extract_json($r['text']);
    if (!is_array($parsed)) $parsed = ['subject' => '', 'body' => trim($r['text'])];
    return ['ok' => true, 'subject' => (string)($parsed['subject'] ?? ''), 'body' => (string)($parsed['body'] ?? ''), 'error' => ''];
}

/**
 * Envía un mensaje a un lead, registra la actividad y avanza la etapa si corresponde.
 * @return array{ok:bool, channel:string, error:string}
 */
function outreach_send_message(array $lead, string $channel, string $subject, string $body): array {
    $channel = outreach_norm_channel($channel);
    $subject = trim($subject);
    $body    = trim($body);
    if ($body === '') return ['ok' => false, 'channel' => $channel, 'error' => 'El mensaje está vacío.'];

    $lead_id  = (int)$lead['id'];
    $leadName = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));

    if ($channel === 'whatsapp') {
        $phone = trim((string)($lead['whatsapp_phone'] ?: $lead['phone']));
        if ($phone === '') return ['ok' => false, 'channel' => $channel, 'error' => 'El lead no tiene WhatsApp ni teléfono.'];
        $res = whapi_send_text($phone, $body);
        if (!$res['ok']) return ['ok' => false, 'channel' => $channel, 'error' => 'WhatsApp: ' . $res['error']];
        $actType = 'whatsapp'; $actSubject = '';
    } else {
        $email = trim((string)$lead['email']);
        if ($email === '') return ['ok' => false, 'channel' => $channel, 'error' => 'El lead no tiene email.'];
        if ($subject === '') $subject = 'Mensaje de ' . (agent_profile()['name'] ?? 'Daniel Khan');
        $res = mail_send($email, $leadName, $subject, $body, outreach_email_html($body));
        if (!$res['ok']) return ['ok' => false, 'channel' => $channel, 'error' => 'Email: ' . $res['error']];
        $actType = 'email'; $actSubject = $subject;
    }

    db()->prepare("INSERT INTO lead_activities (lead_id, type, subject, body, direction, status)
                   VALUES (?, ?, ?, ?, 'out', 'sent')")
        ->execute([$lead_id, $actType, $actSubject, $body]);

    if (($lead['stage'] ?? '') === 'prospecto') {
        db()->prepare("UPDATE leads SET stage = 'contactado', updated_at = NOW() WHERE id = ?")->execute([$lead_id]);
    } else {
        db()->prepare("UPDATE leads SET updated_at = NOW() WHERE id = ?")->execute([$lead_id]);
    }
    return ['ok' => true, 'channel' => $channel, 'error' => ''];
}

/** Historial reciente del lead como texto plano para contexto del modelo. */
function outreach_lead_history(int $leadId, int $limit = 6): string {
    $limit = max(1, min(20, $limit));
    $st = db()->prepare("SELECT type, subject, body, direction FROM lead_activities
                         WHERE lead_id = ? ORDER BY sent_at DESC LIMIT " . $limit);
    $st->execute([$leadId]);
    $rows = $st->fetchAll();
    if (!$rows) return '';
    $out = [];
    foreach ($rows as $r) {
        $who   = ($r['direction'] === 'in') ? 'Prospecto' : 'Daniel';
        $out[] = '[' . $r['type'] . '] ' . $who . ': ' . truncate((string)$r['body'], 200);
    }
    return implode("\n", $out);
}

/** Reglas de formato + contrato JSON según el canal. */
function outreach_channel_rules(string $channel): string {
    if ($channel === 'whatsapp') {
        return "CANAL: WhatsApp. Mensaje breve (2 a 5 líneas), cercano y profesional, sin asunto. "
             . "Nada de markdown ni enlaces largos. Un solo llamado a la acción claro.\n"
             . 'Responde en JSON: {"subject":"", "body":"<mensaje>"}';
    }
    return "CANAL: Email. Asunto corto y atractivo (máx. ~60 caracteres) y cuerpo de 80 a 160 palabras, "
         . "párrafos cortos, un solo llamado a la acción. Evita saludos genéricos tipo \"Estimado señor\". "
         . "No incluyas firma (se agrega automáticamente).\n"
         . 'Responde en JSON: {"subject":"<asunto>", "body":"<cuerpo sin firma>"}';
}

/** Envuelve el cuerpo en HTML simple + firma del perfil. */
function outreach_email_html(string $body): string {
    $p   = agent_profile();
    $sig = trim((string)($p['signature'] ?? ''));
    $html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1e293b">';
    $html .= '<div>' . nl2br(e($body)) . '</div>';
    if ($sig !== '') {
        $html .= '<div style="margin-top:18px;padding-top:12px;border-top:1px solid #e2e8f0;'
               . 'color:#64748b;font-size:13px;white-space:pre-line">' . nl2br(e($sig)) . '</div>';
    }
    return $html . '</div>';
}
