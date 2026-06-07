<?php
/** Endpoint de Secuencias (cadencias de outreach). POST JSON, respuestas {ok, ...}. */
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

    case 'list':
        json_out(['ok' => true, 'sequences' => sequences_all()]);

    case 'get': {
        $s = sequence_get((int)($d['id'] ?? 0));
        if (!$s) json_out(['ok' => false, 'error' => 'Secuencia no encontrada.'], 404);
        json_out(['ok' => true, 'sequence' => $s]);
    }

    case 'save': {
        $steps = isset($d['steps']) && is_array($d['steps']) ? $d['steps'] : [];
        $id = sequence_save([
            'id'          => (int)($d['id'] ?? 0),
            'name'        => (string)($d['name'] ?? ''),
            'description' => (string)($d['description'] ?? ''),
            'active'      => !empty($d['active']),
            'steps'       => $steps,
        ]);
        json_out(['ok' => true, 'id' => $id]);
    }

    case 'delete': {
        sequence_delete((int)($d['id'] ?? 0));
        json_out(['ok' => true]);
    }

    case 'toggle': {
        $st = sequence_toggle((int)($d['id'] ?? 0));
        if ($st === '') json_out(['ok' => false, 'error' => 'Secuencia no encontrada.'], 404);
        json_out(['ok' => true, 'status' => $st]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
