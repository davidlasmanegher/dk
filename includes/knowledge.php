<?php
/**
 * Base de conocimiento de SISTEL (RAG): casos de éxito, propuestas, one-pager, guía ejecutiva.
 * Trocea documentos en pasajes y los recupera por relevancia (semántica con embeddings si hay
 * OpenAI key, si no FULLTEXT con respaldo LIKE). Un solo cerebro (sin marcas).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/embeddings.php';

/** Divide un texto en pasajes de ~900 caracteres respetando párrafos. */
function knowledge_chunk_text(string $text, int $target = 900, int $hardMax = 1500): array {
    $text = trim(preg_replace("/\r\n?/", "\n", $text));
    if ($text === '') return [];
    $paras = preg_split('/\n\s*\n/', $text);
    $chunks = []; $buf = '';
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (mb_strlen($p) > $hardMax) {
            if ($buf !== '') { $chunks[] = $buf; $buf = ''; }
            $sentences = preg_split('/(?<=[.!?])\s+/', $p);
            $s = '';
            foreach ($sentences as $sent) {
                if (mb_strlen($s . ' ' . $sent) > $target && $s !== '') { $chunks[] = trim($s); $s = ''; }
                $s = trim($s . ' ' . $sent);
            }
            if ($s !== '') $chunks[] = trim($s);
            continue;
        }
        if (mb_strlen($buf . "\n\n" . $p) > $target && $buf !== '') { $chunks[] = $buf; $buf = $p; }
        else { $buf = $buf === '' ? $p : ($buf . "\n\n" . $p); }
    }
    if ($buf !== '') $chunks[] = $buf;
    return $chunks;
}

/** Agrega un documento, lo trocea y (si hay embeddings) lo vectoriza. Devuelve el id. */
function knowledge_add(string $type, string $title, string $content, string $source = ''): int {
    $pdo = db();
    $chunks = knowledge_chunk_text($content);
    $st = $pdo->prepare("INSERT INTO knowledge (type,title,content,source,chunks) VALUES (?,?,?,?,?)");
    $st->execute([$type, $title, $content, $source, count($chunks)]);
    $docId = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare("INSERT INTO knowledge_chunks (doc_id,title,content) VALUES (?,?,?)");
    foreach ($chunks as $ck) { $ins->execute([$docId, $title, $ck]); }

    if (embeddings_available() && $chunks) {
        try {
            $emb = openai_embed($chunks);
            if ($emb['ok'] && count($emb['vectors']) === count($chunks)) {
                $upd = $pdo->prepare("UPDATE knowledge_chunks SET embedding = ? WHERE doc_id = ? AND content = ? LIMIT 1");
                foreach ($chunks as $i => $ck) { $upd->execute([json_encode($emb['vectors'][$i]), $docId, $ck]); }
            }
        } catch (Throwable $e) { /* queda buscable por FULLTEXT */ }
    }
    return $docId;
}

/** Borra un documento y sus chunks. */
function knowledge_delete(int $docId): void {
    db()->prepare("DELETE FROM knowledge_chunks WHERE doc_id=?")->execute([$docId]);
    db()->prepare("DELETE FROM knowledge WHERE id=?")->execute([$docId]);
}

/** Lista de documentos (sin el contenido completo). */
function knowledge_list(): array {
    return db()->query("SELECT id, type, title, source, chunks, created_at FROM knowledge ORDER BY created_at DESC")->fetchAll();
}

/**
 * Recupera los pasajes más relevantes: semántica (embeddings) → FULLTEXT → LIKE.
 * @return array<int,array{title:string,content:string}>
 */
function knowledge_retrieve(string $query, int $limit = 4): array {
    $pdo = db();
    $query = trim(preg_replace('/\s+/', ' ', $query));
    if ($query === '') return [];
    $limit = max(1, min(8, $limit));

    if (embeddings_available()) {
        try {
            $hasAny = (int)$pdo->query("SELECT COUNT(*) FROM knowledge_chunks WHERE embedding IS NOT NULL AND embedding <> '' LIMIT 1")->fetchColumn();
            if ($hasAny > 0) {
                $vec = knowledge_search_vector($query, $limit);
                if ($vec) return array_map(fn($r) => ['title' => $r['title'], 'content' => $r['content']], $vec);
            }
        } catch (Throwable $e) { /* sigue */ }
    }

    try {
        $sql = "SELECT title, content, MATCH(content) AGAINST (:q IN NATURAL LANGUAGE MODE) AS score
                FROM knowledge_chunks
                WHERE MATCH(content) AGAINST (:q2 IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC LIMIT $limit";
        $st = $pdo->prepare($sql);
        $st->execute(['q' => $query, 'q2' => $query]);
        $rows = $st->fetchAll();
        if ($rows) return array_map(fn($r) => ['title' => $r['title'], 'content' => $r['content']], $rows);
    } catch (Throwable $e) { /* respaldo */ }

    $words = array_values(array_filter(preg_split('/\s+/', mb_strtolower($query)), fn($w) => mb_strlen($w) >= 4));
    $words = array_slice(array_unique($words), 0, 6);
    if (!$words) return [];
    $likes = implode(' OR ', array_fill(0, count($words), 'LOWER(content) LIKE ?'));
    $params = [];
    foreach ($words as $w) { $params[] = '%' . $w . '%'; }
    try {
        $st = db()->prepare("SELECT title, content FROM knowledge_chunks WHERE ($likes) LIMIT $limit");
        $st->execute($params);
        return array_map(fn($r) => ['title' => $r['title'], 'content' => $r['content']], $st->fetchAll());
    } catch (Throwable $e) { return []; }
}

/** Bloque de contexto formateado para inyectar en un prompt. Vacío si no hay hits. */
function knowledge_context(string $query, int $limit = 4, int $perChunk = 700): string {
    $hits = knowledge_retrieve($query, $limit);
    if (!$hits) return '';
    $out = "--- CONOCIMIENTO Y CASOS REALES DE SISTEL (referencia) ---\n"
         . "Usá estos pasajes del material real de SISTEL para dar prueba social, datos y casos concretos. No los copies literal; integralos con criterio y solo si son pertinentes.\n\n";
    foreach ($hits as $i => $h) {
        $txt = trim($h['content']);
        if (mb_strlen($txt) > $perChunk) $txt = mb_substr($txt, 0, $perChunk) . '…';
        $out .= "[" . ($i + 1) . "] " . trim($h['title']) . "\n" . $txt . "\n\n";
    }
    return $out;
}
