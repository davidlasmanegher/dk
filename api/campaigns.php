<?php
/** Endpoint de Campañas de prospección. POST JSON, respuestas {ok, ...}. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/campaigns.php';
boot();
verify_csrf_token();
require_once __DIR__ . '/../includes/auth.php';
require_auth_api();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    case 'list':
        json_out(['ok' => true, 'campaigns' => campaigns_all()]);

    case 'get': {
        $c = campaign_get((int)($d['id'] ?? 0));
        if (!$c) json_out(['ok' => false, 'error' => 'Campaña no encontrada.'], 404);
        json_out(['ok' => true, 'campaign' => $c]);
    }

    case 'save': {
        $id = campaign_save([
            'id'          => (int)($d['id'] ?? 0),
            'name'        => (string)($d['name'] ?? ''),
            'sector'      => (string)($d['sector'] ?? ''),
            'segments'    => (string)($d['segments'] ?? ''),
            'region'      => (string)($d['region'] ?? ''),
            'objective'   => (string)($d['objective'] ?? ''),
            'daily_quota' => (int)($d['daily_quota'] ?? 10),
            'channel'     => (string)($d['channel'] ?? 'auto'),
            'sequence_id' => (int)($d['sequence_id'] ?? 0),
            'status'      => (string)($d['status'] ?? 'activa'),
            'notes'       => (string)($d['notes'] ?? ''),
        ]);
        json_out(['ok' => true, 'id' => $id]);
    }

    case 'delete': {
        campaign_delete((int)($d['id'] ?? 0));
        json_out(['ok' => true]);
    }

    case 'toggle': {
        $c = campaign_get((int)($d['id'] ?? 0));
        if (!$c) json_out(['ok' => false, 'error' => 'Campaña no encontrada.'], 404);
        $new = $c['status'] === 'activa' ? 'pausada' : 'activa';
        db()->prepare("UPDATE campaigns SET status = ? WHERE id = ?")->execute([$new, (int)$c['id']]);
        json_out(['ok' => true, 'status' => $new]);
    }

    case 'run': {
        @set_time_limit(0);
        $c = campaign_get((int)($d['id'] ?? 0));
        if (!$c) json_out(['ok' => false, 'error' => 'Campaña no encontrada.'], 404);
        if (!claude_available()) json_out(['ok' => false, 'error' => 'Configura tu API key de Claude en Ajustes.'], 400);
        $r = campaign_run($c);
        json_out(['ok' => true, 'prepared' => $r['prepared'], 'failed' => $r['failed'], 'pool' => $r['pool']]);
    }

    case 'preview': {
        // Cuántos leads coinciden con un foco dado (sin crear la campaña).
        $tmp = [
            'id'       => 0,
            'sector'   => (string)($d['sector'] ?? ''),
            'segments' => (string)($d['segments'] ?? ''),
            'region'   => (string)($d['region'] ?? ''),
        ];
        json_out(['ok' => true, 'pool' => campaign_pool_count($tmp)]);
    }

    case 'report':
        json_out(['ok' => true, 'report' => campaign_report()]);

    case 'report_full': {
        $pdo = db();
        $camps = campaign_report();
        foreach ($camps as &$c) {
            $cid = (int)$c['id'];
            $c['responses'] = (int)$pdo->query("SELECT COUNT(DISTINCT a.lead_id) FROM lead_activities a JOIN campaign_leads cl ON cl.lead_id = a.lead_id AND cl.campaign_id = $cid WHERE a.direction = 'in'")->fetchColumn();
            $c['reply_rate'] = ((int)$c['contacted']) > 0 ? round($c['responses'] / $c['contacted'] * 100, 1) : 0.0;
        }
        unset($c);
        $funnel    = $pdo->query("SELECT stage, COUNT(*) c FROM leads GROUP BY stage")->fetchAll(PDO::FETCH_KEY_PAIR);
        $contacted = (int)$pdo->query("SELECT COUNT(*) FROM campaign_leads WHERE status = 'contactado'")->fetchColumn();
        $responses = (int)$pdo->query("SELECT COUNT(DISTINCT a.lead_id) FROM lead_activities a JOIN campaign_leads cl ON cl.lead_id = a.lead_id WHERE a.direction = 'in'")->fetchColumn();
        $recent = $pdo->query("SELECT a.direction, a.type, a.subject, a.body, a.sent_at, CONCAT(l.first_name,' ',COALESCE(l.last_name,'')) nm, l.company FROM lead_activities a JOIN campaign_leads cl ON cl.lead_id = a.lead_id JOIN leads l ON l.id = a.lead_id ORDER BY a.id DESC LIMIT 12")->fetchAll();
        json_out(['ok' => true,
            'totals' => [
                'campaigns'  => count($camps),
                'contacted'  => $contacted,
                'responses'  => $responses,
                'reply_rate' => $contacted > 0 ? round($responses / $contacted * 100, 1) : 0.0,
                'pending'    => (int)$pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status='sugerida' AND type='outreach'")->fetchColumn(),
                'inbox'      => (int)$pdo->query("SELECT COUNT(*) FROM inbox_messages WHERE status='pendiente' AND lead_id IS NOT NULL")->fetchColumn(),
            ],
            'campaigns' => $camps,
            'funnel'    => $funnel,
            'recent'    => $recent,
        ]);
    }

    case 'sequences':
        json_out(['ok' => true, 'sequences' => agent_sequences()]);

    case 'classify_status': {
        $pending = (int)db()->query("SELECT COUNT(*) FROM leads WHERE (industry IS NULL OR industry='') AND company IS NOT NULL AND company<>''")->fetchColumn();
        $total   = (int)db()->query("SELECT COUNT(*) FROM leads WHERE company IS NOT NULL AND company<>''")->fetchColumn();
        json_out(['ok' => true, 'pending' => $pending, 'total' => $total]);
    }

    case 'classify': {
        @set_time_limit(0);
        if (!claude_available()) json_out(['ok' => false, 'error' => 'Configura tu API key de Claude en Ajustes.'], 400);
        $r = campaign_classify_sectors(40, 1); // un lote por request; el frontend itera
        json_out(['ok' => true] + $r);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
