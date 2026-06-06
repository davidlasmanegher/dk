<?php
/**
 * Outreach 1:1 a leads — genera y ENVÍA email / WhatsApp en la voz de Daniel.
 * La lógica vive en includes/outreach.php (compartida con el runner del agente).
 * Generar y enviar están separados: el humano revisa antes de mandar.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/outreach.php';
require_once __DIR__ . '/../includes/auth.php';
boot();
verify_csrf_token();
require_auth_api();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    // ── generate — redacta el borrador, NO envía ───────────────────────────────
    case 'generate': {
        $lead_id = (int)($d['lead_id'] ?? 0);
        if (!$lead_id) json_out(['ok' => false, 'error' => 'Falta el lead.'], 400);
        $lead = outreach_get_lead($lead_id);
        if (!$lead) json_out(['ok' => false, 'error' => 'Lead no encontrado.'], 404);

        $channel = outreach_norm_channel((string)($d['channel'] ?? 'email'));
        $r = outreach_generate_draft($lead, $channel, trim((string)($d['goal'] ?? '')), trim((string)($d['context'] ?? '')));
        if (!$r['ok']) json_out(['ok' => false, 'error' => $r['error']], 502);

        json_out(['ok' => true, 'channel' => $channel, 'subject' => $r['subject'], 'body' => $r['body']]);
    }

    // ── send — envía de verdad y registra la actividad ─────────────────────────
    case 'send': {
        $lead_id = (int)($d['lead_id'] ?? 0);
        if (!$lead_id) json_out(['ok' => false, 'error' => 'Falta el lead.'], 400);
        $lead = outreach_get_lead($lead_id);
        if (!$lead) json_out(['ok' => false, 'error' => 'Lead no encontrado.'], 404);

        $r = outreach_send_message(
            $lead,
            (string)($d['channel'] ?? 'email'),
            (string)($d['subject'] ?? ''),
            (string)($d['body'] ?? '')
        );
        if (!$r['ok']) json_out(['ok' => false, 'error' => $r['error']], 502);

        json_out(['ok' => true, 'channel' => $r['channel']]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
