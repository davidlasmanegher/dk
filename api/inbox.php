<?php
/**
 * API de la bandeja de entrada (con sesión): listar, aprobar, descartar,
 * regenerar respuesta y revisar correo (IMAP) a demanda.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/inbox.php';
require_once __DIR__ . '/../includes/imap_inbox.php';
require_once __DIR__ . '/../includes/auth.php';
boot();
verify_csrf_token();
require_auth_api();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    case 'list': {
        $status = trim((string)($d['status'] ?? 'pendiente'));
        $st = db()->prepare(
            "SELECT m.*, CONCAT(l.first_name, ' ', COALESCE(l.last_name,'')) AS lead_name, l.company AS lead_company
             FROM inbox_messages m LEFT JOIN leads l ON l.id = m.lead_id
             WHERE m.status = ? ORDER BY m.created_at DESC LIMIT 80"
        );
        $st->execute([$status]);
        json_out(['ok' => true, 'messages' => $st->fetchAll()]);
    }

    case 'counts': {
        $n = (int)db()->query("SELECT COUNT(*) FROM inbox_messages WHERE status = 'pendiente'")->fetchColumn();
        json_out(['ok' => true, 'pending' => $n]);
    }

    case 'approve': {
        @set_time_limit(0);
        $r = inbox_approve((int)($d['id'] ?? 0), isset($d['body']) ? (string)$d['body'] : null);
        json_out($r, $r['ok'] ? 200 : 400);
    }

    case 'discard': {
        $r = inbox_discard((int)($d['id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    case 'regenerate': {
        @set_time_limit(0);
        $r = inbox_regenerate((int)($d['id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    case 'fetch_email': {
        @set_time_limit(0);
        $r = imap_inbox_fetch(30);
        json_out($r, $r['ok'] ? 200 : 400);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
