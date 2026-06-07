<?php
/**
 * Bucle de aprendizaje de Daniel.
 *  - CAPTURA: cada mensaje aprobado/editado se guarda (versión IA vs versión final).
 *  - RETROALIMENTACIÓN: learning_context() inyecta los mejores ejemplos en cada generación,
 *    para que Daniel imite el estilo real que el equipo aprueba.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function learning_stopwords(): array {
    return array_flip(['de','la','que','el','en','y','a','los','del','se','las','por','un','para','con','no','una','su','al','lo',
        'como','mas','más','pero','sus','le','ya','o','este','si','sí','porque','esta','entre','cuando','muy','sin','sobre','tambien','también',
        'me','hasta','hay','donde','quien','desde','todo','nos','durante','todos','uno','les','ni','contra','otros','ese','eso','ante','ellos',
        'esto','mi','mí','antes','algunos','que','unos','yo','otro','otras','otra','el','tanto','esa','estos','mucho','nada','muchos','cual',
        'poco','ella','estar','estas','algunas','algo','nosotros','the','to','and','of','in','for','is','on','that','with','as','we','our','your','you','it']);
}

/** Resumen de diferencias entre la versión IA y la versión final. */
function compute_diff_summary(string $ai, string $final): array {
    $la = mb_strlen($ai); $lf = mb_strlen($final);
    $sw = learning_stopwords();
    $clean = function ($txt) use ($sw) {
        $words = preg_split('/\s+/', mb_strtolower(trim($txt))) ?: [];
        $out = [];
        foreach ($words as $w) {
            $w = preg_replace('/[^\p{L}\p{N}]/u', '', $w);
            if ($w !== '' && mb_strlen($w) > 2 && !isset($sw[$w])) $out[] = $w;
        }
        return $out;
    };
    $aw = $clean($ai); $fw = $clean($final);
    $ac = array_count_values($aw); $fc = array_count_values($fw);
    $removed = []; foreach ($ac as $w => $n) { $d = $n - ($fc[$w] ?? 0); if ($d > 0) $removed[$w] = $d; }
    $added   = []; foreach ($fc as $w => $n) { $d = $n - ($ac[$w] ?? 0); if ($d > 0) $added[$w] = $d; }
    arsort($removed); arsort($added);
    $changed = (count($aw) + count($fw)) > 0
        ? min(100, (int)round((array_sum($added) + array_sum($removed)) / (count($aw) + count($fw)) * 100)) : 0;
    return [
        'length_delta'   => $lf - $la,
        'percent_changed'=> $changed,
        'words_removed'  => array_slice(array_keys($removed), 0, 8),
        'words_added'    => array_slice(array_keys($added), 0, 8),
    ];
}

/** Registra un ejemplo de aprendizaje (versión IA vs versión final aprobada). */
function learning_record(string $source, ?int $leadId, string $channel, string $context, string $ai, string $final): void {
    $ai = trim($ai); $final = trim($final);
    if ($final === '') return;
    $norm = function ($s) { return preg_replace('/\s+/', '', mb_strtolower($s)); };
    $kept = ($norm($ai) !== '' && $norm($ai) === $norm($final)) ? 1 : 0;
    $diff = compute_diff_summary($ai, $final);
    try {
        db()->prepare("INSERT INTO learning_examples (source, lead_id, channel, context, ai_version, final_version, diff_summary, kept_as_is)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$source, $leadId ?: null, $channel, mb_substr($context, 0, 2000), $ai, $final,
                       json_encode($diff, JSON_UNESCAPED_UNICODE), $kept]);
    } catch (Throwable $e) { /* tabla puede no existir aún; no romper el envío */ }
}

/** Few-shot: ejemplos reales aprobados, para inyectar en el system prompt. */
function learning_context(int $limit = 3): string {
    try {
        $rows = db()->query("SELECT context, final_version FROM learning_examples
                             WHERE final_version <> '' ORDER BY id DESC LIMIT " . (int)max(1, $limit))->fetchAll();
    } catch (Throwable $e) { return ''; }
    if (!$rows) return '';
    $b = "EJEMPLOS DE MENSAJES REALES YA APROBADOS POR EL EQUIPO (son la referencia de cómo escribe bien Daniel: imitá su estilo, tono y longitud):\n";
    foreach ($rows as $r) {
        $ctx = trim((string)$r['context']);
        $b .= "\n— " . ($ctx !== '' ? ('Situación: ' . truncate($ctx, 140) . "\n  ") : '')
            . "Mensaje aprobado: " . trim((string)$r['final_version']) . "\n";
    }
    return $b;
}

/** Métricas del aprendizaje (para la página). */
function learning_stats(): array {
    try {
        $total = (int)db()->query("SELECT COUNT(*) FROM learning_examples")->fetchColumn();
        $kept  = (int)db()->query("SELECT COUNT(*) FROM learning_examples WHERE kept_as_is = 1")->fetchColumn();
        $rows  = db()->query("SELECT diff_summary FROM learning_examples ORDER BY id DESC LIMIT 100")->fetchAll();
    } catch (Throwable $e) { return ['total' => 0, 'kept_as_is' => 0, 'kept_pct' => 0, 'top_added' => [], 'top_removed' => []]; }
    $added = []; $removed = [];
    foreach ($rows as $r) {
        $d = json_decode((string)$r['diff_summary'], true);
        if (is_array($d)) {
            foreach (($d['words_added'] ?? [])   as $w) $added[$w]   = ($added[$w] ?? 0) + 1;
            foreach (($d['words_removed'] ?? []) as $w) $removed[$w] = ($removed[$w] ?? 0) + 1;
        }
    }
    arsort($added); arsort($removed);
    return [
        'total'       => $total,
        'kept_as_is'  => $kept,
        'kept_pct'    => $total ? (int)round($kept / $total * 100) : 0,
        'top_added'   => array_slice(array_keys($added), 0, 10),
        'top_removed' => array_slice(array_keys($removed), 0, 10),
    ];
}

/** Últimos ejemplos para listar en la página. */
function learning_list(int $limit = 30): array {
    try {
        return db()->query("SELECT le.*, CONCAT(l.first_name, ' ', COALESCE(l.last_name,'')) AS lead_name
                            FROM learning_examples le LEFT JOIN leads l ON l.id = le.lead_id
                            ORDER BY le.id DESC LIMIT " . (int)max(1, $limit))->fetchAll();
    } catch (Throwable $e) { return []; }
}
