<?php
/**
 * Runner del agente autónomo — modo HÍBRIDO:
 *   - SUGIERE (deja para aprobar) los primeros contactos a prospectos.
 *   - EJECUTA solo los seguimientos a leads que ya están en conversación.
 *
 * Tres piezas: agent_plan() crea tareas, agent_run() las procesa,
 * agent_approve_task()/agent_discard_task() resuelven las sugerencias.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/outreach.php';

/** Días sin actividad para disparar un seguimiento. */
function agent_followup_days(): int {
    return max(1, (int)(setting('agent_followup_days', '4') ?: 4));
}

/** Etapas "en conversación" donde se permite ejecutar follow-up automático. */
function agent_active_stages(): array {
    return ['contactado', 'interesado', 'propuesta', 'negociacion'];
}

/** Canal preferido para un lead: WhatsApp si tiene número, si no email. */
function agent_pick_channel(array $lead): string {
    return (trim((string)($lead['whatsapp_phone'] ?? '')) !== '') ? 'whatsapp' : 'email';
}

/**
 * Planificador: crea agent_tasks 'pendiente' según el estado de los leads.
 * No duplica: omite leads que ya tienen una tarea 'pendiente' o 'sugerida'.
 * @return int cantidad de tareas creadas
 */
function agent_plan(): int {
    $pdo  = db();
    $days = agent_followup_days();
    $rows = $pdo->query("
        SELECT l.* FROM leads l
        WHERE l.stage NOT IN ('ganado','perdido','pausado','archivado')
          AND NOT EXISTS (
              SELECT 1 FROM agent_tasks t
              WHERE t.lead_id = l.id AND t.status IN ('pendiente','sugerida')
          )
    ")->fetchAll();

    $created = 0;
    foreach ($rows as $l) {
        $lead_id = (int)$l['id'];
        $hasOut  = (int)$pdo->query("SELECT COUNT(*) FROM lead_activities WHERE lead_id = {$lead_id} AND direction = 'out'")->fetchColumn() > 0;

        $type = null;
        if (!$hasOut && $l['stage'] === 'prospecto') {
            $type = 'outreach';            // primer contacto → se sugerirá
        } else {
            $due = false;
            if (!empty($l['next_action_date']) && $l['next_action_date'] <= date('Y-m-d')) {
                $due = true;
            } else {
                $last = $pdo->query("SELECT MAX(sent_at) FROM lead_activities WHERE lead_id = {$lead_id}")->fetchColumn();
                if ($last && strtotime($last) <= strtotime("-{$days} days")) $due = true;
            }
            if ($due && in_array($l['stage'], agent_active_stages(), true)) {
                $type = 'follow_up';       // seguimiento → se ejecutará
            }
        }

        if ($type) {
            $pdo->prepare("INSERT INTO agent_tasks (type, lead_id, payload, status, priority) VALUES (?, ?, ?, 'pendiente', 5)")
                ->execute([$type, $lead_id, json_encode(['reason' => $type === 'outreach' ? 'Primer contacto' : 'Seguimiento'])]);
            $created++;
        }
    }
    return $created;
}

/**
 * Ejecutor (modo híbrido): procesa tareas 'pendiente' hasta el límite diario.
 *   - 'outreach' (primer contacto) → genera borrador → 'sugerida' (espera aprobación).
 *   - 'follow_up'                  → genera + ENVÍA → 'completada'.
 * @return array{suggested:int, sent:int, failed:int, processed:int}
 */
function agent_run(int $limit = 0): array {
    $pdo = db();
    if ($limit <= 0) $limit = max(1, (int)(setting('agent_daily_limit', '20') ?: 20));

    $tasks = $pdo->query("SELECT * FROM agent_tasks WHERE status = 'pendiente'
                          ORDER BY priority ASC, created_at ASC LIMIT " . (int)$limit)->fetchAll();

    $res = ['suggested' => 0, 'sent' => 0, 'failed' => 0, 'processed' => 0];
    foreach ($tasks as $t) {
        $res['processed']++;
        $lead = $t['lead_id'] ? outreach_get_lead((int)$t['lead_id']) : null;
        if (!$lead) { agent_mark((int)$t['id'], 'cancelada', 'Lead inexistente'); continue; }

        $channel = agent_pick_channel($lead);
        $goal    = ($t['type'] === 'outreach')
            ? 'Primer contacto para abrir conversación con valor, sin sonar a venta agresiva.'
            : 'Seguimiento que aporta valor y propone un siguiente paso concreto.';

        $draft = outreach_generate_draft($lead, $channel, $goal);
        if (!$draft['ok']) { agent_mark((int)$t['id'], 'fallida', $draft['error']); $res['failed']++; continue; }

        if ($t['type'] === 'follow_up') {
            // Bajo riesgo (ya en conversación) → enviar solo.
            $sent = outreach_send_message($lead, $channel, $draft['subject'], $draft['body']);
            if ($sent['ok']) { agent_mark((int)$t['id'], 'completada', 'Enviado por ' . $sent['channel']); $res['sent']++; }
            else            { agent_mark((int)$t['id'], 'fallida', $sent['error']); $res['failed']++; }
        } else {
            // Primer contacto → sugerir (guardar borrador, esperar aprobación humana).
            $payload = json_encode([
                'channel' => $channel,
                'subject' => $draft['subject'],
                'body'    => $draft['body'],
                'reason'  => 'Primer contacto',
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE agent_tasks SET status = 'sugerida', payload = ?, result = 'Borrador listo para aprobar' WHERE id = ?")
                ->execute([$payload, (int)$t['id']]);
            $res['suggested']++;
        }
    }
    return $res;
}

/** Aprueba y envía una tarea 'sugerida'. */
function agent_approve_task(int $id): array {
    $pdo = db();
    $st  = $pdo->prepare("SELECT * FROM agent_tasks WHERE id = ? AND status = 'sugerida'");
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) return ['ok' => false, 'error' => 'Sugerencia no encontrada o ya procesada.'];

    $lead = $t['lead_id'] ? outreach_get_lead((int)$t['lead_id']) : null;
    if (!$lead) return ['ok' => false, 'error' => 'Lead inexistente.'];

    $p    = json_decode((string)$t['payload'], true) ?: [];
    $sent = outreach_send_message($lead, (string)($p['channel'] ?? 'email'), (string)($p['subject'] ?? ''), (string)($p['body'] ?? ''));
    if (!$sent['ok']) return ['ok' => false, 'error' => $sent['error']];

    agent_mark($id, 'completada', 'Aprobado y enviado por ' . $sent['channel']);
    return ['ok' => true];
}

/** Descarta una tarea pendiente o sugerida. */
function agent_discard_task(int $id): array {
    db()->prepare("UPDATE agent_tasks SET status = 'cancelada', result = 'Descartada' WHERE id = ? AND status IN ('sugerida','pendiente')")
        ->execute([$id]);
    return ['ok' => true];
}

/** Marca una tarea con estado/resultado y sella executed_at. */
function agent_mark(int $id, string $status, string $result = ''): void {
    db()->prepare("UPDATE agent_tasks SET status = ?, result = ?, executed_at = NOW() WHERE id = ?")
        ->execute([$status, $result, $id]);
}
