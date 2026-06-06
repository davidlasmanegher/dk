<?php
/** Cabecera y barra lateral del Agente DK. Espera: $page_title, $active. */
if (!isset($active))     $active     = '';
if (!isset($page_title)) $page_title = 'Panel';

$nav = [
    ['dashboard', 'Panel',            'M3 12l9-9 9 9M5 10v10h14V10'],
    ['leads',     'Leads',            'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-2-3.46'],
    ['contenido', 'Contenido',        'M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v4h4M9 13h6M9 17h4'],
    ['outreach',  'Outreach',         'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    ['agente',    'Agente',           'M13 10V3L4 14h7v7l9-11h-7z'],
    ['perfil',    'Perfil',           'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ['ajustes',   'Ajustes',          'M10.3 3.3a2 2 0 013.4 0l.4.7a2 2 0 002 .9l.8-.1a2 2 0 012.2 2.2l-.1.8a2 2 0 00.9 2l.7.4a2 2 0 010 3.4l-.7.4a2 2 0 00-.9 2l.1.8a2 2 0 01-2.2 2.2l-.8-.1a2 2 0 00-2 .9l-.4.7a2 2 0 01-3.4 0l-.4-.7a2 2 0 00-2-.9l-.8.1a2 2 0 01-2.2-2.2l.1-.8a2 2 0 00-.9-2l-.7-.4a2 2 0 010-3.4l.7-.4a2 2 0 00.9-2l-.1-.8A2 2 0 016.7 4.8l.8.1a2 2 0 002-.9zM12 15a3 3 0 100-6 3 3 0 000 6z'],
    ['usuarios',  'Usuarios',         'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 12l2 2 4-4'],
];

$key_ok = has_api_key();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(generate_csrf_token()) ?>">
<title><?= e($page_title) ?> &middot; DK &middot; Agente SISTEL</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
<script src="assets/app.js"></script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  #sidebar  { transition: transform .2s ease; }
  #mainWrap { transition: margin-left .2s ease; }
  @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
  .spin { animation: spin 1s linear infinite; }
</style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
<div class="flex min-h-screen">

  <!-- Overlay mobile -->
  <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/30 z-20 lg:hidden"></div>

  <!-- Sidebar -->
  <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-56 flex flex-col -translate-x-full lg:translate-x-0" style="background:#0f172a">
    <!-- Logo -->
    <div class="h-14 flex items-center gap-3 px-4 border-b border-white/10">
      <div class="h-9 w-9 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold text-sm shrink-0">DK</div>
      <div class="leading-tight min-w-0">
        <div class="text-sm font-semibold text-white truncate">Daniel Khan</div>
        <div class="text-[11px] text-slate-400 truncate">Agente SISTEL LATAM</div>
      </div>
      <button id="sidebarClose" class="lg:hidden ml-auto shrink-0 p-1 rounded text-slate-400 hover:text-white">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-2 py-3 overflow-y-auto space-y-0.5">
      <?php foreach ($nav as [$slug, $label, $icon]):
        $is = ($active === $slug); ?>
        <a href="index.php?page=<?= $slug ?>"
           class="group flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition <?= $is ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-white/10 hover:text-white' ?>">
          <svg class="h-4 w-4 shrink-0 <?= $is ? 'text-white' : 'text-slate-500 group-hover:text-white' ?>"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="<?= $icon ?>"/>
          </svg>
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Footer sidebar -->
    <div class="px-4 py-3 border-t border-white/10">
      <?php if ($key_ok): ?>
        <div class="flex items-center gap-2 text-xs text-emerald-400">
          <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
          IA conectada
        </div>
      <?php else: ?>
        <a href="index.php?page=ajustes" class="flex items-center gap-2 text-xs text-amber-400 no-underline">
          <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
          Configura tu API key
        </a>
      <?php endif; ?>
    </div>
  </aside>

  <!-- Main -->
  <div id="mainWrap" class="flex-1 flex flex-col min-h-screen lg:ml-56">
    <!-- Topbar -->
    <header class="h-12 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-10">
      <div class="flex items-center gap-2">
        <button id="sidebarToggle" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="text-sm font-semibold text-slate-900"><?= e($page_title) ?></h1>
      </div>
      <!-- Slot de acciones de página -->
      <div id="topbarActions" class="flex items-center gap-2">
        <?php if (!$key_ok && $active !== 'ajustes'): ?>
          <a href="index.php?page=ajustes" class="text-xs font-medium text-amber-700 bg-amber-50 ring-1 ring-amber-200 px-3 py-1 rounded-full hover:bg-amber-100 transition">
            Configura API key
          </a>
        <?php endif; ?>
        <?php $cu = function_exists('current_user') ? current_user() : null; if ($cu): ?>
          <a href="index.php?page=usuarios" class="hidden sm:inline text-xs text-slate-500 hover:text-indigo-600 transition">Hola, <?= e($cu['name'] ?: $cu['username']) ?></a>
          <button onclick="dkLogout()" title="Cerrar sesión"
                  class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200 px-2.5 py-1 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            Salir
          </button>
        <?php endif; ?>
      </div>
    </header>

    <main class="flex-1 px-5 py-6 lg:px-8 lg:py-7">
      <?php if (!$key_ok && $active !== 'ajustes'): ?>
        <div class="mb-5 rounded-xl bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-sm text-amber-800 flex items-center justify-between">
          <span>La generación con IA está desactivada. Agrega tu API key de Claude para activarla.</span>
          <a href="index.php?page=ajustes" class="font-semibold underline shrink-0 ml-4 text-amber-800">Ir a Ajustes</a>
        </div>
      <?php endif; ?>

<script>
(function () {
  var sidebar  = document.getElementById('sidebar');
  var overlay  = document.getElementById('sidebarOverlay');
  var toggler  = document.getElementById('sidebarToggle');
  var closeBtn = document.getElementById('sidebarClose');
  var mainWrap = document.getElementById('mainWrap');

  function lg() { return window.innerWidth >= 1024; }
  var desktopOpen = localStorage.getItem('dk_sb') !== '0';

  function applyDesktop(open) {
    overlay.classList.add('hidden');
    if (open) {
      sidebar.style.removeProperty('transform');
      mainWrap.style.removeProperty('margin-left');
    } else {
      sidebar.style.transform = 'translateX(-100%)';
      mainWrap.style.marginLeft = '0px';
    }
  }

  function applyMobile(open) {
    mainWrap.style.removeProperty('margin-left');
    if (open) {
      sidebar.style.transform = 'translateX(0)';
      overlay.classList.remove('hidden');
    } else {
      sidebar.style.removeProperty('transform');
      overlay.classList.add('hidden');
    }
  }

  function init() {
    if (lg()) applyDesktop(desktopOpen);
    else      applyMobile(false);
  }

  toggler.addEventListener('click', function () {
    if (lg()) {
      desktopOpen = !desktopOpen;
      localStorage.setItem('dk_sb', desktopOpen ? '1' : '0');
      applyDesktop(desktopOpen);
    } else {
      applyMobile(true);
    }
  });
  closeBtn.addEventListener('click', function () { applyMobile(false); });
  overlay.addEventListener('click',  function () { applyMobile(false); });
  window.addEventListener('resize',  function () {
    if (lg()) applyDesktop(desktopOpen);
    else      applyMobile(false);
  });
  init();
})();
</script>
