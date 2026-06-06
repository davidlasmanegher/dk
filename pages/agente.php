<?php
/** Cola de tareas autónomas del agente */
$auto_mode = setting('agent_auto_mode', '0') === '1';
?>
<div class="space-y-5">

  <!-- Estado del agente -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="h-10 w-10 rounded-xl <?= $auto_mode ? 'bg-emerald-100' : 'bg-slate-100' ?> grid place-items-center">
        <svg class="h-5 w-5 <?= $auto_mode ? 'text-emerald-600' : 'text-slate-400' ?>"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
      </div>
      <div>
        <div class="text-sm font-semibold text-slate-900">Agente autónomo</div>
        <div class="text-xs <?= $auto_mode ? 'text-emerald-600' : 'text-slate-400' ?>">
          <?= $auto_mode ? 'Modo automático activo' : 'Modo manual (auto desactivado)' ?>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-xs text-slate-500">Límite diario: <?= e(setting('agent_daily_limit', '20')) ?> tareas</span>
      <a href="index.php?page=ajustes" class="text-xs text-indigo-600 font-medium hover:underline">Configurar</a>
    </div>
  </div>

  <!-- Sugerencias para aprobar -->
  <div id="suggestionsWrap" class="hidden">
    <h3 class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
      <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      Sugerencias para aprobar
    </h3>
    <div id="suggestionsList" class="grid gap-3"></div>
  </div>

  <!-- Acciones -->
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2">
      <select id="filterStatus" onchange="loadTasks()"
              class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        <option value="">Todas</option>
        <option value="pendiente" selected>Pendientes</option>
        <option value="en_proceso">En proceso</option>
        <option value="completada">Completadas</option>
        <option value="fallida">Fallidas</option>
        <option value="cancelada">Canceladas</option>
      </select>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="runAgent()" id="runAgentBtn"
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Ejecutar ahora
      </button>
      <button onclick="openNewTaskModal()"
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg ring-1 ring-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Nueva tarea
      </button>
    </div>
  </div>

  <!-- Tabla tareas -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-xs text-slate-500 uppercase tracking-wide border-b border-slate-100">
          <th class="px-5 py-3 text-left font-medium">Tipo</th>
          <th class="px-5 py-3 text-left font-medium">Lead</th>
          <th class="px-5 py-3 text-left font-medium">Estado</th>
          <th class="px-5 py-3 text-left font-medium">Prioridad</th>
          <th class="px-5 py-3 text-left font-medium">Programada</th>
          <th class="px-5 py-3 text-left font-medium">Resultado</th>
          <th class="px-5 py-3 text-left font-medium"></th>
        </tr>
      </thead>
      <tbody id="tasksTableBody">
        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Cargando…</td></tr>
      </tbody>
    </table>
  </div>

</div>

<!-- Modal nueva tarea -->
<div id="newTaskModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-md">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900">Nueva tarea</h2>
      <button onclick="closeNewTaskModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de tarea</label>
        <select id="nt_type" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <option value="follow_up">Seguimiento</option>
          <option value="content_gen">Generar contenido</option>
          <option value="outreach">Outreach inicial</option>
          <option value="lead_research">Investigar lead</option>
          <option value="daily_digest">Digest diario</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Lead (opcional)</label>
        <select id="nt_lead_id" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <option value="">— ninguno —</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Prioridad</label>
        <select id="nt_priority" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <option value="1">Alta (1)</option>
          <option value="3">Media-alta (3)</option>
          <option value="5" selected>Normal (5)</option>
          <option value="7">Media-baja (7)</option>
          <option value="10">Baja (10)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Fecha programada</label>
        <input type="datetime-local" id="nt_scheduled_at" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeNewTaskModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="saveTaskBtn" onclick="saveTask()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Crear tarea</button>
    </footer>
  </div>
</div>

<script>
var statusColors = {
  pendiente:  'bg-amber-50 text-amber-700 ring-amber-200',
  sugerida:   'bg-violet-50 text-violet-700 ring-violet-200',
  en_proceso: 'bg-sky-50 text-sky-700 ring-sky-200',
  completada: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  fallida:    'bg-red-50 text-red-600 ring-red-200',
  cancelada:  'bg-slate-100 text-slate-400 ring-slate-200',
};
var typeLabels = { follow_up:'Seguimiento', content_gen:'Contenido', outreach:'Outreach', lead_research:'Investigación', daily_digest:'Digest' };

async function runAgent() {
  var restore = loading(document.getElementById('runAgentBtn'), 'Ejecutando…');
  var r = await api('api/agent.php', { action: 'run' });
  restore();
  if (r && r.ok) {
    toast('Agente: ' + r.planned + ' planificadas · ' + r.suggested + ' a aprobar · ' + r.sent + ' enviadas' + (r.failed ? (' · ' + r.failed + ' fallidas') : ''), 'ok');
    loadSuggestions(); loadTasks();
  } else { toast(r.error || 'Error al ejecutar el agente.', 'error'); }
}

