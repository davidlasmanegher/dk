<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
boot();
verify_csrf_token();
require_once __DIR__ . '/../includes/auth.php';
require_auth_api();

$d = json_in();
$action = $d['action'] ?? '';

switch ($action) {

    // ── list_leads ──────────────────────────────────────────────────────────
    case 'list_leads': {
        $search   = trim((string)($d['search']   ?? ''));
        $stage    = trim((string)($d['stage']    ?? ''));
        $industry = trim((string)($d['industry'] ?? ''));
        $country  = trim((string)($d['country']  ?? ''));
        $limit    = max(1, min(200, (int)($d['limit']  ?? 20)));
        $offset   = max(0, (int)($d['offset'] ?? 0));

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = "(first_name LIKE ? OR last_name LIKE ? OR company LIKE ? OR email LIKE ?)";
            $like     = '%' . $search . '%';
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($stage !== '') { $where[] = 'stage = ?'; $params[] = $stage; }
        if ($industry !== '') { $where[] = 'industry LIKE ?'; $params[] = '%' . $industry . '%'; }
        if ($country !== '') { $where[] = 'country = ?'; $params[] = $country; }

        $sql = "SELECT * FROM leads WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $st = db()->prepare($sql);
        $st->execute($params);
        $leads = $st->fetchAll();

        // Count
        $cntSt = db()->prepare("SELECT COUNT(*) FROM leads WHERE " . implode(' AND ', $where));
        $cntParams = array_slice($params, 0, count($params) - 2);
        $cntSt->execute($cntParams);
        $total = (int)$cntSt->fetchColumn();

        json_out(['ok' => true, 'leads' => $leads, 'total' => $total]);
    }

    // ── get_lead ────────────────────────────────────────────────────────────
    case 'get_lead': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        $st = db()->prepare("SELECT * FROM leads WHERE id = ?");
        $st->execute([$id]);
        $lead = $st->fetch();
        if (!$lead) json_out(['ok' => false, 'error' => 'Lead no encontrado.'], 404);
        json_out(['ok' => true, 'lead' => $lead]);
    }

    // ── save_lead ───────────────────────────────────────────────────────────
    case 'save_lead': {
        $id         = (int)($d['id'] ?? 0);
        $first_name = trim((string)($d['first_name'] ?? ''));
        if ($first_name === '') json_out(['ok' => false, 'error' => 'El nombre es obligatorio.'], 400);

        $fields = [
            'first_name'       => $first_name,
            'last_name'        => trim((string)($d['last_name']       ?? '')),
            'company'          => trim((string)($d['company']         ?? '')),
            'role'             => trim((string)($d['role']            ?? '')),
            'industry'         => trim((string)($d['industry']        ?? '')),
            'company_size'     => trim((string)($d['company_size']    ?? '')),
            'country'          => trim((string)($d['country']         ?? 'México')),
            'city'             => trim((string)($d['city']            ?? '')),
            'email'            => trim((string)($d['email']           ?? '')),
            'phone'            => trim((string)($d['phone']           ?? '')),
            'whatsapp_phone'   => trim((string)($d['whatsapp_phone']  ?? '')),
            'linkedin_url'     => trim((string)($d['linkedin_url']    ?? '')),
            'stage'            => trim((string)($d['stage']           ?? 'prospecto')),
            'source'           => trim((string)($d['source']          ?? 'manual')),
            'score'            => max(0, min(100, (int)($d['score']   ?? 0))),
            'notes'            => trim((string)($d['notes']           ?? '')),
            'next_action'      => trim((string)($d['next_action']     ?? '')),
            'next_action_date' => trim((string)($d['next_action_date'] ?? '')) ?: null,
            'assigned_to'      => trim((string)($d['assigned_to']     ?? 'Daniel Khan')),
        ];

        if ($id) {
            $set    = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
            $st     = db()->prepare("UPDATE leads SET $set WHERE id = :id");
            $fields['id'] = $id;
            $st->execute($fields);
            json_out(['ok' => true, 'id' => $id]);
        } else {
            $cols   = implode(', ', array_keys($fields));
            $placeholders = ':' . implode(', :', array_keys($fields));
            $st     = db()->prepare("INSERT INTO leads ($cols) VALUES ($placeholders)");
            $st->execute($fields);
            $newId  = (int)db()->lastInsertId();
            json_out(['ok' => true, 'id' => $newId]);
        }
    }

    // ── delete_lead ─────────────────────────────────────────────────────────
    case 'delete_lead': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        $st = db()->prepare("DELETE FROM leads WHERE id = ?");
        $st->execute([$id]);
        json_out(['ok' => true]);
    }

    // ── add_activity ────────────────────────────────────────────────────────
    case 'add_activity': {
        $lead_id   = (int)($d['lead_id']  ?? 0);
        $type      = trim((string)($d['type']      ?? 'nota'));
        $subject   = trim((string)($d['subject']   ?? ''));
        $body      = trim((string)($d['body']      ?? ''));
        $direction = trim((string)($d['direction'] ?? 'out'));
        $status    = trim((string)($d['status']    ?? 'sent'));

        if (!$lead_id) json_out(['ok' => false, 'error' => 'lead_id inválido.'], 400);

        $st = db()->prepare("INSERT INTO lead_activities (lead_id, type, subject, body, direction, status)
                             VALUES (?, ?, ?, ?, ?, ?)");
        $st->execute([$lead_id, $type, $subject, $body, $direction, $status]);

        // Actualizar updated_at del lead
        db()->prepare("UPDATE leads SET updated_at = NOW() WHERE id = ?")->execute([$lead_id]);

        json_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
    }

    // ── list_activities ─────────────────────────────────────────────────────
    case 'list_activities': {
        $lead_id = (int)($d['lead_id'] ?? 0);
        if (!$lead_id) json_out(['ok' => false, 'error' => 'lead_id inválido.'], 400);
        $st = db()->prepare("SELECT * FROM lead_activities WHERE lead_id = ? ORDER BY sent_at DESC LIMIT 100");
        $st->execute([$lead_id]);
        json_out(['ok' => true, 'activities' => $st->fetchAll()]);
    }

    // ── Secuencias ──────────────────────────────────────────────────────────
    case 'list_sequences': {
        $rows = db()->query("SELECT * FROM sequences ORDER BY created_at DESC")->fetchAll();
        json_out(['ok' => true, 'sequences' => $rows]);
    }

    case 'get_sequence': {
        $id = (int)($d['id'] ?? 0);
        if (!$id) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);
        $st = db()->prepare("SELECT * FROM sequences WHERE id = ?");
        $st->execute([$id]);
        $seq = $st->fetch();
        if (!$seq) json_out(['ok' => false, 'error' => 'Secuencia no encontrada.'], 404);
        json_out(['ok' => true, 'sequence' => $seq]);
    }

    case 'save_sequence': {
        $id          = (int)($d['id'] ?? 0);
        $name        = trim((string)($d['name']            ?? ''));
        if ($name === '') json_out(['ok' => false, 'error' => 'El nombre es obligatorio.'], 400);
        $fields = [
            'name'            => $name,
            'description'     => trim((string)($d['description']     ?? '')),
            'target_stage'    => trim((string)($d['target_stage']    ?? '')),
            'target_industry' => trim((string)($d['target_industry'] ?? '')),
            'steps_json'      => trim((string)($d['steps_json']      ?? '[]')),
            'active'          => (int)($d['active'] ?? 1),
        ];
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
            $st  = db()->prepare("UPDATE sequences SET $set, updated_at = NOW() WHERE id = :id");
            $fields['id'] = $id;
            $st->execute($fields);
            json_out(['ok' => true, 'id' => $id]);
        } else {
            $cols = implode(', ', array_keys($fields));
            $ph   = ':' . implode(', :', array_keys($fields));
            $st   = db()->prepare("INSERT INTO sequences ($cols) VALUES ($ph)");
            $st->execute($fields);
            json_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
        }
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
