<?php
/** Base de conocimiento: alta, listado y baja de documentos (con sesión). */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/knowledge.php';
require_once __DIR__ . '/../includes/auth.php';
boot();
verify_csrf_token();
require_auth_api();

$d      = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    case 'list': {
        json_out(['ok' => true, 'docs' => knowledge_list(), 'embeddings' => embeddings_available()]);
    }

    case 'add': {
        @set_time_limit(0);
        $type    = trim((string)($d['type']    ?? 'caso'));
        $title   = trim((string)($d['title']   ?? ''));
        $content = trim((string)($d['content'] ?? ''));
        $source  = trim((string)($d['source']  ?? ''));
        if ($title === '' || $content === '') json_out(['ok' => false, 'error' => 'Título y contenido son obligatorios.'], 400);
        $id = knowledge_add($type, $title, $content, $source);
        $chunks = (int)db()->query("SELECT chunks FROM knowledge WHERE id = " . (int)$id)->fetchColumn();
        json_out(['ok' => true, 'id' => $id, 'chunks' => $chunks, 'embeddings' => embeddings_available()]);
    }

    case 'delete': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        knowledge_delete($id);
        json_out(['ok' => true]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
