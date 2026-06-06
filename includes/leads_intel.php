<?php
/**
 * Inteligencia de leads: caracterización (segmento A-E) y priorización (score 0-100)
 * por reglas. Reutilizable por el importador y por altas/edición de leads.
 *
 * Segmentos (modelo del análisis de mercado México):
 *   A = alta prioridad estratégica (empresa reconocida + cargo relevante)
 *   B = RH / Talento Humano
 *   C = Capacitación / Desarrollo
 *   D = Alta dirección
 *   E = frío / genérico / fuera de México
 */
require_once __DIR__ . '/db.php';

function _li_norm($s): string { return mb_strtoupper(trim((string)$s), 'UTF-8'); }

/** Marcas grandes/reconocidas en México (curado del análisis + top de la base). */
function lead_top_companies(): array {
    return ['PEPSICO','GEPP','BIMBO','GNP','BAYER','TELEFONICA','ALSEA','ADIDAS','WALMART','FEMSA',
        'COCA-COLA','COCA COLA','MOBILITY ADO','AFORE SURA',' SURA','NESTLE','UNILEVER','DANONE',
        'KIMBERLY','PROCTER','HEINEKEN','GRUPO MODELO','CEMEX','GRUMA','LALA','SIGMA','MABE',
        'LIVERPOOL','SORIANA','CHEDRAUI','OXXO','TELMEX','JOHNSON','HSBC','BBVA','BANORTE',
        'SANTANDER','CITIBANAMEX','IBM','MICROSOFT','ORACLE','ACCENTURE','DELOITTE','KPMG'];
}

/** ¿Empresa reconocida? Por lista curada o por volumen en la base ($freq >= 5). */
function lead_is_top_company(string $empresa, int $freq = 0): bool {
    $e = _li_norm($empresa);
    if ($e === '') return false;
    if ($freq >= 5) return true;
    foreach (lead_top_companies() as $t) { if (strpos($e, $t) !== false) return true; }
    return false;
}

/** Clasifica el cargo: direccion | rh | capacitacion | gerencia | otro */
function lead_role_class(string $puesto): string {
    $p = _li_norm($puesto);
    if ($p === '') return 'otro';
    if (preg_match('/(CEO|PRESIDENT|DIRECTOR GENERAL|DIRECTORA GENERAL|FOUNDER|FUNDADOR|SOCIO|DUE.O|CHIEF|VICEPRESID|\bVP\b|GENERAL MANAGER|COUNTRY MANAGER)/u', $p)) return 'direccion';
    if (preg_match('/(CAPACITAC|TRAINING|LEARNING|DESARROLLO ORGAN|FORMACION|ADIESTRAM|\bL&D\b|TALENT DEVELOPMENT)/u', $p)) return 'capacitacion';
    if (preg_match('/(RECURSOS HUMANOS|\bRH\b|\bRRHH\b|TALENT|CAPITAL HUMANO|\bPEOPLE\b|HUMAN RESOURCES|CHRO|\bDHO\b|GESTION HUMANA)/u', $p)) return 'rh';
    if (preg_match('/(DIRECTOR|DIRECTORA|GERENTE|HEAD|MANAGER|JEFE|JEFA|\bLEAD\b|COORDINAD|SUBDIRECT)/u', $p)) return 'gerencia';
    return 'otro';
}

/** Clasifica el email: corporativo | personal | generico | none */
function lead_email_class(string $email): string {
    $e = strtolower(trim($email));
    if ($e === '' || strpos($e, '@') === false) return 'none';
    $parts = explode('@', $e, 2);
    $loc = $parts[0]; $dom = $parts[1] ?? '';
    foreach (['gmail','hotmail','yahoo','outlook','live.','icloud','prodigy','aol.'] as $d) if (strpos($dom, $d) !== false) return 'personal';
    foreach (['info','contacto','ventas','admin','recepcion','hola','contact','marketing','rrhh'] as $g) if (strpos($loc, $g) === 0) return 'generico';
    return 'corporativo';
}

/** ¿El lead es de México? (país vacío se asume MX en esta base). */
function lead_is_mexico(array $L): bool {
    $p = _li_norm($L['country'] ?? $L['pais'] ?? '');
    return $p === 'MEXICO' || $p === 'MÉXICO' || $p === '';
}

