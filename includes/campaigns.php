<?php
/**
 * Campañas de prospección estratégica.
 *
 * Una campaña define un FOCO (sector, segmentos, región), un OBJETIVO, un CUPO
 * diario y una CADENCIA. Cada día (cron) o a demanda, la campaña:
 *   1. Selecciona los N mejores leads del foco que aún no tocó (por score).
 *   2. Genera el primer contacto personalizado con la voz de Daniel.
 *   3. Lo deja como SUGERENCIA ('sugerida') para aprobar en la Bandeja del Agente.
 * Al aprobar (agent_approve_task), se envía y se inscribe en la cadencia de seguimiento.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/agent.php';      // agent_pick_channel, agent_enroll_lead
require_once __DIR__ . '/outreach.php';   // outreach_generate_draft, outreach_norm_channel

/** Lista las campañas con métricas (inscritos, contactados, pool restante). */
function campaigns_all(): array {
    $rows = db()->query("SELECT * FROM campaigns ORDER BY (status='activa') DESC, created_at DESC")->fetchAll();
    foreach ($rows as &$c) {
        $cid = (int)$c['id'];
        $c['total_enrolled']  = (int)db()->query("SELECT COUNT(*) FROM campaign_leads WHERE campaign_id={$cid}")->fetchColumn();
        $c['total_contacted'] = (int)db()->query("SELECT COUNT(*) FROM campaign_leads WHERE campaign_id={$cid} AND status='contactado'")->fetchColumn();
        $c['pending_approval']= (int)db()->query("SELECT COUNT(*) FROM campaign_leads cl JOIN agent_tasks t ON t.id=cl.task_id WHERE cl.campaign_id={$cid} AND t.status='sugerida'")->fetchColumn();
        $c['pool']            = campaign_pool_count($c);
    }
    return $rows;
}

