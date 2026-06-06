<?php
/** Autenticación: alta del primer admin (setup), login y logout. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
boot();
verify_csrf_token();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    // ── setup — crea el primer usuario (solo si no existe ninguno) ──────────────
    case 'setup': {
        if (auth_user_count() > 0) {
            json_out(['ok' => false, 'error' => 'El acceso ya está configurado. Iniciá sesión.'], 403);
        }
        $username = (string)($d['username'] ?? '');
        $password = (string)($d['password'] ?? '');
        $r = auth_create_user($username, $password, (string)($d['name'] ?? ''));
        if (!$r['ok']) json_out($r, 400);
        auth_login($username, $password);   // entra directo
        json_out(['ok' => true]);
    }

    // ── login ───────────────────────────────────────────────────────────────
    case 'login': {
        $r = auth_login((string)($d['username'] ?? ''), (string)($d['password'] ?? ''));
        json_out($r, $r['ok'] ? 200 : 401);
    }

    // ── logout ──────────────────────────────────────────────────────────────
    case 'logout': {
        auth_logout();
        json_out(['ok' => true]);
    }

    // ── change_password (requiere sesión) ──────────────────────────────────────
    case 'change_password': {
        require_auth_api();
        $r = auth_change_password((string)($d['current'] ?? ''), (string)($d['new'] ?? ''));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    // ── create_user (requiere sesión) ──────────────────────────────────────────
    case 'create_user': {
        require_auth_api();
        $r = auth_create_user((string)($d['username'] ?? ''), (string)($d['password'] ?? ''), (string)($d['name'] ?? ''));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    // ── list_users (requiere sesión) ───────────────────────────────────────────
    case 'list_users': {
        require_auth_api();
        json_out(['ok' => true, 'users' => auth_list_users()]);
    }

    // ── delete_user (requiere sesión) ──────────────────────────────────────────
    case 'delete_user': {
        require_auth_api();
        $r = auth_delete_user((int)($d['id'] ?? 0));
        json_out($r, $r['ok'] ? 200 : 400);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
