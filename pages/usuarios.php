<?php
/** Mi cuenta (cambiar contraseña) + gestión de usuarios del panel. */
$me = current_user() ?: [];
?>
<div class="max-w-3xl space-y-5">

  <!-- Mi cuenta -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">Mi cuenta</h2>
    <p class="text-xs text-slate-500 mb-4">
      Sesión iniciada como <strong class="text-slate-700"><?= e($me['name'] ?: $me['username']) ?></strong>
      (<?= e($me['username'] ?? '') ?>)
    </p>
    <form onsubmit="return changePass(event)" class="space-y-3 max-w-sm">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña actual</label>
        <input id="cp_cur" type="password" autocomplete="current-password"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nueva contraseña</label>
        <input id="cp_new" type="password" autocomplete="new-password"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres.</p>
      </div>
      <button id="cpBtn" type="submit"
              class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Cambiar contraseña
      </button>
    </form>
  </div>

  <!-- Usuarios del panel -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-sm font-semibold text-slate-900">Usuarios del panel</h2>
        <p class="text-xs text-slate-500">Quiénes pueden acceder a la plataforma.</p>
      </div>
      <button onclick="toggleNewUser()"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo usuario
      </button>
    </div>

    <div id="newUserForm" class="hidden mb-4 rounded-lg ring-1 ring-slate-200 bg-slate-50 p-4">
      <div class="grid sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre</label>
          <input id="nu_name" type="text" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Daniel Khan">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Usuario</label>
          <input id="nu_user" type="text" autocomplete="off" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="daniel">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña</label>
          <input id="nu_pass" type="password" autocomplete="new-password" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Mínimo 8">
        </div>
      </div>
      <div class="flex items-center gap-2 mt-3">
        <button id="nuBtn" onclick="createUser()" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">Crear</button>
        <button onclick="toggleNewUser()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-100 transition">Cancelar</button>
      </div>
    </div>

    <div id="usersList" class="divide-y divide-slate-100">
      <div class="py-4 text-sm text-slate-400 text-center">Cargando…</div>
    </div>
  </div>
</div>

<script>
var MY_ID = <?= (int)($me['id'] ?? 0) ?>;

async function changePass(e) {
  e.preventDefault();
  var cur = document.getElementById('cp_cur').value;
  var nw  = document.getElementById('cp_new').value;
  if (!cur || !nw) { toast('Completá ambos campos.', 'error'); return false; }
  var restore = loading(document.getElementById('cpBtn'), 'Cambiando…');
  var r = await api('api/auth.php', { action: 'change_password', current: cur, new: nw });
  restore();
  if (r && r.ok) {
    toast('Contraseña actualizada.', 'ok');
    document.getElementById('cp_cur').value = '';
    document.getElementById('cp_new').value = '';
  } else { toast(r.error || 'No se pudo cambiar.', 'error'); }
  return false;
}

function toggleNewUser() {
  document.getElementById('newUserForm').classList.toggle('hidden');
}

async function loadUsers() {
  var r = await api('api/auth.php', { action: 'list_users' });
  var c = document.getElementById('usersList');
  if (!r || !r.ok || !r.users || !r.users.length) {
    c.innerHTML = '<div class="py-4 text-sm text-slate-400 text-center">Sin usuarios.</div>';
    return;
  }
  c.innerHTML = r.users.map(function(u) {
    var last = u.last_login ? ('Último acceso: ' + String(u.last_login).substring(0,16)) : 'Nunca ingresó';
    var isMe = (parseInt(u.id) === MY_ID);
    return '<div class="py-3 flex items-center justify-between gap-3">'
      + '<div class="min-w-0">'
      + '<div class="text-sm font-medium text-slate-900">' + escapeHtml(u.name || u.username)
      + (isMe ? ' <span class="text-xs font-normal text-indigo-600">(vos)</span>' : '') + '</div>'
      + '<div class="text-xs text-slate-500">' + escapeHtml(u.username) + ' · ' + escapeHtml(last) + '</div>'
      + '</div>'
      + (isMe ? '' : '<button onclick="delUser(' + parseInt(u.id) + ",'" + escapeHtml(u.username).replace(/'/g,"") + "')\" class=\"text-xs text-red-600 hover:underline shrink-0\">Eliminar</button>")
      + '</div>';
  }).join('');
}

async function createUser() {
  var data = {
    action:   'create_user',
    name:     document.getElementById('nu_name').value.trim(),
    username: document.getElementById('nu_user').value.trim(),
    password: document.getElementById('nu_pass').value,
  };
  if (!data.username || !data.password) { toast('Usuario y contraseña son obligatorios.', 'error'); return; }
  var restore = loading(document.getElementById('nuBtn'), 'Creando…');
  var r = await api('api/auth.php', data);
  restore();
  if (r && r.ok) {
    toast('Usuario creado.', 'ok');
    document.getElementById('nu_name').value = '';
    document.getElementById('nu_user').value = '';
    document.getElementById('nu_pass').value = '';
    toggleNewUser();
    loadUsers();
  } else { toast(r.error || 'No se pudo crear.', 'error'); }
}

async function delUser(id, username) {
  if (!confirm('¿Eliminar al usuario "' + username + '"? No podrá acceder más.')) return;
  var r = await api('api/auth.php', { action: 'delete_user', id: id });
  if (r && r.ok) { toast('Usuario eliminado.', 'ok'); loadUsers(); }
  else { toast(r.error || 'No se pudo eliminar.', 'error'); }
}

loadUsers();
</script>
