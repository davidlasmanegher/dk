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

    case 'sequences':
        json_out(['ok' => true, 'sequences' => agent_sequences()]);

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
