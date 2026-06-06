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

    // Solo tareas cuya fecha programada ya llegó (o sin fecha).
    $tasks = $pdo->query("SELECT * FROM agent_tasks WHERE status = 'pendiente'
                          AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                          ORDER BY priority ASC, scheduled_at ASC, created_at ASC LIMIT " . (int)$limit)->fetchAll();

    $res = ['suggested' => 0, 'sent' => 0, 'failed' => 0, 'processed' => 0];
    foreach ($tasks as $t) {
        $res['processed']++;
        $lead = $t['lead_id'] ? outreach_get_lead((int)$t['lead_id']) : null;
        if (!$lead) { agent_mark((int)$t['id'], 'cancelada', 'Lead inexistente'); continue; }

        // Pasos de secuencia traen canal y objetivo en el payload.
        $pl      = json_decode((string)$t['payload'], true) ?: [];
        $channel = !empty($pl['channel']) ? outreach_norm_channel((string)$pl['channel']) : agent_pick_channel($lead);
        if (!empty($pl['goal'])) {
            $goal = (string)$pl['goal'];
        } else {
            $goal = ($t['type'] === 'outreach')
                ? 'Primer contacto para abrir conversación con valor, sin sonar a venta agresiva.'
                : 'Seguimiento que aporta valor y propone un siguiente paso concreto.';
        }

        $draft = outreach_generate_draft($lead, $channel, $goal);
        if (!$draft['ok']) { agent_mark((int)$t['id'], 'fallida', $draft['error']); $res['failed']++; continue; }

        // follow_up suelto del planificador → envía solo (bajo riesgo, ya en conversación).
        // outreach (primer contacto) y secuencia (todos los pasos) → sugiere para aprobar.
        if ($t['type'] === 'follow_up') {
            $sent = outreach_send_message($lead, $channel, $draft['subject'], $draft['body']);
            if ($sent['ok']) { agent_mark((int)$t['id'], 'completada', 'Enviado por ' . $sent['channel']); $res['sent']++; }
            else            { agent_mark((int)$t['id'], 'fallida', $sent['error']); $res['failed']++; }
        } else {
            $reason = ($t['type'] === 'secuencia') ? ('Paso de secuencia · ' . $channel) : 'Primer contacto';
            $payload = json_encode(array_merge($pl, [
                'channel' => $channel, 'subject' => $draft['subject'], 'body' => $draft['body'], 'reason' => $reason,
            ]), JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE agent_tasks SET status = 'sugerida', payload = ?, result = 'Borrador listo para aprobar' WHERE id = ?")
                ->execute([$payload, (int)$t['id']]);
            $res['suggested']++;
        }
    }
    return $res;
}

/** Lista las secuencias activas (id + nombre + nº de pasos). */
function agent_sequences(): array {
    $rows = db()->query("SELECT id, name, description, steps_json FROM sequences WHERE active = 1 ORDER BY name")->fetchAll();
    foreach ($rows as &$r) {
        $steps = json_decode((string)$r['steps_json'], true);
        $r['step_count'] = is_array($steps) ? count($steps) : 0;
        unset($r['steps_json']);
    }
    return $rows;
}

/**
 * Inscribe un lead en una secuencia: crea una tarea programada por paso (scheduled_at = hoy + día).
 * No re-inscribe si el lead ya tiene pasos pendientes de esa secuencia.
 * @return array{ok:bool, created?:int, error?:string}
 */
function agent_enroll_lead(int $leadId, int $seqId): array {
    $pdo = db();
    $lead = outreach_get_lead($leadId);
    if (!$lead) return ['ok' => false, 'error' => 'Lead no encontrado.'];
    $st = $pdo->prepare("SELECT * FROM sequences WHERE id = ? AND active = 1");
    $st->execute([$seqId]);
    $seq = $st->fetch();
    if (!$seq) return ['ok' => false, 'error' => 'Secuencia no encontrada o inactiva.'];

    $steps = json_decode((string)$seq['steps_json'], true);
    if (!is_array($steps) || !$steps) return ['ok' => false, 'error' => 'La secuencia no tiene pasos válidos.'];

    // Evitar doble inscripción: ¿ya hay pasos abiertos de esta secuencia para el lead?
    $dup = $pdo->prepare("SELECT COUNT(*) FROM agent_tasks WHERE lead_id = ? AND type = 'secuencia'
                          AND status IN ('pendiente','sugerida')
                          AND JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sequence_id')) = ?");
    $dup->execute([$leadId, (string)$seqId]);
    if ((int)$dup->fetchColumn() > 0) return ['ok' => false, 'error' => 'El lead ya está inscrito en esta secuencia.'];

    $ins = $pdo->prepare("INSERT INTO agent_tasks (type, lead_id, payload, status, priority, scheduled_at)
                          VALUES ('secuencia', ?, ?, 'pendiente', 4, ?)");
    $created = 0;
    foreach ($steps as $i => $step) {
        $day     = (int)($step['day'] ?? ($i * 3));
        $channel = (string)($step['channel'] ?? $step['type'] ?? 'email');
        $goal    = (string)($step['goal'] ?? $step['subject_template'] ?? 'Paso de seguimiento');
        $sched   = date('Y-m-d 09:00:00', strtotime("+{$day} days"));
        $payload = json_encode(['sequence_id' => (int)$seqId, 'sequence' => $seq['name'], 'step' => $i + 1, 'channel' => $channel, 'goal' => $goal], JSON_UNESCAPED_UNICODE);
        $ins->execute([$leadId, $payload, $sched]);
        $created++;
    }
    return ['ok' => true, 'created' => $created];
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
