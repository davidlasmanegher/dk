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

/**
 * Guardrail de prueba social (por código, no por prompt): si el texto enumera
 * "empresas/clientes como X, Y, Z" y alguno NO está en la lista aprobada (setting
 * social_proof), reemplaza la enumeración por los aprobados. Evita que el modelo
 * invente clientes (J&J, Pfizer, etc.) pese a las reglas del system prompt.
 */
function scrub_social_proof(string $text): string {
    if (trim($text) === '') return $text;
    $prof = function_exists('agent_profile') ? agent_profile() : [];
    $wl = array_values(array_filter(array_map('trim', explode(',', (string)($prof['social_proof'] ?? '')))));
    $pattern = '/\b(empresas|compañías|companias|clientes|marcas|organizaciones)\s+como\s+([^.;\n]+)/iu';
    if (empty($wl)) {
        return preg_replace($pattern, '$1 líderes de su industria', $text);
    }
    $wlLower = array_map(function ($w) { return mb_strtolower($w); }, $wl);
    return preg_replace_callback($pattern, function ($m) use ($wl, $wlLower) {
        $parts = preg_split('/\s*,\s*|\s+y\s+|\s*&\s*/u', trim($m[2])) ?: [];
        $unapproved = false;
        foreach ($parts as $p) {
            $p = trim($p); if ($p === '') continue;
            $ok = false;
            foreach ($wlLower as $w) {
                if ($w !== '' && (mb_stripos($p, $w) !== false || mb_stripos($w, mb_strtolower($p)) !== false)) { $ok = true; break; }
            }
            if (!$ok) { $unapproved = true; break; }
        }
        if (!$unapproved) return $m[0];
        $use = array_slice($wl, 0, 3);
        $joined = count($use) > 1 ? implode(', ', array_slice($use, 0, -1)) . ' y ' . end($use) : ($use[0] ?? '');
        return $m[1] . ' como ' . $joined;
    }, $text);
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
    $company = trim((string)($lead['company'] ?? ''));
    if ($company !== '') {
        $user .= "\nPERSONALIZACIÓN OBLIGATORIA: escribí un correo 100% personalizado a {$company}. Nombrá a {$company} y hablá de SU realidad y SUS retos en concreto; NO uses generalidades como \"las organizaciones\" o \"las empresas\".\n";
    }
    $user .= "\nFORMATO DEL CUERPO: párrafos cortos en texto natural; podés usar **negrita** para destacar conceptos clave (se convierte a HTML). NO uses viñetas con asteriscos ni encabezados markdown (#).";
    $user .= "\nResponde SOLO con JSON válido, sin texto extra.";

    $r = claude_call($system, $user, 1500, 0.7);
    if (!$r['ok']) return ['ok' => false, 'subject' => '', 'body' => '', 'error' => $r['error']];

    $parsed = extract_json($r['text']);
    if (!is_array($parsed)) $parsed = ['subject' => '', 'body' => trim($r['text'])];
    return ['ok' => true,
            'subject' => scrub_social_proof((string)($parsed['subject'] ?? '')),
            'body'    => scrub_social_proof((string)($parsed['body'] ?? '')),
            'error'   => ''];
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

/** Envuelve el cuerpo en HTML + firma. Convierte markdown ligero (negritas, párrafos) y elimina asteriscos sueltos. */
function outreach_email_html(string $body): string {
    $p   = agent_profile();
    $sig = trim((string)($p['signature'] ?? ''));

    $esc = e(trim($body));                                                   // escapar HTML
    $esc = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $esc);    // **negrita** -> <strong>
    $esc = str_replace('*', '', $esc);                                       // quitar asteriscos residuales
    $blocks = preg_split('/\n\s*\n/', $esc);                                 // párrafos por doble salto
    $bodyHtml = '';
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b !== '') $bodyHtml .= '<p style="margin:0 0 14px">' . nl2br($b) . '</p>';
    }

    $html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1e293b">';
    $html .= $bodyHtml;
    $html .= outreach_signature_html();
    return $html . '</div>';
}

/** Firma corporativa HTML de Daniel (maquetada, con links, liviana para buena entregabilidad). */
function outreach_signature_html(): string {
    return '<table style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;margin-top:22px"><tr><td style="padding-top:14px;border-top:2px solid #2563eb">'
        . '<div style="font-size:17px;font-weight:700;color:#0f172a">Daniel Khan</div>'
        . '<div style="display:inline-block;background:#2563eb;color:#ffffff;font-size:12px;font-weight:600;padding:3px 10px;border-radius:5px;margin:5px 0">Senior Business Developer LATAM</div>'
        . '<div style="margin-top:6px;color:#334155"><a href="mailto:daniel.khan@sistelco.com.mx" style="color:#2563eb;text-decoration:none">daniel.khan@sistelco.com.mx</a>&nbsp; ·&nbsp; <a href="tel:+525598162472" style="color:#334155;text-decoration:none">+52 55 9816 2472</a></div>'
        . '<div style="margin-top:3px;color:#334155"><a href="https://www.sistelco.com.mx" style="color:#2563eb;text-decoration:none">www.sistelco.com.mx</a>&nbsp; ·&nbsp; <a href="https://www.linkedin.com/company/sistel-m%C3%A9xico/" style="color:#2563eb;text-decoration:none">LinkedIn: sistel-méxico</a></div>'
        . '<div style="margin-top:4px;color:#64748b;font-size:12px">Bosque Real 8, Depto 604, Huixquilucan, Estado de México, C.P. 52770</div>'
        . '<div style="margin-top:7px;font-size:12px;color:#0f172a;font-weight:700;letter-spacing:.5px">SISTEL&nbsp; <span style="font-weight:400;color:#94a3b8">| Empresa B Certificada</span></div>'
        . '</td></tr></table>';
}
