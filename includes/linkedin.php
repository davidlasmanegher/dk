<?php
/**
 * Cliente de publicación en LinkedIn (API oficial /rest/posts).
 * Publica en la página de SISTEL México como organización.
 * Settings: linkedin_token (access token con w_organization_social),
 *           linkedin_author_urn (urn:li:organization:{ID} de la página),
 *           linkedin_version (default 202405).
 */
require_once __DIR__ . '/db.php';

const LINKEDIN_POSTS_URL = 'https://api.linkedin.com/rest/posts';

function linkedin_version(): string {
    $v = trim((string)setting('linkedin_version', ''));
    return $v !== '' ? $v : '202506';
}

/** ¿LinkedIn configurado (token + URN de la página/persona)? */
function linkedin_available(): bool {
    return strlen(trim((string)setting('linkedin_token', ''))) > 20
        && trim((string)setting('linkedin_author_urn', '')) !== '';
}

/**
 * Publica un texto en LinkedIn como el autor configurado (organización o persona).
 * @return array{ok:bool, error:string, http?:int}
 */
function linkedin_publish(string $text): array {
    $token  = trim((string)setting('linkedin_token', ''));
    $author = trim((string)setting('linkedin_author_urn', ''));
    if (strlen($token) < 20 || $author === '') {
        return ['ok' => false, 'error' => 'Falta el token o el URN de LinkedIn en Ajustes.'];
    }
    $text = trim($text);
    if ($text === '') return ['ok' => false, 'error' => 'El texto del post está vacío.'];

    $payload = [
        'author'        => $author,
        'commentary'    => $text,
        'visibility'    => 'PUBLIC',
        'distribution'  => ['feedDistribution' => 'MAIN_FEED', 'targetEntities' => [], 'thirdPartyDistributionChannels' => []],
        'lifecycleState'=> 'PUBLISHED',
        'isReshareDisabledByAuthor' => false,
    ];

    $ch = curl_init(LINKEDIN_POSTS_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: ' . linkedin_version(),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT    => 30,
    ]);
    $res   = curl_exec($ch);
    $errno = curl_errno($ch);
    $cerr  = curl_error($ch);
    $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) return ['ok' => false, 'error' => 'Error de red al contactar LinkedIn: ' . $cerr];
    if ($http >= 200 && $http < 300) return ['ok' => true, 'error' => '', 'http' => $http];

    $d   = json_decode($res, true);
    $msg = $d['message'] ?? ('LinkedIn respondió HTTP ' . $http);
    return ['ok' => false, 'error' => $msg, 'http' => $http];
}
