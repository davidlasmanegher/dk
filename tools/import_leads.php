<?php
/**
 * Importador de leads desde un JSON (CLI, fuera del flujo web).
 * Caracteriza cada lead (segmento + score por reglas) y deduplica por email.
 * Uso: php tools/import_leads.php <archivo.json> [--commit]
 *   sin --commit hace un dry-run (no inserta, solo reporta).
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Solo CLI\n"); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/leads_intel.php';

$jsonPath = $argv[1] ?? '';
$commit   = in_array('--commit', $argv, true);
if ($jsonPath === '' || !is_file($jsonPath)) { fwrite(STDERR, "Uso: php tools/import_leads.php <archivo.json> [--commit]\n"); exit(1); }

$rows = json_decode(file_get_contents($jsonPath), true);
if (!is_array($rows)) { fwrite(STDERR, "JSON inválido o vacío.\n"); exit(1); }

// Frecuencia por empresa (para detectar empresa grande por volumen).
$freq = [];
foreach ($rows as $r) { $e = mb_strtoupper(trim($r['empresa'] ?? ''), 'UTF-8'); if ($e !== '') $freq[$e] = ($freq[$e] ?? 0) + 1; }

$pdo    = db();
$exists = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE email = ? AND email <> ''");
$ins    = $pdo->prepare(
    "INSERT INTO leads (first_name,last_name,company,role,country,city,region,email,phone,stage,source,score,segment,privacy_consent,assigned_to)
     VALUES (:fn,:ln,:co,:ro,:pa,:ci,:re,:em,:ph,'prospecto','import_congreso',:sc,:sg,:pc,'Daniel Khan')"
);

$n = 0; $skip = 0; $bySeg = []; $scoreSum = 0;
foreach ($rows as $r) {
    $email = trim($r['email'] ?? '');
    if ($email !== '') { $exists->execute([$email]); if ((int)$exists->fetchColumn() > 0) { $skip++; continue; } }

    $empresa = trim($r['empresa'] ?? '');
    $f = $freq[mb_strtoupper($empresa, 'UTF-8')] ?? 0;
    $L = ['company'=>$empresa, 'role'=>$r['puesto']??'', 'country'=>$r['pais']??'',
          'email'=>$email, 'phone'=>$r['telefono']??'', 'privacy'=>$r['aviso']??''];
    $seg = lead_segment($L, $f);
    $sc  = lead_score_calc($L, $f);
    $ln  = trim(($r['ap_paterno'] ?? '') . ' ' . ($r['ap_materno'] ?? ''));
    $ciudad = trim($r['ciudad'] ?? '') ?: trim($r['del_mun'] ?? '');

    if ($commit) {
        $ins->execute([
            ':fn'=>$r['nombre']??'', ':ln'=>$ln, ':co'=>$empresa, ':ro'=>$r['puesto']??'',
            ':pa'=>(trim($r['pais']??'') ?: 'México'), ':ci'=>$ciudad, ':re'=>$r['estado']??'',
            ':em'=>$email, ':ph'=>$r['telefono']??'', ':sc'=>$sc, ':sg'=>$seg,
            ':pc'=>(mb_strtoupper(trim($r['aviso']??''),'UTF-8')==='SI'?1:0),
        ]);
    }
    $n++; $bySeg[$seg] = ($bySeg[$seg] ?? 0) + 1; $scoreSum += $sc;
}

echo ($commit ? "IMPORTADOS" : "DRY-RUN (no se insertó nada)") . ": $n | omitidos por email duplicado: $skip\n";
echo "Score promedio: " . ($n ? round($scoreSum / $n, 1) : 0) . "\n";
ksort($bySeg);
foreach ($bySeg as $s => $c) echo "  Segmento " . lead_segment_label($s) . ": $c\n";