/** Segmento A-E. $freq = nº de contactos de la misma empresa en la base. */
function lead_segment(array $L, int $freq = 0): string {
    if (!lead_is_mexico($L)) return 'E';
    $rc  = lead_role_class($L['role'] ?? $L['puesto'] ?? '');
    $top = lead_is_top_company($L['company'] ?? $L['empresa'] ?? '', $freq);
    if ($top && in_array($rc, ['direccion','rh','capacitacion'], true)) return 'A';
    if ($rc === 'direccion')   return 'D';
    if ($rc === 'rh')          return 'B';
    if ($rc === 'capacitacion') return 'C';
    return 'E';
}

/** Score de prioridad 0-100. */
function lead_score_calc(array $L, int $freq = 0): int {
    $rc = lead_role_class($L['role'] ?? $L['puesto'] ?? '');
    $map = ['direccion'=>30,'rh'=>25,'capacitacion'=>25,'gerencia'=>15,'otro'=>5];
    $score = $map[$rc] ?? 5;
    if (lead_is_top_company($L['company'] ?? $L['empresa'] ?? '', $freq)) $score += 25;
    $ec = lead_email_class($L['email'] ?? '');
    $score += ($ec === 'corporativo' ? 15 : ($ec === 'personal' ? 5 : 0));
    if (trim((string)($L['phone'] ?? $L['telefono'] ?? '')) !== '') $score += 10;
    if (lead_is_mexico($L)) $score += 15;
    if (_li_norm($L['privacy'] ?? $L['aviso'] ?? '') === 'SI') $score += 5;
    return max(0, min(100, $score));
}

/** Etiqueta legible del segmento. */
function lead_segment_label(string $seg): string {
    return [
        'A' => 'A · Estratégico',
        'B' => 'B · RH/Talento',
        'C' => 'C · Capacitación',
        'D' => 'D · Alta dirección',
        'E' => 'E · Nutrición',
    ][$seg] ?? $seg;
}

/** Fotografía agregada de la base de leads (para el reporte ejecutivo IA). */
function leads_base_stats(): array {
    $pdo = db();
    return [
        'total'        => (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
        'high_priority'=> (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE score >= 80")->fetchColumn(),
        'contacted'    => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE stage <> 'prospecto'")->fetchColumn(),
        'by_segment'   => $pdo->query("SELECT segment, COUNT(*) c, ROUND(AVG(score)) avg_score FROM leads GROUP BY segment ORDER BY segment")->fetchAll(),
        'by_region'    => $pdo->query("SELECT COALESCE(NULLIF(region,''),'(s/d)') region, COUNT(*) c FROM leads GROUP BY region ORDER BY c DESC LIMIT 10")->fetchAll(),
        'by_stage'     => $pdo->query("SELECT stage, COUNT(*) c FROM leads GROUP BY stage ORDER BY c DESC")->fetchAll(),
        'top_companies'=> $pdo->query("SELECT company, COUNT(*) c FROM leads WHERE company <> '' GROUP BY company ORDER BY c DESC LIMIT 12")->fetchAll(),
    ];
}

/** Formatea las stats como texto plano para el prompt del modelo. */
function leads_stats_text(array $s): string {
    $t  = "Total de prospectos: {$s['total']}\n";
    $t .= "Alta prioridad (score >= 80): {$s['high_priority']}\n";
    $t .= "Ya contactados (etapa distinta de prospecto): {$s['contacted']}\n\n";
    $t .= "Por segmento (cantidad | score promedio):\n";
    foreach ($s['by_segment'] as $r) $t .= "  " . lead_segment_label((string)$r['segment']) . ": {$r['c']} (score {$r['avg_score']})\n";
    $t .= "\nTop regiones:\n";
    foreach ($s['by_region'] as $r) $t .= "  {$r['region']}: {$r['c']}\n";
    $t .= "\nEtapas del pipeline:\n";
    foreach ($s['by_stage'] as $r) $t .= "  {$r['stage']}: {$r['c']}\n";
    $t .= "\nTop empresas por nº de contactos:\n";
    foreach ($s['top_companies'] as $r) $t .= "  {$r['company']}: {$r['c']}\n";
    return $t;
}
