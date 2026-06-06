<?php
/** Dashboard ejecutivo del Agente DK */
?>
<div class="space-y-6">

  <!-- Foco del día -->
  <div id="focusBar" class="hidden grid sm:grid-cols-3 gap-4">
    <a href="index.php?page=agente" class="bg-white rounded-xl ring-1 ring-amber-200 p-4 flex items-center gap-3 hover:ring-amber-300 transition">
      <div class="h-10 w-10 rounded-lg bg-amber-50 text-amber-600 grid place-items-center shrink-0">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      </div>
      <div><div class="text-xl font-bold text-slate-900"><span id="f_suggestions">0</span></div><div class="text-xs text-slate-500">Sugerencias por aprobar</div></div>
    </a>
    <a href="index.php?page=inbox" class="bg-white rounded-xl ring-1 ring-sky-200 p-4 flex items-center gap-3 hover:ring-sky-300 transition">
      <div class="h-10 w-10 rounded-lg bg-sky-50 text-sky-600 grid place-items-center shrink-0">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
      </div>
      <div><div class="text-xl font-bold text-slate-900"><span id="f_inbox">0</span></div><div class="text-xs text-slate-500">Mensajes en bandeja</div></div>
    </a>
    <a href="index.php?page=leads&sort=recent" class="bg-white rounded-xl ring-1 ring-rose-200 p-4 flex items-center gap-3 hover:ring-rose-300 transition">
      <div class="h-10 w-10 rounded-lg bg-rose-50 text-rose-600 grid place-items-center shrink-0">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div><div class="text-xl font-bold text-slate-900"><span id="f_overdue">0</span></div><div class="text-xs text-slate-500">Seguimientos vencidos</div></div>
    </a>
  </div>

  <!-- KPIs -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $kpis = [
      ['Total leads',        'k_total',    'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-2-3.46', 'bg-indigo-50 text-indigo-600'],
      ['Alta prioridad',     'k_high',     'M13 10V3L4 14h7v7l9-11h-7z',                                                                              'bg-emerald-50 text-emerald-600'],
      ['Enviados (7 días)',  'k_week',     'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',                                                                    'bg-sky-50 text-sky-600'],
      ['Contenido en borrador','k_content','M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v4h4',                                       'bg-violet-50 text-violet-600'],
    ];
    foreach ($kpis as [$label, $id, $icon, $cls]): ?>
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4 flex items-center gap-3">
        <div class="h-10 w-10 rounded-lg <?= $cls ?> grid place-items-center shrink-0">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></svg>
        </div>
        <div><div class="text-2xl font-bold text-slate-900" id="<?= $id ?>">—</div><div class="text-xs text-slate-500"><?= e($label) ?></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <!-- Embudo + segmentos -->
    <div class="lg:col-span-2 space-y-5">
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Embudo del pipeline</h2>
        <div id="funnel" class="space-y-2.5 text-sm text-slate-400">Cargando…</div>
      </div>
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Leads por segmento</h2>
        <div id="segments" class="space-y-2.5 text-sm text-slate-400">Cargando…</div>
      </div>
    </div>

    <!-- Actividad + acciones -->
    <div class="space-y-4">
      <div class="bg-white rounded-xl ring-1 ring-slate-200">
        <div class="px-4 py-3.5 border-b border-slate-100"><h2 class="text-sm font-semibold text-slate-900">Actividad reciente</h2></div>
        <ul id="recentActivity" class="divide-y divide-slate-50 text-sm"><li class="px-4 py-3 text-slate-400">Cargando…</li></ul>
      </div>
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Acciones rápidas</h2>
        <div class="space-y-2">
          <button onclick="openNewLeadModal()" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo lead
          </button>
          <a href="index.php?page=leads" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg ring-1 ring-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h6v14H4zM14 5h6v8h-6z"/></svg>
            Ver pipeline
          </a>
          <a href="index.php?page=agente" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg ring-1 ring-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Ejecutar agente
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Nuevo Lead -->
<div id="newLeadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-lg max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900">Nuevo lead</h2>
      <button onclick="closeNewLeadModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label><input type="text" id="nl_first_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Apellido</label><input type="text" id="nl_last_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Empresa</label><input type="text" id="nl_company" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Cargo</label><input type="text" id="nl_role" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Ciudad</label><input type="text" id="nl_city" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Email</label><input type="email" id="nl_email" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">WhatsApp</label><input type="tel" id="nl_whatsapp" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500"></div>
        <div><label class="block text-xs font-medium text-slate-600 mb-1">Etapa</label>
          <select id="nl_stage" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="prospecto">Prospecto</option><option value="contactado">Contactado</option><option value="interesado">Interesado</option>
            <option value="propuesta">Propuesta</option><option value="negociacion">Negociación</option><option value="ganado">Ganado</option>
          </select>
        </div>
        <div class="col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Notas</label><textarea id="nl_notes" rows="3" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"></textarea></div>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeNewLeadModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="saveNewLeadBtn" onclick="saveNewLead()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar lead</button>
    </footer>
  </div>
</div>

