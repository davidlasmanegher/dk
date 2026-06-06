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
    'dashboard' => 'Panel',
    'leads'     => 'Leads',
    'lead'      => 'Detalle de Lead',
    'contenido' => 'Contenido',
    'agente'    => 'Agente Autónomo',
    'perfil'    => 'Perfil del Agente',
    'outreach'  => 'Outreach',
    'inbox'     => 'Bandeja',
    'ajustes'   => 'Ajustes',
    'usuarios'  => 'Usuarios',
    'login'     => 'Ingresar',
];

$fullscreen_pages = ['login'];

$active = $_GET['page'] ?? 'dashboard';
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
    header('Location: index.php?page=dashboard'); exit;
}

$file = __DIR__ . "/pages/{$active}.php";
if (!file_exists($file)) {
    $active     = 'dashboard';
    $file       = __DIR__ . '/pages/dashboard.php';
    $page_title = 'Panel';
    $isFullscreen = false;
}

if ($isFullscreen) {
    require $file;
} else {
    require __DIR__ . '/includes/layout_top.php';
    require $file;
    require __DIR__ . '/includes/layout_bottom.php';
}
