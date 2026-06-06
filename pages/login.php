<?php
/** Login / alta del primer administrador — pantalla full-screen (sin sidebar). */
$setup = (auth_user_count() === 0);
$csrf  = generate_csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($csrf) ?>">
<title><?= $setup ? 'Configurar acceso' : 'Ingresar' ?> &middot; DK &middot; SISTEL</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
<script src="assets/app.js"></script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }
  .spin { animation: spin 1s linear infinite; }
</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-sm">
    <div class="flex items-center gap-3 mb-6 justify-center">
      <div class="h-11 w-11 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold text-lg shrink-0">DK</div>
      <div class="leading-tight">
        <div class="font-semibold text-slate-900">Daniel Khan</div>
        <div class="text-xs text-slate-500">Agente SISTEL LATAM</div>
      </div>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 shadow-sm">
      <?php if ($setup): ?>
        <h1 class="text-base font-semibold text-slate-900 mb-1">Crear acceso de administrador</h1>
        <p class="text-sm text-slate-500 mb-5">Es la primera vez que entrás. Definí tu usuario y contraseña.</p>
      <?php else: ?>
        <h1 class="text-base font-semibold text-slate-900 mb-1">Ingresar</h1>
        <p class="text-sm text-slate-500 mb-5">Accedé al panel del agente.</p>
      <?php endif; ?>

      <form id="authForm" class="space-y-4" onsubmit="return doAuth(event)">
        <?php if ($setup): ?>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre</label>
          <input id="f_name" type="text" autocomplete="name"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                 placeholder="Tu nombre">
        </div>
        <?php endif; ?>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Usuario</label>
          <input id="f_user" type="text" autocomplete="username" required
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña</label>
          <input id="f_pass" type="password" required
                 autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <?php if ($setup): ?><p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres.</p><?php endif; ?>
        </div>
        <button id="authBtn" type="submit"
                class="w-full px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
          <?= $setup ? 'Crear y entrar' : 'Entrar' ?>
        </button>
      </form>
    </div>
    <p class="text-center text-xs text-slate-400 mt-5">SISTEL Tools &middot; acceso restringido</p>
  </div>

<script>
var SETUP = <?= $setup ? 'true' : 'false' ?>;
async function doAuth(e) {
  e.preventDefault();
  var btn = document.getElementById('authBtn');
  var restore = loading(btn, SETUP ? 'Creando…' : 'Entrando…');
  var data = {
    action:   SETUP ? 'setup' : 'login',
    username: document.getElementById('f_user').value.trim(),
    password: document.getElementById('f_pass').value,
  };
  if (SETUP) { var n = document.getElementById('f_name'); data.name = n ? n.value.trim() : ''; }
  var r = await api('api/auth.php', data);
  restore();
  if (r && r.ok) { window.location.href = 'index.php?page=dashboard'; }
  else { toast(r.error || 'No se pudo continuar.', 'error'); }
  return false;
}
</script>
</body>
</html>