async function loadSuggestions() {
  var r = await api('api/agent.php', { action: 'list_tasks', status: 'sugerida', limit: 50 });
  var wrap = document.getElementById('suggestionsWrap');
  var list = document.getElementById('suggestionsList');
  if (!r || !r.ok || !r.tasks || !r.tasks.length) { wrap.classList.add('hidden'); list.innerHTML = ''; return; }
  wrap.classList.remove('hidden');
  list.innerHTML = r.tasks.map(function(t) {
    var p = {}; try { p = JSON.parse(t.payload || '{}'); } catch (e) {}
    var canal = (p.channel === 'whatsapp') ? 'WhatsApp' : 'Email';
    return '<div class="bg-white rounded-xl ring-1 ring-amber-200 p-4">'
      + '<div class="text-sm font-medium text-slate-900 mb-2">' + escapeHtml(t.lead_name || '—')
      + ' <span class="text-xs font-normal text-slate-400">· ' + canal + ' · primer contacto</span></div>'
      + (p.subject ? '<div class="text-xs text-slate-500 mb-1">Asunto: <span class="text-slate-800">' + escapeHtml(p.subject) + '</span></div>' : '')
      + '<div class="text-sm text-slate-700 whitespace-pre-line rounded-lg bg-slate-50 ring-1 ring-slate-100 p-3 mb-3">' + escapeHtml(p.body || '') + '</div>'
      + '<div class="flex items-center gap-2">'
      + '<button onclick="approveTask(' + t.id + ')" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">Aprobar y enviar</button>'
      + '<button onclick="discardTask(' + t.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-slate-600 text-xs font-medium hover:bg-slate-50">Descartar</button>'
      + '</div></div>';
  }).join('');
}

async function approveTask(id) {
  if (!confirm('¿Aprobar y enviar este primer contacto al prospecto? Es real e inmediato.')) return;
  var r = await api('api/agent.php', { action: 'approve_task', id: id });
  if (r && r.ok) { toast('Enviado y registrado.', 'ok'); loadSuggestions(); loadTasks(); }
  else { toast(r.error || 'No se pudo enviar.', 'error'); }
}

async function discardTask(id) {
  var r = await api('api/agent.php', { action: 'discard_task', id: id });
  if (r && r.ok) { toast('Sugerencia descartada.', 'ok'); loadSuggestions(); loadTasks(); }
  else { toast(r.error || 'Error.', 'error'); }
}

async function loadTasks() {
  var status = document.getElementById('filterStatus').value;
  var tbody  = document.getElementById('tasksTableBody');
  tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Cargando…</td></tr>';
  var r = await api('api/agent.php', { action: 'list_tasks', status: status, limit: 50 });
  if (!r || !r.ok || !r.tasks) { tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Error al cargar.</td></tr>'; return; }
  if (r.tasks.length === 0) { tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Sin tareas.</td></tr>'; return; }
  tbody.innerHTML = r.tasks.map(function(t) {
    var sb = statusColors[t.status] || 'bg-slate-100 text-slate-600 ring-slate-200';
    var priorityLabel = t.priority <= 2 ? 'Alta' : (t.priority <= 4 ? 'Media-alta' : (t.priority <= 6 ? 'Normal' : 'Baja'));
    return '<tr class="border-b border-slate-50 hover:bg-slate-50/50 transition">'
      + '<td class="px-5 py-3 font-medium text-slate-900">' + escapeHtml(typeLabels[t.type] || t.type) + '</td>'
      + '<td class="px-5 py-3 text-slate-500 text-xs">' + escapeHtml(t.lead_name || '—') + '</td>'
      + '<td class="px-5 py-3"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + sb + '">' + escapeHtml(t.status) + '</span></td>'
      + '<td class="px-5 py-3 text-slate-500 text-xs">' + priorityLabel + '</td>'
      + '<td class="px-5 py-3 text-slate-500 text-xs">' + (t.scheduled_at ? t.scheduled_at.substring(0,16) : '—') + '</td>'
      + '<td class="px-5 py-3 text-slate-500 text-xs max-w-xs"><span class="line-clamp-2">' + escapeHtml(t.result || '—') + '</span></td>'
      + '<td class="px-5 py-3">'
      + (t.status === 'pendiente' ? '<button onclick="cancelTask(' + t.id + ')" class="text-xs text-red-500 hover:underline">Cancelar</button>' : '')
      + '</td>'
      + '</tr>';
  }).join('');
}

async function cancelTask(id) {
  var r = await api('api/agent.php', { action: 'update_task_status', id: id, status: 'cancelada' });
  if (r && r.ok) { toast('Tarea cancelada.', 'ok'); loadTasks(); }
  else toast(r.error || 'Error.', 'error');
}

// Cargar leads para el select
(async function() {
  var r = await api('api/leads.php', { action: 'list_leads', limit: 200, offset: 0 });
  var sel = document.getElementById('nt_lead_id');
  if (r && r.ok && r.leads) {
    r.leads.forEach(function(l) {
      var opt = document.createElement('option');
      opt.value = l.id;
      opt.textContent = (l.first_name + ' ' + (l.last_name || '')).trim() + (l.company ? ' — ' + l.company : '');
      sel.appendChild(opt);
    });
  }
})();

function openNewTaskModal() { document.getElementById('newTaskModal').classList.remove('hidden'); }
function closeNewTaskModal() { document.getElementById('newTaskModal').classList.add('hidden'); }

async function saveTask() {
  var btn = document.getElementById('saveTaskBtn');
  var restore = loading(btn, 'Creando…');
  var r = await api('api/agent.php', {
    action:       'create_task',
    type:         document.getElementById('nt_type').value,
    lead_id:      document.getElementById('nt_lead_id').value || null,
    priority:     parseInt(document.getElementById('nt_priority').value),
    scheduled_at: document.getElementById('nt_scheduled_at').value || null,
  });
  restore();
  if (r && r.ok) { toast('Tarea creada.', 'ok'); closeNewTaskModal(); loadTasks(); }
  else toast(r.error || 'Error al crear.', 'error');
}

document.getElementById('newTaskModal').addEventListener('click', function(e) { if (e.target === this) closeNewTaskModal(); });

loadSuggestions();
loadTasks();
</script>
