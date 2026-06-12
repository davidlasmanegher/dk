<?php
/**
 * Instalador idempotente del Agente DK.
 * Crea la base de datos dk_daniel, las tablas y siembra configuración inicial.
 * Es seguro re-ejecutarlo: usa INSERT IGNORE y CREATE TABLE IF NOT EXISTS.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$log = [];
$ok  = true;

try {
    $c = cfg();

    // 1. Crear la base de datos si no existe.
    db_server()->exec("CREATE DATABASE IF NOT EXISTS `{$c['db_name']}`
                       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $log[] = "Base de datos «{$c['db_name']}» lista.";

    $pdo = db();

    // 2. Tablas.

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        skey   VARCHAR(64) PRIMARY KEY,
        svalue TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        username   VARCHAR(60)  NOT NULL UNIQUE,
        pass_hash  VARCHAR(255) NOT NULL,
        name       VARCHAR(120) DEFAULT '',
        last_login DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_profile (
        id                  INT AUTO_INCREMENT PRIMARY KEY,
        name                VARCHAR(100) DEFAULT 'Daniel Khan',
        role                VARCHAR(150) DEFAULT 'Consultor Sr. en Aprendizaje Corporativo',
        company             VARCHAR(100) DEFAULT 'SISTEL',
        target_market       TEXT,
        value_proposition   TEXT,
        communication_style TEXT,
        objections_playbook TEXT,
        market_focus        VARCHAR(100) DEFAULT 'México',
        linkedin_url        VARCHAR(255),
        signature           TEXT,
        created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        first_name       VARCHAR(120) NOT NULL DEFAULT '',
        last_name        VARCHAR(120) NOT NULL DEFAULT '',
        company          VARCHAR(180),
        role             VARCHAR(180),
        industry         VARCHAR(120),
        company_size     VARCHAR(30),
        country          VARCHAR(80) DEFAULT 'México',
        city             VARCHAR(120),
        email            VARCHAR(180),
        phone            VARCHAR(40),
        whatsapp_phone   VARCHAR(40),
        linkedin_url     VARCHAR(255),
        stage            VARCHAR(30) DEFAULT 'prospecto',
        source           VARCHAR(50),
        score            INT DEFAULT 0,
        notes            TEXT,
        next_action      TEXT,
        next_action_date DATE NULL,
        assigned_to      VARCHAR(100) DEFAULT 'Daniel Khan',
        created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_stage (stage),
        INDEX idx_country (country),
        INDEX idx_industry (industry),
        INDEX idx_next_action_date (next_action_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_activities (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        lead_id   INT NOT NULL,
        type      VARCHAR(30),
        subject   VARCHAR(255),
        body      TEXT,
        direction VARCHAR(10) DEFAULT 'out',
        status    VARCHAR(20) DEFAULT 'sent',
        sent_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lead_id (lead_id),
        INDEX idx_type (type),
        INDEX idx_sent_at (sent_at),
        CONSTRAINT fk_act_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS content_pieces (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        type         VARCHAR(30) DEFAULT 'post_linkedin',
        title        VARCHAR(255),
        body         LONGTEXT,
        hook         TEXT,
        cta          TEXT,
        lead_id      INT NULL,
        status       VARCHAR(20) DEFAULT 'borrador',
        platform     VARCHAR(30) DEFAULT 'linkedin',
        published_at DATETIME NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_status (status),
        CONSTRAINT fk_cp_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_tasks (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        type         VARCHAR(50),
        lead_id      INT NULL,
        payload      JSON,
        status       VARCHAR(20) DEFAULT 'pendiente',
        priority     INT DEFAULT 5,
        scheduled_at DATETIME NULL,
        executed_at  DATETIME NULL,
        result       TEXT NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_scheduled_at (scheduled_at),
        INDEX idx_lead_id (lead_id),
        CONSTRAINT fk_task_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sequences (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        name            VARCHAR(150),
        description     TEXT,
        target_stage    VARCHAR(30),
        target_industry VARCHAR(100),
        steps_json      LONGTEXT,
        active          TINYINT(1) DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inbox_messages (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        lead_id       INT NULL,
        channel       VARCHAR(20) NOT NULL,
        external_id   VARCHAR(255) NULL,
        from_addr     VARCHAR(255) NULL,
        subject       VARCHAR(255) NULL,
        body          LONGTEXT,
        reply_draft   LONGTEXT,
        has_objection TINYINT(1) DEFAULT 0,
        status        VARCHAR(20) DEFAULT 'pendiente',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        replied_at    DATETIME NULL,
        UNIQUE KEY uq_ext (channel, external_id),
        INDEX idx_status (status),
        INDEX idx_lead (lead_id),
        CONSTRAINT fk_inbox_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS knowledge (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        type       VARCHAR(32) DEFAULT 'caso',
        title      VARCHAR(255),
        content    LONGTEXT,
        source     VARCHAR(255),
        chunks     INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        role       VARCHAR(16) NOT NULL,
        content    LONGTEXT,
        meta       JSON NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS knowledge_chunks (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        doc_id    INT,
        title     VARCHAR(255),
        content   TEXT,
        embedding LONGTEXT NULL,
        INDEX (doc_id),
        FULLTEXT KEY ft_content (content)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS campaigns (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(160) NOT NULL,
        sector      VARCHAR(120) DEFAULT '',
        segments    VARCHAR(40)  DEFAULT '',
        region      VARCHAR(120) DEFAULT '',
        objective   TEXT,
        daily_quota INT DEFAULT 10,
        channel     VARCHAR(20) DEFAULT 'auto',
        sequence_id INT NULL,
        status      VARCHAR(20) DEFAULT 'activa',
        notes       TEXT,
        last_run_at DATETIME NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS campaign_leads (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        lead_id     INT NOT NULL,
        status      VARCHAR(20) DEFAULT 'seleccionado',
        task_id     INT NULL,
        enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_campaign_lead (campaign_id, lead_id),
        INDEX idx_campaign (campaign_id),
        INDEX idx_lead (lead_id),
        CONSTRAINT fk_cl_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
        CONSTRAINT fk_cl_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS learning_examples (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        source        VARCHAR(20) DEFAULT 'inbox',
        lead_id       INT NULL,
        channel       VARCHAR(20) DEFAULT 'email',
        context       TEXT,
        ai_version    LONGTEXT,
        final_version LONGTEXT,
        diff_summary  JSON,
        kept_as_is    TINYINT(1) DEFAULT 0,
        rating        TINYINT DEFAULT 0,
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_source (source),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $log[] = "Tablas creadas/verificadas.";

    // 3. Migraciones idempotentes (ALTER TABLE con try/catch).
    $migrations = [
        "ALTER TABLE leads ADD COLUMN score INT DEFAULT 0 AFTER stage",
        "ALTER TABLE content_pieces ADD COLUMN hashtags VARCHAR(500) NULL AFTER cta",
        "ALTER TABLE leads ADD COLUMN region VARCHAR(120) NULL AFTER city",
        "ALTER TABLE leads ADD COLUMN segment VARCHAR(2) NULL AFTER score",
        "ALTER TABLE leads ADD COLUMN privacy_consent TINYINT(1) DEFAULT 0 AFTER segment",
        "ALTER TABLE leads ADD INDEX idx_segment (segment)",
        "ALTER TABLE leads ADD INDEX idx_score (score)",
        "ALTER TABLE agent_profile ADD COLUMN social_proof TEXT NULL AFTER objections_playbook",
        "ALTER TABLE agent_profile ADD COLUMN persona LONGTEXT NULL AFTER communication_style",
        "ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL AFTER name",
        "ALTER TABLE users ADD COLUMN notify TINYINT(1) DEFAULT 1 AFTER phone",
        "ALTER TABLE chat_messages ADD COLUMN user_id INT NULL AFTER id",
        "ALTER TABLE chat_messages ADD INDEX idx_chat_user (user_id)",
    ];
    foreach ($migrations as $m) {
        try { $pdo->exec($m); } catch (Throwable $e) { /* ya existe — ignorar */ }
    }

    // 4. Settings por defecto (no pisa valores existentes).
    $defaults = [
        'claude_api_key'       => '',
        'claude_model'         => 'claude-sonnet-4-5',
        'openai_api_key'       => '',
        'whapi_token'          => '',
        'whapi_instance_url'   => 'https://gate.whapi.cloud',
        'whapi_owner_phone'    => '',
        'smtp_host'            => '',
        'smtp_port'            => '587',
        'smtp_user'            => '',
        'smtp_pass'            => '',
        'smtp_from_name'       => 'Daniel Khan · SISTEL',
        'smtp_from_email'      => '',
        'linkedin_token'       => '',
        'linkedin_author_urn'  => '',
        'agent_auto_mode'      => '0',
        'agent_daily_limit'    => '20',
        'imap_host'            => 'outlook.office365.com',
        'imap_port'            => '993',
        'imap_user'            => '',
        'imap_pass'            => '',
        'whapi_webhook_token'  => '',
        'inbox_autoreply'      => '0',
        'embeddings_enabled'   => '1',
        'embeddings_model'     => 'text-embedding-3-small',
        'campaign_max_per_company' => '2',
        'signature_name'       => 'Daniel Khan',
        'signature_role'       => 'Consultor Sr. en Aprendizaje Corporativo',
        'signature_email'      => 'daniel.khan@sistelco.com.mx',
        'signature_phone'      => '+52 55 9816 2472',
        'signature_web'        => 'www.sistelco.com.mx',
        'signature_linkedin'   => 'sistel-méxico',
        'signature_address'    => 'Bosque Real 8, Depto 604, Huixquilucan, Estado de México, C.P. 52770',
        'signature_company'    => 'SISTEL',
        'signature_bcorp'      => '1',
    ];
    $st = $pdo->prepare("INSERT IGNORE INTO settings (skey, svalue) VALUES (?, ?)");
    foreach ($defaults as $k => $v) { $st->execute([$k, $v]); }
    $log[] = count($defaults) . " settings sembrados.";

    // 5. Perfil del agente (fila única id=1).
    $pdo->exec("INSERT IGNORE INTO agent_profile (id, name, role, company, target_market, value_proposition, communication_style, objections_playbook, market_focus) VALUES (
        1,
        'Daniel Khan',
        'Consultor Sr. en Aprendizaje Corporativo',
        'SISTEL',
        'Directores y Gerentes de Recursos Humanos, Capacitación, Talento y Desarrollo Organizacional en empresas medianas y grandes del sector industrial, manufactura, retail, servicios financieros y tecnología en México. Organizaciones con más de 200 empleados que buscan profesionalizar su función de aprendizaje.',
        'SISTEL diseña, implementa y opera Universidades Corporativas y ecosistemas de aprendizaje a medida para empresas en LATAM. No somos un proveedor de cursos: somos el socio estratégico que convierte la capacitación en una ventaja competitiva real. Nuestra metodología 6E+IA (Explorar, Estructurar, Ejecutar, Enamorar, Enfocar, Evolucionar) y nuestra plataforma SENSEI permiten medir el impacto del aprendizaje en resultados de negocio.',
        'Consultivo y directo. Habla en términos de negocio, no de pedagogía. Usa datos y casos cuando puede. Tono cálido pero ejecutivo. Escucha primero, propone después. No vende características: conecta con el problema del cliente.',
        'Objeción: Ya tenemos un LMS. Respuesta: Un LMS es solo la plataforma; una Universidad Corporativa es el sistema estratégico que la gobierna. ¿Qué resultados de negocio están midiendo hoy con ese LMS? || Objeción: Es muy caro. Respuesta: El costo real es no medir el impacto. ¿Cuánto les cuesta hoy la rotación, los errores operativos o la brecha de habilidades? || Objeción: No es el momento. Respuesta: Entendido. ¿Qué tendría que pasar en su organización para que sea el momento?',
        'México'
    )");
    $log[] = "Perfil del agente sembrado (id=1).";

    // Prueba social: clientes aprobados (no pisa si el usuario ya cargó su propia lista).
    $pdo->prepare("UPDATE agent_profile SET social_proof = ? WHERE id = 1 AND (social_proof IS NULL OR social_proof = '')")
        ->execute(['Sanofi, Carvajal, Juan Valdez, Unilever']);

    // Secuencia maestra: cadencia México 5x21 (del análisis de mercado).
    $cadencia = json_encode([
        ['day' => 0,  'channel' => 'email',    'goal' => 'Presentación institucional con prueba social; abrir un espacio de conversación.'],
        ['day' => 4,  'channel' => 'linkedin', 'goal' => 'Conexión en LinkedIn con mensaje breve y profesional.'],
        ['day' => 7,  'channel' => 'email',    'goal' => 'Compartir una guía ejecutiva o un insight de valor, sin pedir nada a cambio.'],
        ['day' => 12, 'channel' => 'whatsapp', 'goal' => 'Mensaje breve de seguimiento conectando con un dolor de negocio concreto.'],
        ['day' => 18, 'channel' => 'email',    'goal' => 'Invitación a una sesión ejecutiva de diagnóstico de 30 minutos.'],
        ['day' => 21, 'channel' => 'email',    'goal' => 'Cierre elegante dejando la puerta abierta para retomar más adelante.'],
    ], JSON_UNESCAPED_UNICODE);
    $stSeq = $pdo->prepare("INSERT IGNORE INTO sequences (id, name, description, target_stage, steps_json, active)
                            VALUES (1, 'Cadencia México 5x21', 'Secuencia de 6 toques en 21 días (correo, LinkedIn, WhatsApp) para prospección consultiva en México.', 'prospecto', ?, 1)");
    $stSeq->execute([$cadencia]);
    $log[] = "Secuencia maestra (cadencia 5x21) sembrada.";

    // 6. Carpeta uploads.
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
    $gitIgnore = $uploadsDir . '/.gitignore';
    if (!file_exists($gitIgnore)) {
        @file_put_contents($gitIgnore, "*\n!.gitignore\n");
    }

    $log[] = "Instalación completa.";

} catch (Throwable $e) {
    $ok    = false;
    $log[] = "ERROR: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación · Agente DK</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:system-ui,sans-serif}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
  <div class="max-w-xl w-full bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold text-lg">DK</div>
      <div>
        <h1 class="text-lg font-semibold text-slate-900">Instalación del Agente DK</h1>
        <p class="text-sm text-slate-500">Daniel Khan &mdash; SISTEL LATAM</p>
      </div>
    </div>
    <ul class="space-y-2 mb-6">
      <?php foreach ($log as $line):
        $isErr = (strncmp($line, 'ERROR', 5) === 0); ?>
        <li class="text-sm <?= $isErr ? 'text-red-600' : 'text-slate-700' ?> flex gap-2 items-start">
          <span class="shrink-0 mt-px"><?= $isErr ? '&#x2715;' : '&#x2713;' ?></span>
          <span><?= e($line) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if ($ok): ?>
      <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 p-4 mb-6">
        <p class="text-sm text-emerald-800 font-medium">Todo listo. Abre la plataforma y configura tu API key de Claude en Ajustes.</p>
      </div>
      <div class="flex gap-3">
        <a href="index.php" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Abrir plataforma</a>
        <a href="index.php?page=ajustes" class="px-4 py-2.5 rounded-lg ring-1 ring-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Ir a Ajustes</a>
      </div>
    <?php else: ?>
      <div class="rounded-xl bg-red-50 ring-1 ring-red-200 p-4">
        <p class="text-sm text-red-800">Revisa tus datos en <code>config.php</code> (host, puerto, usuario y contraseña de MySQL en MAMP) y vuelve a cargar esta página.</p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
