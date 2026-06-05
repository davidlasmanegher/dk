<?php
/** Dashboard del Agente DK */
?>
<div class="space-y-6">

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="statsRow">
    <?php
    $stats = [
      ['Total leads',         'total_leads',       'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-2-3.46', 'bg-indigo-50 text-indigo-600'],
      ['Leads esta semana',   'leads_this_week',   'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                  'bg-sky-50 text-sky-600'],
      ['Actividades hoy',     'activities_today',  'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',                                                 'bg-emerald-50 text-emerald-600'],
      ['Piezas de contenido', 'content_pieces',    'M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v4h4',                                        'bg-violet-50 text-violet-600'],
    ];
    foreach ($stats as [$label, $key, $icon, $iconClass]): ?>
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4 flex items-center gap-3">
        <div class="h-10 w-10 rounded-lg <?= $iconClass ?> grid place-items-center shrink-0">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="<?= $icon ?>"/>
          </svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900 stat-value" data-key="<?= $key ?>">
            <span class="skeleton inline-block w-8 h-6">&nbsp;</span>
          </div>
          <div class="text-xs text-slate-500"><?= e($label) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">

    <!-- Leads recientes -->
    <div class="lg:col-span-2 bg-white rounded-xl ring-1 ring-slate-200">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Leads recientes</h2>
        <a href="index.php?page=leads" class="text-xs text-indigo-600 font-medium hover:underline">Ver todos</a>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm" id="recentLeadsTable">
          <thead>
            <tr class="text-xs text-slate-500 uppercase tracking-wide border-b border-slate-100">
              <th class="px-5 py-2.5 text-left font-medium">Nombre</th>
              <th class="px-5 py-2.5 text-left font-medium">Empresa</th>
              <th class="px-5 py-2.5 text-left font-medium">Etapa</th>
              <th class="px-5 py-2.5 text-left font-medium">Score</th>
              <th class="px-5 py-2.5 text-left font-medium"></th>
            </tr>
          </thead>
          <tbody id="recentLeadsBody">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <tr class="border-b border-slate-50">
                <td class="px-5 py-3"><span class="skeleton block h-4 w-28">&nbsp;</span></td>
                <td class="px-5 py-3"><span class="skeleton block h-4 w-24">&nbsp;</span></td>
                <td class="px-5 py-3"><span class="skeleton block h-4 w-16">&nbsp;</span></td>
                <td class="px-5 py-3"><span class="skeleton block h-4 w-8">&nbsp;</span></td>
                <td class="px-5 py-3"><span class="skeleton block h-4 w-10">&nbsp;</span></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel derecho -->
    <div class="space-y-4">
      <!-- Tareas del agente -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200">
        <div class="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Tareas pendientes</h2>
          <a href="index.php?page=agente" class="text-xs text-indigo-600 font-medium hover:underline">Ver todas</a>
        </div>
        <ul id="pendingTasksList" class="divide-y divide-slate-50 text-sm">
          <?php for ($i = 0; $i < 3; $i++): ?>
            <li class="px-4 py-3"><span class="skeleton block h-4 w-full">&nbsp;</span></li>
          <?php endfor; ?>
        </ul>
      </div>

      <!-- Acciones rápidas -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Acciones rápidas</h2>
        <div class="space-y-2">
          <button onclick="openNewLeadModal()"
                  class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo lead
          </button>
          <a href="index.php?page=contenido"
             class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg ring-1 ring-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v4h4M9 13h6M9 17h4"/></svg>
            Generar contenido
          </a>
          <a href="index.php?page=leads"
             class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg ring-1 ring-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h6v14H4zM14 5h6v8h-6z"/></svg>
            Ver pipeline
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
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label>
          <input type="text" id="nl_first_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Nombre">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Apellido</label>
          <input type="text" id="nl_last_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Apellido">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Empresa</label>
          <input type="text" id="nl_company" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Empresa S.A.">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Cargo</label>
          <input type="text" id="nl_role" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Director de RH">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Industria</label>
          <input type="text" id="nl_industry" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Manufactura">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Ciudad</label>
          <input type="text" id="nl_city" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Ciudad de México">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
          <input type="email" id="nl_email" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="correo@empresa.com">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">WhatsApp</label>
          <input type="tel" id="nl_whatsapp" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="+52 55 1234 5678">
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">LinkedIn URL</label>
          <input type="url" id="nl_linkedin_url" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="https://linkedin.com/in/...">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Etapa</label>
          <select id="nl_stage" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="prospecto">Prospecto</option>
            <option value="contactado">Contactado</option>
            <option value="interesado">Interesado</option>
            <option value="propuesta">Propuesta</option>
            <option value="negociacion">Negociación</option>
            <option value="ganado">Ganado</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Fuente</label>
          <select id="nl_source" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="manual">Manual</option>
            <option value="linkedin">LinkedIn</option>
            <option value="referido">Referido</option>
            <option value="evento">Evento</option>
            <option value="web">Web</option>
          </select>
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">Notas</label>
          <textarea id="nl_notes" rows="3" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none" placeholder="Contexto, notas iniciales…"></textarea>
        </div>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeNewLeadModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="saveNewLeadBtn" onclick="saveNewLead()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar lead</button>
    </footer>
  </div>
</div>

<script>
// Cargar datos del dashboard
(async function() {
  // Stats
  var r = await api('api/agent.php', { action: 'get_stats' });
  if (r && r.ok) {
    document.querySelectorAll('.stat-value').forEach(function(el) {
      var key = el.dataset.key;
      el.textContent = (r[key] !== undefined) ? r[key] : '—';
    });
  }

  // Leads recientes
  var lr = await api('api/leads.php', { action: 'list_leads', limit: 5, offset: 0 });
  var tbody = document.getElementById('recentLeadsBody');
  if (lr && lr.ok && lr.leads) {
    if (lr.leads.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-6 text-center text-sm text-slate-400">No hay leads todavía. Crea el primero.</td></tr>';
    } else {
      tbody.innerHTML = lr.leads.map(function(l) {
        var badgeClass = stageBadge(l.stage);
        var name = escapeHtml((l.first_name || '') + ' ' + (l.last_name || '')).trim();
        return '<tr class="border-b border-slate-50 hover:bg-slate-50/50 transition">'
          + '<td class="px-5 py-3 font-medium text-slate-900">' + name + '</td>'
          + '<td class="px-5 py-3 text-slate-600">' + escapeHtml(l.company || '—') + '</td>'
          + '<td class="px-5 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + badgeClass + '">' + escapeHtml(stageLabel(l.stage)) + '</span></td>'
          + '<td class="px-5 py-3 text-slate-600">' + (l.score || 0) + '</td>'
          + '<td class="px-5 py-3"><a href="index.php?page=lead&id=' + l.id + '" class="text-xs text-indigo-600 font-medium hover:underline">Ver</a></td>'
          + '</tr>';
      }).join('');
    }
  } else {
    tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-6 text-center text-sm text-slate-400">Error al cargar leads.</td></tr>';
  }

  // Tareas pendientes
  var tr = await api('api/agent.php', { action: 'list_tasks', status: 'pendiente', limit: 5 });
  var list = document.getElementById('pendingTasksList');
  if (tr && tr.ok && tr.tasks) {
    if (tr.tasks.length === 0) {
      list.innerHTML = '<li class="px-4 py-4 text-center text-sm text-slate-400">Sin tareas pendientes.</li>';
    } else {
      list.innerHTML = tr.tasks.map(function(t) {
        var typeLabel = { follow_up:'Seguimiento', content_gen:'Contenido', outreach:'Outreach', lead_research:'Investigación', daily_digest:'Digest' };
        return '<li class="px-4 py-3 flex items-center justify-between gap-2">'
          + '<div>'
          + '<span class="text-xs font-medium text-slate-700">' + escapeHtml(typeLabel[t.type] || t.type) + '</span>'
          + (t.lead_name ? '<span class="text-xs text-slate-400 ml-2">' + escapeHtml(t.lead_name) + '</span>' : '')
          + '</div>'
          + '<span class="text-[11px] text-slate-400">' + (t.scheduled_at ? t.scheduled_at.substring(0,10) : '') + '</span>'
          + '</li>';
      }).join('');
    }
  } else {
    list.innerHTML = '<li class="px-4 py-4 text-center text-sm text-slate-400">Sin tareas pendientes.</li>';
  }
})();

// Stage helpers
function stageLabel(s) {
  var labels = { prospecto:'Prospecto', contactado:'Contactado', interesado:'Interesado', propuesta:'Propuesta', negociacion:'Negociación', ganado:'Ganado', perdido:'Perdido', pausado:'Pausado' };
  return labels[s] || s;
}
function stageBadge(s) {
  var badges = {
    prospecto:  'bg-slate-100 text-slate-600 ring-slate-200',
    contactado: 'bg-sky-50 text-sky-700 ring-sky-200',
    interesado: 'bg-violet-50 text-violet-700 ring-violet-200',
    propuesta:  'bg-amber-50 text-amber-700 ring-amber-200',
    negociacion:'bg-orange-50 text-orange-700 ring-orange-200',
    ganado:     'bg-emerald-50 text-emerald-700 ring-emerald-200',
    perdido:    'bg-red-50 text-red-600 ring-red-200',
    pausado:    'bg-slate-100 text-slate-400 ring-slate-200',
  };
  return badges[s] || 'bg-slate-100 text-slate-600 ring-slate-200';
}

// Modal nuevo lead
function openNewLeadModal() {
  document.getElementById('newLeadModal').classList.remove('hidden');
}
function closeNewLeadModal() {
  document.getElementById('newLeadModal').classList.add('hidden');
}
async function saveNewLead() {
  var btn = document.getElementById('saveNewLeadBtn');
  var restore = loading(btn, 'Guardando…');
  var data = {
    action: 'save_lead',
    first_name: document.getElementById('nl_first_name').value.trim(),
    last_name: document.getElementById('nl_last_name').value.trim(),
    company: document.getElementById('nl_company').value.trim(),
    role: document.getElementById('nl_role').value.trim(),
    industry: document.getElementById('nl_industry').value.trim(),
    city: document.getElementById('nl_city').value.trim(),
    email: document.getElementById('nl_email').value.trim(),
    whatsapp_phone: document.getElementById('nl_whatsapp').value.trim(),
    linkedin_url: document.getElementById('nl_linkedin_url').value.trim(),
    stage: document.getElementById('nl_stage').value,
    source: document.getElementById('nl_source').value,
    notes: document.getElementById('nl_notes').value.trim(),
  };
  if (!data.first_name) { restore(); toast('El nombre es obligatorio.', 'error'); return; }
  var r = await api('api/leads.php', data);
  restore();
  if (r && r.ok) {
    toast('Lead creado.', 'ok');
    closeNewLeadModal();
    window.location.reload();
  } else {
    toast(r.error || 'Error al guardar.', 'error');
  }
}
document.getElementById('newLeadModal').addEventListener('click', function(e) {
  if (e.target === this) closeNewLeadModal();
});
</script>