/** Carga una campaña por id. */
function campaign_get(int $id): ?array {
    $st = db()->prepare("SELECT * FROM campaigns WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Crea o actualiza una campaña. Devuelve el id. */
function campaign_save(array $f): int {
    $pdo  = db();
    $cols = ['name','sector','segments','region','objective','daily_quota','channel','sequence_id','status','notes'];
    $data = [];
    foreach ($cols as $col) $data[$col] = $f[$col] ?? null;
    $data['name']        = trim((string)($data['name'] ?? '')) ?: 'Campaña sin nombre';
    $data['sector']      = trim((string)($data['sector'] ?? ''));
    $data['segments']    = trim((string)($data['segments'] ?? ''));
    $data['region']      = trim((string)($data['region'] ?? ''));
    $data['daily_quota'] = max(1, min(100, (int)($data['daily_quota'] ?? 10)));
    $data['channel']     = in_array(($data['channel'] ?? 'auto'), ['auto','email','whatsapp'], true) ? $data['channel'] : 'auto';
    $data['status']      = in_array(($data['status'] ?? 'activa'), ['activa','pausada','finalizada'], true) ? $data['status'] : 'activa';
    $data['sequence_id'] = !empty($data['sequence_id']) ? (int)$data['sequence_id'] : null;

    $id = (int)($f['id'] ?? 0);
    if ($id) {
        $set = implode(', ', array_map(function ($c) { return "$c = :$c"; }, $cols));
        $data['id'] = $id;
        $pdo->prepare("UPDATE campaigns SET $set, updated_at = NOW() WHERE id = :id")->execute($data);
        return $id;
    }
    $ph = ':' . implode(', :', $cols);
    $pdo->prepare("INSERT INTO campaigns (" . implode(', ', $cols) . ") VALUES ($ph)")->execute($data);
    return (int)$pdo->lastInsertId();
}

/** Elimina una campaña (sus campaign_leads caen por CASCADE; las tareas quedan). */
function campaign_delete(int $id): void {
    db()->prepare("DELETE FROM campaigns WHERE id = ?")->execute([$id]);
}

/** Construye el WHERE del foco (sector/segmentos/región) + params. Solo prospectos. */
function campaign_match_where(array $c): array {
    $where  = ["l.stage = 'prospecto'"];
    $params = [];
    $sector = trim((string)($c['sector'] ?? ''));
    if ($sector !== '') {
        $where[]  = "(l.industry LIKE ? OR l.company LIKE ?)";
        $params[] = "%{$sector}%";
        $params[] = "%{$sector}%";
    }
    $segs = array_values(array_filter(array_map('trim', explode(',', (string)($c['segments'] ?? '')))));
    if ($segs) {
        $ph       = implode(',', array_fill(0, count($segs), '?'));
        $where[]  = "l.segment IN ({$ph})";
        foreach ($segs as $s) $params[] = strtoupper(substr($s, 0, 1));
    }
    $region = trim((string)($c['region'] ?? ''));
    if ($region !== '') {
        $where[]  = "(l.region LIKE ? OR l.city LIKE ? OR l.country LIKE ?)";
        $params[] = "%{$region}%"; $params[] = "%{$region}%"; $params[] = "%{$region}%";
    }
    return [implode(' AND ', $where), $params];
}

/** Cuántos leads quedan en el foco sin estar ya inscritos en la campaña. */
function campaign_pool_count(array $c): int {
    [$where, $params] = campaign_match_where($c);
    $sql = "SELECT COUNT(*) FROM leads l
            WHERE {$where}
              AND NOT EXISTS (SELECT 1 FROM campaign_leads cl WHERE cl.campaign_id = ? AND cl.lead_id = l.id)";
    $params[] = (int)($c['id'] ?? 0);
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

/** Selecciona los N mejores candidatos del foco aún no inscritos (por score). */
function campaign_select_candidates(array $c, int $limit): array {
    [$where, $params] = campaign_match_where($c);
    $sql = "SELECT l.* FROM leads l
            WHERE {$where}
              AND NOT EXISTS (SELECT 1 FROM campaign_leads cl WHERE cl.campaign_id = ? AND cl.lead_id = l.id)
            ORDER BY l.score DESC, l.id ASC
            LIMIT " . (int)$limit;
    $params[] = (int)($c['id'] ?? 0);
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Corre una campaña: selecciona hasta el cupo diario, genera el primer contacto
 * y lo deja como 'sugerida' para aprobar. No envía nada por su cuenta.
 * @return array{prepared:int, failed:int, pool:int}
 */
function campaign_run(array $c): array {
    $pdo       = db();
    $quota     = max(1, (int)($c['daily_quota'] ?? 10));
    $seqId     = (int)($c['sequence_id'] ?? 0);
    $objective = trim((string)($c['objective'] ?? '')) ?: 'Primer contacto para abrir conversación con valor, sin sonar a venta agresiva.';
    $cands     = campaign_select_candidates($c, $quota);

    $prepared = 0; $failed = 0;
    foreach ($cands as $lead) {
        $leadId = (int)$lead['id'];

        // Reserva el cupo (la UNIQUE evita duplicados aunque corra dos veces el mismo día).
        try {
            $pdo->prepare("INSERT INTO campaign_leads (campaign_id, lead_id, status) VALUES (?, ?, 'seleccionado')")
                ->execute([(int)$c['id'], $leadId]);
        } catch (Throwable $e) { continue; }

        $chan    = (string)($c['channel'] ?? 'auto');
        $channel = ($chan === 'auto' || $chan === '') ? agent_pick_channel($lead) : outreach_norm_channel($chan);

        $draft = outreach_generate_draft($lead, $channel, $objective, 'Campaña de prospección: ' . $c['name']);
        if (!$draft['ok']) {
            $pdo->prepare("UPDATE campaign_leads SET status = 'descartado' WHERE campaign_id = ? AND lead_id = ?")
                ->execute([(int)$c['id'], $leadId]);
            $failed++; continue;
        }

        $payload = json_encode([
            'reason'             => 'Campaña: ' . $c['name'],
            'campaign_id'        => (int)$c['id'],
            'channel'            => $channel,
            'subject'            => $draft['subject'],
            'body'               => $draft['body'],
            'goal'               => $objective,
            'enroll_sequence_id' => $seqId,
        ], JSON_UNESCAPED_UNICODE);
        $pdo->prepare("INSERT INTO agent_tasks (type, lead_id, payload, status, priority, result)
                       VALUES ('outreach', ?, ?, 'sugerida', 3, 'Borrador de campaña listo para aprobar')")
            ->execute([$leadId, $payload]);
        $taskId = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE campaign_leads SET task_id = ? WHERE campaign_id = ? AND lead_id = ?")
            ->execute([$taskId, (int)$c['id'], $leadId]);
        $prepared++;
    }
    $pdo->prepare("UPDATE campaigns SET last_run_at = NOW() WHERE id = ?")->execute([(int)$c['id']]);
    return ['prepared' => $prepared, 'failed' => $failed, 'pool' => campaign_pool_count($c)];
}

/** Corre todas las campañas activas (para el cron diario). */
function campaign_run_all(): array {
    $out = [];
    $cs  = db()->query("SELECT * FROM campaigns WHERE status = 'activa'")->fetchAll();
    foreach ($cs as $c) {
        $r = campaign_run($c);
        $out[] = ['campaign' => $c['name'], 'prepared' => $r['prepared'], 'failed' => $r['failed']];
    }
    return $out;
}

/** Reporte de gestión: estado y métricas por campaña. */
function campaign_report(): array {
    $report = [];
    foreach (campaigns_all() as $c) {
        $report[] = [
            'id'               => (int)$c['id'],
            'name'             => $c['name'],
            'status'           => $c['status'],
            'sector'           => $c['sector'],
            'segments'         => $c['segments'],
            'region'           => $c['region'],
            'daily_quota'      => (int)$c['daily_quota'],
            'enrolled'         => (int)$c['total_enrolled'],
            'contacted'        => (int)$c['total_contacted'],
            'pending_approval' => (int)$c['pending_approval'],
            'pool'             => (int)$c['pool'],
            'last_run_at'      => $c['last_run_at'],
        ];
    }
    return $report;
}

/** Sectores válidos para la clasificación (controla el vocabulario). */
function campaign_sectors_vocab(): string {
    return 'farma, salud, financiero, seguros, banca, manufactura, consumo masivo, retail, '
         . 'tecnología, telecomunicaciones, energía, construcción, educación, gobierno, '
         . 'automotriz, alimentos y bebidas, logística, minería, turismo, servicios profesionales, otro';
}

/**
 * Clasifica el sector (industry) de leads que no lo tienen, infiriéndolo de la empresa con IA.
 * Procesa en lotes. maxBatches=0 = todos los lotes hasta agotar.
 * @return array{updated:int, batches:int, remaining:int}
 */
function campaign_classify_sectors(int $batchSize = 40, int $maxBatches = 0): array {
    $pdo     = db();
    $updated = 0; $batches = 0;
    $vocab   = campaign_sectors_vocab();

    $fail = 0;
    $aborted = '';
    while ($maxBatches === 0 || $batches < $maxBatches) {
        $rows = $pdo->query("SELECT id, company FROM leads
                             WHERE (industry IS NULL OR industry = '')
                               AND company IS NOT NULL AND company <> ''
                             ORDER BY id LIMIT " . (int)$batchSize)->fetchAll();
        if (!$rows) break;

        $list = '';
        foreach ($rows as $r) $list .= $r['id'] . ': ' . $r['company'] . "\n";

        $system = "Sos un clasificador de empresas (mayormente mexicanas) por sector/industria. "
                . "Devolvés ÚNICAMENTE un objeto JSON que mapea el id de cada empresa a su sector. "
                . "Sectores válidos (elegí el más cercano, en minúsculas, exactamente uno): {$vocab}. "
                . "Si no podés determinarlo, usá 'otro'. No agregues texto fuera del JSON.";
        $user = "Clasificá estas empresas (formato 'id: nombre'):\n\n{$list}\n\n"
              . "Respondé SOLO el JSON: {\"<id>\": \"<sector>\", ...}";

        $r = claude_call($system, $user, 1800, 0);
        $batches++;
        // Sin créditos o credencial inválida: abortar SIN ensuciar la base (no marcar 'otro').
        if (!$r['ok'] && preg_match('/credit|balance|billing|insufficient|invalid x-api-key|authentication|api key/i', $r['error'])) {
            $aborted = $r['error'];
            break;
        }
        $map = $r['ok'] ? extract_json($r['text']) : null;

        // Tolerancia a fallos transitorios: reintenta una vez; si insiste, marca 'otro' y avanza.
        if (!is_array($map)) {
            $fail++;
            if ($fail >= 2) {
                $ids = array_map(function ($x) { return (int)$x['id']; }, $rows);
                $pdo->exec("UPDATE leads SET industry = 'otro' WHERE id IN (" . implode(',', $ids) . ") AND (industry IS NULL OR industry = '')");
                $fail = 0;
            } else {
                usleep(2000000); // backoff ante posible rate limit
            }
            continue;
        }
        $fail = 0;

        $upd = $pdo->prepare("UPDATE leads SET industry = ? WHERE id = ? AND (industry IS NULL OR industry = '')");
        foreach ($map as $id => $sector) {
            $sector = trim((string)$sector);
            if ($sector === '') $sector = 'otro';
            $upd->execute([mb_substr($sector, 0, 120), (int)$id]);
            $updated++;
        }
    }

    $remaining = (int)$pdo->query("SELECT COUNT(*) FROM leads
                                   WHERE (industry IS NULL OR industry = '')
                                     AND company IS NOT NULL AND company <> ''")->fetchColumn();
    return ['updated' => $updated, 'batches' => $batches, 'remaining' => $remaining, 'aborted' => $aborted];
}
