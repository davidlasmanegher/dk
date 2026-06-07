<?php
/**
 * Chat con Daniel (copiloto conversacional con tool use).
 * Daniel consulta el CRM, el pipeline y el conocimiento, redacta y guarda borradores,
 * e inscribe leads en secuencias. Nunca envía a un cliente: deja todo para aprobar.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/agent.php';        // trae outreach, knowledge, claude, whapi, mailer
require_once __DIR__ . '/../includes/leads_intel.php';
require_once __DIR__ . '/../includes/campaigns.php';     // campañas de prospección
require_once __DIR__ . '/../includes/auth.php';
boot();
verify_csrf_token();
require_auth_api();
@set_time_limit(0);

$d      = json_in();
$action = $d['action'] ?? '';

// ── Definición de herramientas (function calling) ──────────────────────────────
function chat_tools(): array {
    return [
        ['name' => 'buscar_leads', 'description' => 'Busca leads en el CRM por filtros (segmento A-E, etapa, texto libre, score mínimo).',
         'input_schema' => ['type' => 'object', 'properties' => [
            'segmento' => ['type' => 'string', 'description' => 'A, B, C, D o E'],
            'etapa'    => ['type' => 'string', 'description' => 'prospecto, contactado, interesado, propuesta, negociacion, ganado'],
            'texto'    => ['type' => 'string', 'description' => 'nombre, empresa o palabra clave'],
            'min_score'=> ['type' => 'integer'],
            'limite'   => ['type' => 'integer', 'description' => 'máximo de resultados (default 10)'],
         ]]],
        ['name' => 'resumen_pipeline', 'description' => 'Resumen del pipeline: totales, alta prioridad, embudo por etapa, leads por segmento y pendientes (bandeja, sugerencias, seguimientos vencidos).',
         'input_schema' => ['type' => 'object', 'properties' => new stdClass()]],
        ['name' => 'ver_lead', 'description' => 'Trae los datos y el historial reciente de un lead por su id.',
         'input_schema' => ['type' => 'object', 'properties' => ['lead_id' => ['type' => 'integer']], 'required' => ['lead_id']]],
        ['name' => 'buscar_conocimiento', 'description' => 'Busca pasajes en la base de conocimiento de SISTEL (casos, propuestas, guías).',
         'input_schema' => ['type' => 'object', 'properties' => ['consulta' => ['type' => 'string']], 'required' => ['consulta']]],
        ['name' => 'guardar_contenido', 'description' => 'Guarda una pieza de contenido como borrador. Vos redactás el cuerpo en tu voz.',
         'input_schema' => ['type' => 'object', 'properties' => [
            'tipo'   => ['type' => 'string', 'description' => 'post_linkedin, articulo, email_seguimiento, mensaje_prospecto o propuesta'],
            'titulo' => ['type' => 'string'],
            'cuerpo' => ['type' => 'string'],
         ], 'required' => ['tipo', 'titulo', 'cuerpo']]],
        ['name' => 'guardar_borrador_outreach', 'description' => 'Guarda un borrador de mensaje (email o whatsapp) para un lead, listo para que el equipo lo revise y envíe. Vos redactás.',
         'input_schema' => ['type' => 'object', 'properties' => [
            'lead_id' => ['type' => 'integer'],
            'canal'   => ['type' => 'string', 'description' => 'email o whatsapp'],
            'asunto'  => ['type' => 'string'],
            'cuerpo'  => ['type' => 'string'],
         ], 'required' => ['lead_id', 'canal', 'cuerpo']]],
        ['name' => 'inscribir_secuencia', 'description' => 'Inscribe un lead en una secuencia de prospección (por defecto la cadencia 5x21).',
         'input_schema' => ['type' => 'object', 'properties' => [
            'lead_id'     => ['type' => 'integer'],
            'sequence_id' => ['type' => 'integer', 'description' => 'opcional; default 1'],
         ], 'required' => ['lead_id']]],
        ['name' => 'estado_campanas', 'description' => 'Estado de las campañas de prospección: foco, cupo diario, leads en pool, tocados, contactados y pendientes de aprobar.',
         'input_schema' => ['type' => 'object', 'properties' => new stdClass()]],
        ['name' => 'crear_campana', 'description' => 'Crea una campaña de prospección con foco (sector, segmentos, región), objetivo y cupo diario. Queda activa: cada día selecciona los mejores leads del foco y prepara el primer contacto para aprobar.',
         'input_schema' => ['type' => 'object', 'properties' => [
            'nombre'      => ['type' => 'string'],
            'sector'      => ['type' => 'string', 'description' => 'foco sectorial, ej: farma, manufactura, financiero'],
            'segmentos'   => ['type' => 'string', 'description' => 'opcional, CSV de segmentos A-E (ej "A,D"); vacío = todos'],
            'region'      => ['type' => 'string', 'description' => 'opcional, ej: México, CDMX, Monterrey'],
            'objetivo'    => ['type' => 'string', 'description' => 'qué se busca lograr con el primer contacto'],
            'cupo_diario' => ['type' => 'integer', 'description' => 'contactos por día, default 10'],
         ], 'required' => ['nombre', 'sector']]],
        ['name' => 'correr_campana', 'description' => 'Ejecuta ahora una campaña por su id: selecciona los leads del día y prepara los borradores de primer contacto para aprobar en la Bandeja del Agente.',
         'input_schema' => ['type' => 'object', 'properties' => ['campaign_id' => ['type' => 'integer']], 'required' => ['campaign_id']]],
    ];
}

// ── Ejecución de una herramienta ───────────────────────────────────────────────
function chat_execute(string $name, array $in) {
    $pdo = db();
    switch ($name) {
        case 'buscar_leads': {
            $where = ['1=1']; $params = [];
            if (!empty($in['segmento'])) { $where[] = 'segment = ?'; $params[] = strtoupper(substr((string)$in['segmento'], 0, 1)); }
            if (!empty($in['etapa']))    { $where[] = 'stage = ?';   $params[] = (string)$in['etapa']; }
            if (isset($in['min_score'])) { $where[] = 'score >= ?';  $params[] = (int)$in['min_score']; }
            if (!empty($in['texto'])) {
                $where[] = '(first_name LIKE ? OR last_name LIKE ? OR company LIKE ? OR role LIKE ?)';
                $like = '%' . $in['texto'] . '%'; array_push($params, $like, $like, $like, $like);
            }
            $limit = max(1, min(25, (int)($in['limite'] ?? 10)));
            $sql = "SELECT id, first_name, last_name, company, role, region, segment, score, stage
                    FROM leads WHERE " . implode(' AND ', $where) . " ORDER BY score DESC, id LIMIT " . $limit;
            $st = $pdo->prepare($sql); $st->execute($params);
            return ['count' => $st->rowCount(), 'leads' => $st->fetchAll()];
        }
        case 'resumen_pipeline': {
            return [
                'total'        => (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
                'alta_prioridad' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE score >= 80")->fetchColumn(),
                'por_etapa'    => $pdo->query("SELECT stage, COUNT(*) c FROM leads GROUP BY stage")->fetchAll(PDO::FETCH_KEY_PAIR),
                'por_segmento' => $pdo->query("SELECT COALESCE(NULLIF(segment,''),'?') s, COUNT(*) c FROM leads GROUP BY segment")->fetchAll(PDO::FETCH_KEY_PAIR),
                'bandeja_pendiente' => (int)$pdo->query("SELECT COUNT(*) FROM inbox_messages WHERE status='pendiente'")->fetchColumn(),
                'sugerencias'  => (int)$pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status='sugerida'")->fetchColumn(),
                'seguimientos_vencidos' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE next_action_date IS NOT NULL AND next_action_date <= CURDATE() AND stage NOT IN ('ganado','perdido','pausado')")->fetchColumn(),
            ];
        }
        case 'ver_lead': {
            $lead = outreach_get_lead((int)($in['lead_id'] ?? 0));
            if (!$lead) return ['error' => 'Lead no encontrado.'];
            unset($lead['ai_body'], $lead['ai_title']);
            $lead['historial'] = outreach_lead_history((int)$lead['id'], 8);
            return $lead;
        }
        case 'buscar_conocimiento': {
            $hits = knowledge_retrieve((string)($in['consulta'] ?? ''), 4);
            return $hits ? ['pasajes' => $hits] : ['mensaje' => 'No hay nada en la base de conocimiento sobre eso. Cargá documentos en la sección Conocimiento.'];
        }
        case 'guardar_contenido': {
            $tipo = (string)($in['tipo'] ?? 'post_linkedin');
            $plat = in_array($tipo, ['email_seguimiento', 'propuesta'], true) ? 'email' : 'linkedin';
            $pdo->prepare("INSERT INTO content_pieces (type, title, body, status, platform) VALUES (?, ?, ?, 'borrador', ?)")
                ->execute([$tipo, (string)($in['titulo'] ?? ''), (string)($in['cuerpo'] ?? ''), $plat]);
            return ['ok' => true, 'mensaje' => 'Contenido guardado como borrador en la sección Contenido.', 'id' => (int)$pdo->lastInsertId()];
        }
        case 'guardar_borrador_outreach': {
            $lead = outreach_get_lead((int)($in['lead_id'] ?? 0));
            if (!$lead) return ['error' => 'Lead no encontrado.'];
            $canal = outreach_norm_channel((string)($in['canal'] ?? 'email'));
            $tipo  = $canal === 'whatsapp' ? 'mensaje_prospecto' : 'email_seguimiento';
            $title = (string)($in['asunto'] ?? ('Borrador para ' . trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))));
            $pdo->prepare("INSERT INTO content_pieces (type, title, body, status, platform, lead_id) VALUES (?, ?, ?, 'borrador', ?, ?)")
                ->execute([$tipo, $title, (string)($in['cuerpo'] ?? ''), $canal, (int)$lead['id']]);
            return ['ok' => true, 'mensaje' => 'Borrador de ' . $canal . ' guardado para revisión, asociado al lead.', 'id' => (int)$pdo->lastInsertId()];
        }
        case 'inscribir_secuencia': {
            return agent_enroll_lead((int)($in['lead_id'] ?? 0), (int)($in['sequence_id'] ?? 1));
        }
        case 'estado_campanas': {
            $rep = campaign_report();
            return $rep ? ['campanas' => $rep] : ['mensaje' => 'No hay campañas creadas todavía.'];
        }
        case 'crear_campana': {
            $id = campaign_save([
                'name'        => (string)($in['nombre'] ?? ''),
                'sector'      => (string)($in['sector'] ?? ''),
                'segments'    => (string)($in['segmentos'] ?? ''),
                'region'      => (string)($in['region'] ?? ''),
                'objective'   => (string)($in['objetivo'] ?? ''),
                'daily_quota' => max(1, (int)($in['cupo_diario'] ?? 10)),
                'sequence_id' => 1,
                'channel'     => 'auto',
                'status'      => 'activa',
            ]);
            $c = campaign_get($id);
            return ['ok' => true, 'campaign_id' => $id, 'pool' => $c ? campaign_pool_count($c) : 0,
                    'mensaje' => 'Campaña creada y activa. Decime "corré la campaña ' . $id . '" para que prepare los primeros contactos, o esperá al barrido diario.'];
        }
        case 'correr_campana': {
            $c = campaign_get((int)($in['campaign_id'] ?? 0));
            if (!$c) return ['error' => 'Campaña no encontrada.'];
            $r = campaign_run($c);
            return ['ok' => true, 'preparados' => $r['prepared'], 'fallidos' => $r['failed'],
                    'mensaje' => $r['prepared'] . ' borradores de primer contacto listos para aprobar en la Bandeja del Agente.'];
        }
    }
    return ['error' => 'Herramienta desconocida: ' . $name];
}

function chat_system(): string {
    return agent_identity_block()
        . "\n\nEstás en un chat de trabajo con tu equipo (tu líder comercial). Pueden pedirte que consultes leads, el pipeline o los casos de SISTEL, que redactes contenido o mensajes, que inscribas leads en secuencias, o que gestiones campañas de prospección por sector (crearlas, correrlas, reportar cómo van). Usá las herramientas disponibles cuando te pidan hacer algo concreto.\n"
        . "REGLA: nunca envías nada a un cliente directamente. Cuando redactás un mensaje, lo GUARDÁS como borrador para que el equipo lo revise y envíe.\n"
        . "Después de usar herramientas, contá en lenguaje natural, breve y de colega, QUÉ hiciste y proponé el siguiente paso. Si te piden algo que no podés hacer aún, decilo con franqueza.";
}

switch ($action) {

    case 'history': {
        $rows = db()->query("SELECT role, content, created_at FROM chat_messages ORDER BY id DESC LIMIT 40")->fetchAll();
        json_out(['ok' => true, 'messages' => array_reverse($rows)]);
    }

    case 'clear': {
        db()->exec("DELETE FROM chat_messages");
        json_out(['ok' => true]);
    }

    case 'send': {
        $msg = trim((string)($d['message'] ?? ''));
        if ($msg === '') json_out(['ok' => false, 'error' => 'Mensaje vacío.'], 400);
        db()->prepare("INSERT INTO chat_messages (role, content) VALUES ('user', ?)")->execute([$msg]);

        if (!claude_available()) {
            $err = 'Configura tu API key de Claude en Ajustes para que pueda ayudarte.';
            db()->prepare("INSERT INTO chat_messages (role, content) VALUES ('assistant', ?)")->execute([$err]);
            json_out(['ok' => true, 'reply' => $err, 'steps' => []]);
        }

        // Reconstruir la conversación (últimos turnos).
        $hist = db()->query("SELECT role, content FROM chat_messages ORDER BY id DESC LIMIT 16")->fetchAll();
        $hist = array_reverse($hist);
        $messages = [];
        foreach ($hist as $h) {
            if (!$messages && $h['role'] !== 'user') continue; // la conversación debe empezar con user
            $messages[] = ['role' => $h['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string)$h['content']];
        }
        if (!$messages) $messages[] = ['role' => 'user', 'content' => $msg];

        $r = claude_call_tools(chat_system(), $messages, chat_tools(), 'chat_execute', 6, 2000);
        if (!$r['ok']) json_out(['ok' => false, 'error' => $r['error']], 502);

        $reply = $r['text'] !== '' ? $r['text'] : 'Listo.';
        $meta  = json_encode(['steps' => $r['steps']], JSON_UNESCAPED_UNICODE);
        db()->prepare("INSERT INTO chat_messages (role, content, meta) VALUES ('assistant', ?, ?)")->execute([$reply, $meta]);
        json_out(['ok' => true, 'reply' => $reply, 'steps' => $r['steps']]);
    }

    default:
        json_out(['ok' => false, 'error' => "Acción desconocida: {$action}"], 400);
}
