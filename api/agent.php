<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/agent.php';
boot();
verify_csrf_token();
require_once __DIR__ . '/../includes/auth.php';
require_auth_api();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    // ── save_profile ─────────────────────────────────────────────────────────
    case 'save_profile': {
        $fields = [
            'name'                => trim((string)($d['name']                ?? 'Daniel Khan')),
            'role'                => trim((string)($d['role']                ?? '')),
            'company'             => trim((string)($d['company']             ?? 'SISTEL')),
            'target_market'       => trim((string)($d['target_market']       ?? '')),
            'value_proposition'   => trim((string)($d['value_proposition']   ?? '')),
            'communication_style' => trim((string)($d['communication_style'] ?? '')),
            'objections_playbook' => trim((string)($d['objections_playbook'] ?? '')),
            'market_focus'        => trim((string)($d['market_focus']        ?? 'México')),
            'linkedin_url'        => trim((string)($d['linkedin_url']        ?? '')),
            'signature'           => trim((string)($d['signature']           ?? '')),
        ];
        $st = db()->prepare(
            "INSERT INTO agent_profile (id, name, role, company, target_market, value_proposition,
             communication_style, objections_playbook, market_focus, linkedin_url, signature)
             VALUES (1, :name, :role, :company, :target_market, :value_proposition,
             :communication_style, :objections_playbook, :market_focus, :linkedin_url, :signature)
             ON DUPLICATE KEY UPDATE
               name = VALUES(name), role = VALUES(role), company = VALUES(company),
               target_market = VALUES(target_market), value_proposition = VALUES(value_proposition),
               communication_style = VALUES(communication_style),
               objections_playbook = VALUES(objections_playbook),
               market_focus = VALUES(market_focus), linkedin_url = VALUES(linkedin_url),
               signature = VALUES(signature), updated_at = NOW()"
        );
        $st->execute($fields);
        json_out(['ok' => true]);
    }

    // ── get_profile ──────────────────────────────────────────────────────────
    case 'get_profile': {
        $st = db()->query("SELECT * FROM agent_profile WHERE id = 1");
        $profile = $st->fetch();
        json_out(['ok' => true, 'profile' => $profile ?: []]);
    }

    // ── save_settings ────────────────────────────────────────────────────────
    case 'save_settings': {
        $allowed = ['claude_api_key','claude_model','openai_api_key',
                    'whapi_token','whapi_instance_url','whapi_owner_phone',
                    'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email',
                    'imap_host','imap_port','imap_user','imap_pass','whapi_webhook_token','inbox_autoreply',
                    'linkedin_token','linkedin_author_urn',
                    'agent_auto_mode','agent_daily_limit'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $d)) {
                set_setting($key, (string)$d[$key]);
            }
        }
        json_out(['ok' => true]);
    }

    // ── get_stats ─────────────────────────────────────────────────────────────
    case 'get_stats': {
        $pdo = db();
        $totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();

        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $leadsThisWeek = (int)$pdo->prepare("SELECT COUNT(*) FROM leads WHERE created_at >= ?")->execute([$weekStart]) ? 0 : 0;
        $st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE DATE(created_at) >= ?");
        $st->execute([$weekStart]);
        $leadsThisWeek = (int)$st->fetchColumn();

        $st2 = $pdo->prepare("SELECT COUNT(*) FROM lead_activities WHERE DATE(sent_at) = CURDATE()");
        $st2->execute();
        $activitiesToday = (int)$st2->fetchColumn();

        $contentPieces = (int)$pdo->query("SELECT COUNT(*) FROM content_pieces")->fetchColumn();

        json_out([
            'ok'               => true,
            'total_leads'      => $totalLeads,
            'leads_this_week'  => $leadsThisWeek,
            'activities_today' => $activitiesToday,
            'content_pieces'   => $contentPieces,
        ]);
    }

    // ── list_tasks ────────────────────────────────────────────────────────────
    case 'list_tasks': {
        $status = trim((string)($d['status'] ?? ''));
        $limit  = max(1, min(200, (int)($d['limit'] ?? 50)));

        $where  = ['1=1'];
        $params = [];
        if ($status !== '') { $where[] = 't.status = ?'; $params[] = $status; }

        $sql = "SELECT t.*, CONCAT(l.first_name, ' ', COALESCE(l.last_name,'')) AS lead_name
                FROM agent_tasks t
                LEFT JOIN leads l ON l.id = t.lead_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.priority ASC, t.scheduled_at ASC, t.created_at ASC
                LIMIT ?";
        $params[] = $limit;
        $st = db()->prepare($sql);
        $st->execute($params);
        json_out(['ok' => true, 'tasks' => $st->fetchAll()]);
    }

    // ── create_task ───────────────────────────────────────────────────────────
    case 'create_task': {
        $type         = trim((string)($d['type']     ?? 'follow_up'));
        $lead_id      = $d['lead_id'] ? (int)$d['lead_id'] : null;
        $priority     = max(1, min(10, (int)($d['priority'] ?? 5)));
        $scheduled_at = trim((string)($d['scheduled_at'] ?? '')) ?: null;
        $payload      = !empty($d['payload']) ? json_encode($d['payload']) : null;

        $st = db()->prepare(
            "INSERT INTO agent_tasks (type, lead_id, payload, priority, scheduled_at, status)
             VALUES (?, ?, ?, ?, ?, 'pendiente')"
        );
        $st->execute([$type, $lead_id, $payload, $priority, $scheduled_at]);
        json_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
    }

    // ── update_task_status ───────────────────────────────────────────────────
    case 'update_task_status': {
        $id     = (int)($d['id']     ?? 0);
        $status = trim((string)($d['status'] ?? ''));
        $result = trim((string)($d['result'] ?? ''));
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);

        $validStatuses = ['pendiente','en_proceso','completada','fallida','cancelada'];
        if (!in_array($status, $validStatuses, true)) {
            json_out(['ok' => false, 'error' => 'Estado inválido.'], 400);
        }

        $st = db()->prepare(
            "UPDATE agent_tasks SET status = ?, result = ?,
             executed_at = CASE WHEN ? IN ('completada','fallida') THEN NOW() ELSE executed_at END
             WHERE id = ?"
        );
        $st->execute([$status, $result ?: null, $status, $id]);
        json_out(['ok' => true]);
    }

    // ── dashboard — métricas ejecutivas del panel ──────────────────────────────
    case 'dashboard': {
        $pdo = db();
        $funnel = $pdo->query("SELECT stage, COUNT(*) c FROM leads GROUP BY stage")->fetchAll(PDO::FETCH_KEY_PAIR);
        $bySeg  = $pdo->query("SELECT COALESCE(NULLIF(segment,''),'?') s, COUNT(*) c FROM leads GROUP BY segment")->fetchAll(PDO::FETCH_KEY_PAIR);
        $recent = $pdo->query(
            "SELECT a.type, a.direction, a.subject, a.body, a.sent_at,
                    CONCAT(l.first_name, ' ', COALESCE(l.last_name,'')) AS lead_name
             FROM lead_activities a LEFT JOIN leads l ON l.id = a.lead_id
             ORDER BY a.sent_at DESC LIMIT 8"
        )->fetchAll();
        json_out([
            'ok'            => true,
            'total'         => (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
            'high'          => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE score >= 80")->fetchColumn(),
            'inbox_pending' => (int)$pdo->query("SELECT COUNT(*) FROM inbox_messages WHERE status = 'pendiente'")->fetchColumn(),
            'suggestions'   => (int)$pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status = 'sugerida'")->fetchColumn(),
            'overdue'       => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE next_action_date IS NOT NULL AND next_action_date <= CURDATE() AND stage NOT IN ('ganado','perdido','pausado')")->fetchColumn(),
            'content_drafts'=> (int)$pdo->query("SELECT COUNT(*) FROM content_pieces WHERE status = 'borrador'")->fetchColumn(),
            'activity_week' => (int)$pdo->query("SELECT COUNT(*) FROM lead_activities WHERE direction = 'out' AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
            'funnel'        => $funnel,
            'by_segment'    => $bySeg,
            'recent_activity' => $recent,
        ]);
    }

    // ── plan — el planificador crea tareas según el estado de los leads ─────────
    case 'plan': {
        $n = agent_plan();
        json_out(['ok' => true, 'created' => $n]);
    }

    // ── run — planifica y procesa (híbrido: sugiere / ejecuta) ─────────────────
    case 'run': {
        @set_time_limit(0);
        $planned = agent_plan();
        $res     = agent_run();
        json_out([
            'ok'        => true,
            'planned'   => $planned,
            'suggested' => $res['suggested'],
            'sent'      => $res['sent'],
            'failed'    => $res['failed'],
            'processed' => $res['processed'],
        ]);
    }

    // ── sequences — lista de secuencias activas ────────────────────────────────
    case 'sequences': {
        json_out(['ok' => true, 'sequences' => agent_sequences()]);
    }

    // ── enroll — inscribe un lead en una secuencia ─────────────────────────────
    case 'enroll': {
        $r = agent_enroll_lead((int)($d['lead_id'] ?? 0), (int)($d['sequence_id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    // ── approve_task — aprueba y envía una sugerencia ──────────────────────────
    case 'approve_task': {
        $r = agent_approve_task((int)($d['id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    // ── discard_task — descarta una tarea pendiente o sugerida ─────────────────
    case 'discard_task': {
        $r = agent_discard_task((int)($d['id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    // ── get_task — detalle (incluye el borrador en payload_data) ───────────────
    case 'get_task': {
        $id = (int)($d['id'] ?? 0);
        $st = db()->prepare("SELECT t.*, CONCAT(l.first_name, ' ', COALESCE(l.last_name,'')) AS lead_name,
                                    l.company AS lead_company
                             FROM agent_tasks t LEFT JOIN leads l ON l.id = t.lead_id WHERE t.id = ?");
        $st->execute([$id]);
        $t = $st->fetch();
        if (!$t) json_out(['ok' => false, 'error' => 'Tarea no encontrada.'], 404);
        $t['payload_data'] = json_decode((string)$t['payload'], true);
        json_out(['ok' => true, 'task' => $t]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