<script>
var STAGE_ORDER = ['prospecto','contactado','interesado','propuesta','negociacion','ganado','perdido'];
var SEG_ORDER   = ['A','B','C','D','E'];
function stageLabel(s){ return {prospecto:'Prospecto',contactado:'Contactado',interesado:'Interesado',propuesta:'Propuesta',negociacion:'Negociación',ganado:'Ganado',perdido:'Perdido',pausado:'Pausado'}[s]||s; }
function segLabel(s){ return {A:'A · Estratégico',B:'B · RH/Talento',C:'C · Capacitación',D:'D · Alta dirección',E:'E · Nutrición'}[s]||('Segmento '+s); }
function stageBar(s){ return {prospecto:'bg-slate-400',contactado:'bg-sky-500',interesado:'bg-violet-500',propuesta:'bg-amber-500',negociacion:'bg-orange-500',ganado:'bg-emerald-500',perdido:'bg-red-400'}[s]||'bg-slate-400'; }
function segBar(s){ return {A:'bg-emerald-500',B:'bg-sky-500',C:'bg-violet-500',D:'bg-amber-500',E:'bg-slate-400'}[s]||'bg-slate-400'; }

function renderBars(containerId, order, data, labelFn, barFn) {
  var cont = document.getElementById(containerId);
  var max = 1;
  order.forEach(function(k){ if ((data[k]||0) > max) max = data[k]; });
  var rows = order.filter(function(k){ return (data[k]||0) > 0; });
  if (!rows.length) { cont.innerHTML = '<div class="text-slate-400 text-sm">Sin datos.</div>'; return; }
  cont.innerHTML = rows.map(function(k){
    var v = data[k] || 0; var pct = Math.max(3, Math.round(v / max * 100));
    return '<div>'
      + '<div class="flex items-center justify-between text-xs mb-1"><span class="text-slate-600">' + escapeHtml(labelFn(k)) + '</span><span class="font-semibold text-slate-900">' + v + '</span></div>'
      + '<div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full ' + barFn(k) + '" style="width:' + pct + '%"></div></div>'
      + '</div>';
  }).join('');
}

(async function() {
  var r = await api('api/agent.php', { action: 'dashboard' });
  if (!r || !r.ok) return;

  document.getElementById('k_total').textContent   = r.total;
  document.getElementById('k_high').textContent    = r.high;
  document.getElementById('k_week').textContent    = r.activity_week;
  document.getElementById('k_content').textContent = r.content_drafts;

  document.getElementById('f_suggestions').textContent = r.suggestions;
  document.getElementById('f_inbox').textContent       = r.inbox_pending;
  document.getElementById('f_overdue').textContent     = r.overdue;
  if (r.suggestions + r.inbox_pending + r.overdue > 0) document.getElementById('focusBar').classList.remove('hidden');

  renderBars('funnel', STAGE_ORDER, r.funnel || {}, stageLabel, stageBar);
  renderBars('segments', SEG_ORDER, r.by_segment || {}, segLabel, segBar);

  var ul = document.getElementById('recentActivity');
  if (!r.recent_activity || !r.recent_activity.length) {
    ul.innerHTML = '<li class="px-4 py-4 text-center text-sm text-slate-400">Sin actividad todavía.</li>';
  } else {
    ul.innerHTML = r.recent_activity.map(function(a){
      var dir = a.direction === 'in' ? 'text-emerald-600' : 'text-slate-400';
      var arrow = a.direction === 'in' ? '↓' : '↑';
      return '<li class="px-4 py-2.5">'
        + '<div class="flex items-center justify-between gap-2">'
        + '<span class="text-xs font-medium text-slate-700">' + escapeHtml(a.lead_name || '—') + '</span>'
        + '<span class="text-[11px] text-slate-400">' + (a.sent_at || '').substring(5,16) + '</span></div>'
        + '<div class="text-xs text-slate-500"><span class="' + dir + '">' + arrow + ' ' + escapeHtml(a.type) + '</span> ' + escapeHtml((a.subject || a.body || '').substring(0,46)) + '</div>'
        + '</li>';
    }).join('');
  }
})();

function openNewLeadModal(){ document.getElementById('newLeadModal').classList.remove('hidden'); }
function closeNewLeadModal(){ document.getElementById('newLeadModal').classList.add('hidden'); }
async function saveNewLead() {
  var btn = document.getElementById('saveNewLeadBtn');
  var restore = loading(btn, 'Guardando…');
  var data = {
    action: 'save_lead',
    first_name: document.getElementById('nl_first_name').value.trim(),
    last_name: document.getElementById('nl_last_name').value.trim(),
    company: document.getElementById('nl_company').value.trim(),
    role: document.getElementById('nl_role').value.trim(),
    city: document.getElementById('nl_city').value.trim(),
    email: document.getElementById('nl_email').value.trim(),
    whatsapp_phone: document.getElementById('nl_whatsapp').value.trim(),
    stage: document.getElementById('nl_stage').value,
    notes: document.getElementById('nl_notes').value.trim(),
  };
  if (!data.first_name) { restore(); toast('El nombre es obligatorio.', 'error'); return; }
  var r = await api('api/leads.php', data);
  restore();
  if (r && r.ok) { toast('Lead creado.', 'ok'); closeNewLeadModal(); window.location.reload(); }
  else toast(r.error || 'Error al guardar.', 'error');
}
document.getElementById('newLeadModal').addEventListener('click', function(e){ if (e.target === this) closeNewLeadModal(); });
</script>
