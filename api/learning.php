<?php
/** API del bucle de aprendizaje: métricas + ejemplos. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/learning.php';
boot();
verify_csrf_token();
require_once __DIR__ . '/../includes/auth.php';
require_auth_api();

$d = json_in();
switch ($d['action'] ?? '') {
    case 'stats':
        json_out(['ok' => true, 'stats' => learning_stats(), 'examples' => learning_list(30)]);
    default:
        json_out(['ok' => false, 'error' => 'Acción desconocida.'], 400);
}
