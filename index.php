<?php
/** Router principal del Agente DK. */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
boot();

ob_start();

// Si la BD no está lista, redirigir al instalador.
try {
    db()->query("SELECT 1 FROM settings LIMIT 1");
} catch (Throwable $e) {
    header('Location: install.php');
    exit;
}

$pages = [
    'chat'      => 'Daniel',
    'dashboard' => 'Panel',
    'leads'     => 'Leads',
    'lead'      => 'Detalle de Lead',
    'contenido' => 'Contenido',
    'agente'    => 'Agente Autónomo',
    'campanas'  => 'Campañas',
    'secuencias'=> 'Secuencias',
    'aprendizaje'=> 'Aprendizaje',
    'perfil'    => 'Perfil del Agente',
    'conocimiento' => 'Conocimiento',
    'outreach'  => 'Secuencias',
    'inbox'     => 'Bandeja',
    'ajustes'   => 'Ajustes',
    'usuarios'  => 'Usuarios',
    'login'     => 'Ingresar',
];

$fullscreen_pages = ['login'];

$active = $_GET['page'] ?? 'chat';
$isFullscreen = in_array($active, $fullscreen_pages, true);
if (!$isFullscreen && !isset($pages[$active])) $active = 'dashboard';
$page_title = $pages[$active] ?? ucfirst($active);

// ── Guard de autenticación ──────────────────────────────────────────────────
require_once __DIR__ . '/includes/auth.php';
if (auth_user_count() === 0) {
    // Sin usuarios todavía: forzar el alta del primer administrador.
    $active = 'login'; $isFullscreen = true; $page_title = 'Configurar acceso';
} elseif (!is_logged_in() && $active !== 'login') {
    header('Location: index.php?page=login'); exit;
} elseif (is_logged_in() && $active === 'login') {
    header('Location: index.php?page=chat'); exit;
}

$file = __DIR__ . "/pages/{$active}.php";
if (!file_exists($file)) {
    $active     = 'chat';
    $file       = __DIR__ . '/pages/chat.php';
    $page_title = 'Daniel';
    $isFullscreen = false;
}

if ($isFullscreen) {
    require $file;
} else {
    require __DIR__ . '/includes/layout_top.php';
    require $file;
    require __DIR__ . '/includes/layout_bottom.php';
}
