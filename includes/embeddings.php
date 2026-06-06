<?php
/**
 * Embeddings vectoriales para búsqueda semántica en la base de conocimiento.
 * OpenAI text-embedding-3-small (1536 dims). Vectores en knowledge_chunks.embedding (JSON);
 * la similitud se computa en PHP (cosine). Fallback transparente a FULLTEXT/LIKE si no hay key.
 */
require_once __DIR__ . '/db.php';

const OPENAI_EMBEDDINGS_URL = 'https://api.openai.com/v1/embeddings';

function embeddings_model(): string {
    $m = trim((string)setting('embeddings_model', ''));
    return $m !== '' ? $m : 'text-embedding-3-small';
}

/** ¿Embeddings habilitados? (setting on + openai key). */
function embeddings_available(): bool {
    if ((string)setting('embeddings_enabled', '1') !== '1') return false;
    return strlen(trim((string)setting('openai_api_key', ''))) > 10;
}

/**
 * Genera embeddings vía OpenAI. Acepta string o array de strings.
 * @return array{ok:bool, vectors:array, error:string, tokens:int}
 */
function openai_embed($input, array $opts = []): array {
    $key = trim((string)setting('openai_api_key', ''));
    if (strlen($key) < 10) return ['ok' => false, 'vectors' => [], 'error' => 'Falta OpenAI API key.', 'tokens' => 0];

    $batch = is_array($input) ? $input : [$input];
    if (!$batch) return ['ok' => true, 'vectors' => [], 'error' => '', 'tokens' => 0];

    $payload = ['model' => $opts['model'] ?? embeddings_model(), 'input' => $batch];
    $ch = curl_init(OPENAI_EMBEDDINGS_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 120,
    ]);
    $res   = curl_exec($ch);
    $errno = curl_errno($ch);
    $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) return ['ok' => false, 'vectors' => [], 'error' => 'Error de red al contactar OpenAI.', 'tokens' => 0];
    $data = json_decode($res, true);
    if ($http !== 200) {
        $msg = $data['error']['message'] ?? ('OpenAI respondió con código ' . $http);
        return ['ok' => false, 'vectors' => [], 'error' => $msg, 'tokens' => 0];
    }
    $vectors = [];
    foreach (($data['data'] ?? []) as $item) {
        if (isset($item['embedding']) && is_array($item['embedding'])) $vectors[] = $item['embedding'];
    }
    return ['ok' => true, 'vectors' => $vectors, 'error' => '', 'tokens' => (int)($data['usage']['total_tokens'] ?? 0)];
}

/** Similitud coseno entre dos vectores. */
function cosine_similarity(array $a, array $b): float {
    $n = min(count($a), count($b));
    if ($n === 0) return 0.0;
    $dot = 0.0; $na = 0.0; $nb = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $va = (float)$a[$i]; $vb = (float)$b[$i];
        $dot += $va * $vb; $na += $va * $va; $nb += $vb * $vb;
    }
    $den = sqrt($na) * sqrt($nb);
    return $den > 0 ? $dot / $den : 0.0;
}

/**
 * Búsqueda vectorial: embebe el query, compara con los chunks que tienen embedding, top-K.
 * @return array<int,array{title:string,content:string,score:float}>
 */
function knowledge_search_vector(string $query, int $topK = 4): array {
    $query = trim($query);
    if ($query === '' || !embeddings_available()) return [];
    $emb = openai_embed($query);
    if (!$emb['ok'] || empty($emb['vectors'][0])) return [];
    $qv = $emb['vectors'][0];

    $st = db()->query("SELECT title, content, embedding FROM knowledge_chunks WHERE embedding IS NOT NULL AND embedding <> ''");
    $scored = [];
    while ($row = $st->fetch()) {
        $vec = json_decode($row['embedding'], true);
        if (!is_array($vec) || empty($vec)) continue;
        $scored[] = ['title' => $row['title'], 'content' => $row['content'], 'score' => cosine_similarity($qv, $vec)];
    }
    if (!$scored) return [];
    usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($scored, 0, max(1, $topK));
}
