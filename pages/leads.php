<?php
/** Pipeline de leads con vista kanban / lista */
?>
<div class="space-y-4">

  <!-- Filtros + vista -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex flex-wrap items-center gap-3">
    <input type="text" id="filterSearch" placeholder="Buscar nombre, empresa…"
           class="flex-1 min-w-40 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" oninput="loadLeads()">
    <select id="filterStage" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" onchange="loadLeads()">
      <option value="">Todas las etapas</option>
      <option value="prospecto">Prospecto</option>
      <option value="contactado">Contactado</option>
      <option value="interesado">Interesado</option>
      <option value="propuesta">Propuesta</option>
      <option value="negociacion">Negociación</option>
      <option value="ganado">Ganado</option>
      <option value="perdido">Perdido</option>
      <option value="pausado">Pausado</option>
    </select>
    <select id="filterIndustry" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" onchange="loadLeads()">
      <option value="">Todas las industrias</option>
      <option value="Manufactura">Manufactura</option>
      <option value="Retail">Retail</option>
      <option value="Servicios Financieros">Servicios Financieros</option>
      <option value="Tecnología">Tecnología</option>
      <option value="Salud">Salud</option>
      <option value="Educación">Educación</option>
      <option value="Logística">Logística</option>
      <option value="Energía">Energía</option>
      <option value="Otro">Otro</option>
    </select>
    <select id="filterSegment" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" onchange="currentPage=0;loadLeads()">
      <option value="">Todos los segmentos</option>
      <option value="A">A · Estratégico</option>
      <option value="B">B · RH/Talento</option>
      <option value="C">C · Capacitación</option>
      <option value="D">D · Alta dirección</option>
      <option value="E">E · Nutrición</option>
    </select>
    <select id="filterSort" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" onchange="loadLeads()">
      <option value="score">Prioridad (score)</option>
      <option value="recent">Más recientes</option>
    </select>

    <!-- Toggle vista -->
    <div class="ml-auto flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg">
      <button id="viewList" onclick="setView('list')"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition bg-white shadow-sm text-slate-900">
        Lista
      </button>
      <button id="viewKanban" onclick="setView('kanban')"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition text-slate-500 hover:text-slate-700">
        Kanban
      </button>
    </div>

    <button onclick="openBaseReport()"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg ring-1 ring-indigo-300 text-indigo-700 text-sm font-medium hover:bg-indigo-50 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Reporte IA
    </button>
    <button onclick="openLeadModal()"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      Nuevo lead
    </button>
  </div>

  <!-- Vista lista -->
  <div id="listView" class="bg-white rounded-xl ring-1 ring-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-xs text-slate-500 uppercase tracking-wide border-b border-slate-100">
          <th class="px-5 py-3 text-left font-medium">Nombre</th>
          <th class="px-5 py-3 text-left font-medium">Empresa</th>
          <th class="px-5 py-3 text-left font-medium">Cargo</th>
          <th class="px-5 py-3 text-left font-medium">Segmento</th>
          <th class="px-5 py-3 text-left font-medium">Etapa</th>
          <th class="px-5 py-3 text-left font-medium">Score</th>
          <th class="px-5 py-3 text-left font-medium">Prox. acción</th>
          <th class="px-5 py-3 text-left font-medium"></th>
        </tr>
      </thead>
      <tbody id="leadsTableBody">
        <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-400">Cargando…</td></tr>
      </tbody>
    </table>
    <div id="paginationBar" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"></div>
  </div>

  <!-- Vista kanban -->
  <div id="kanbanView" class="hidden">
    <div class="overflow-x-auto pb-3">
      <div class="inline-flex gap-4 min-w-max px-1">
        <?php
        $stages = [
          ['prospecto',   'Prospecto',    'bg-slate-100'],
          ['contactado',  'Contactado',   'bg-sky-50'],
          ['interesado',  'Interesado',   'bg-violet-50'],
          ['propuesta',   'Propuesta',    'bg-amber-50'],
          ['negociacion', 'Negociación',  'bg-orange-50'],
          ['ganado',      'Ganado',       'bg-emerald-50'],
        ];
        foreach ($stages as [$slug, $label, $bg]): ?>
          <div class="w-64">
            <div class="text-xs font-semibold text-slate-600 mb-2 px-1"><?= e($label) ?>
              <span id="kanban_count_<?= $slug ?>" class="ml-1 text-slate-400"></span>
            </div>
            <div id="kanban_col_<?= $slug ?>" class="space-y-2 min-h-24 rounded-xl p-2 <?= $bg ?> ring-1 ring-black/5">
              <div class="skeleton h-20 rounded-lg">&nbsp;</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- Modal lead -->
<div id="leadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900" id="leadModalTitle">Nuevo lead</h2>
      <button onclick="closeLeadModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1">
      <input type="hidden" id="lm_id" value="">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label>
          <input type="text" id="lm_first_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Apellido</label>
          <input type="text" id="lm_last_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Empresa</label>
          <input type="text" id="lm_company" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Cargo</label>
          <input type="text" id="lm_role" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Industria</label>
          <input type="text" id="lm_industry" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Tamaño empresa</label>
          <select id="lm_company_size" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="">— sin especificar —</option>
            <option value="1-50">1-50 empleados</option>
            <option value="51-200">51-200 empleados</option>
            <option value="201-500">201-500 empleados</option>
            <option value="501-1000">501-1000 empleados</option>
            <option value="1000+">Más de 1000</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Ciudad</label>
          <input type="text" id="lm_city" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">País</label>
          <input type="text" id="lm_country" value="México" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
          <input type="email" id="lm_email" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Teléfono</label>
          <input type="tel" id="lm_phone" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">WhatsApp</label>
          <input type="tel" id="lm_whatsapp_phone" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Etapa</label>
          <select id="lm_stage" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="prospecto">Prospecto</option>
            <option value="contactado">Contactado</option>
            <option value="interesado">Interesado</option>
            <option value="propuesta">Propuesta</option>
            <option value="negociacion">Negociación</option>
            <option value="ganado">Ganado</option>
            <option value="perdido">Perdido</option>
            <option value="pausado">Pausado</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Fuente</label>
          <select id="lm_source" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="manual">Manual</option>
            <option value="linkedin">LinkedIn</option>
            <option value="referido">Referido</option>
            <option value="evento">Evento</option>
            <option value="web">Web</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Score (0-100)</label>
          <input type="number" id="lm_score" min="0" max="100" value="0" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Segmento</label>
          <select id="lm_segment" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="">—</option>
            <option value="A">A · Estratégico</option>
            <option value="B">B · RH/Talento</option>
            <option value="C">C · Capacitación</option>
            <option value="D">D · Alta dirección</option>
            <option value="E">E · Nutrición</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Región / Estado</label>
          <input type="text" id="lm_region" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">LinkedIn URL</label>
          <input type="url" id="lm_linkedin_url" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="https://linkedin.com/in/...">
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">Proxima acción</label>
          <input type="text" id="lm_next_action" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Enviar propuesta">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Fecha proxima acción</label>
          <input type="date" id="lm_next_action_date" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">Notas</label>
          <textarea id="lm_notes" rows="3" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"></textarea>
        </div>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-between">
      <button id="lm_delete_btn" onclick="deleteLead()" class="hidden px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 ring-1 ring-red-200 transition">Eliminar</button>
      <div class="flex items-center gap-2.5 ml-auto">
        <button onclick="closeLeadModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
        <button id="saveLeadBtn" onclick="saveLead()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
      </div>
    </footer>
  </div>
</div>

<!-- Modal reporte ejecutivo IA -->
<div id="reportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900">Reporte ejecutivo de la base</h2>
      <button onclick="document.getElementById('reportModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div id="reportBody" class="px-6 py-5 overflow-y-auto flex-1 text-sm text-slate-700 leading-relaxed">…</div>
  </div>
</div>

<script>
var currentView = 'list';
var currentPage = 0;
var pageSize    = 20;

function setView(v) {
  currentView = v;
  document.getElementById('listView').classList.toggle('hidden', v !== 'list');
  document.getElementById('kanbanView').classList.toggle('hidden', v !== 'kanban');
  document.getElementById('viewList').className   = v === 'list'   ? 'px-3 py-1.5 rounded-md text-xs font-medium transition bg-white shadow-sm text-slate-900' : 'px-3 py-1.5 rounded-md text-xs font-medium transition text-slate-500 hover:text-slate-700';
  document.getElementById('viewKanban').className = v === 'kanban' ? 'px-3 py-1.5 rounded-md text-xs font-medium transition bg-white shadow-sm text-slate-900' : 'px-3 py-1.5 rounded-md text-xs font-medium transition text-slate-500 hover:text-slate-700';
  loadLeads();
}

async function loadLeads() {
  var search   = document.getElementById('filterSearch').value;
  var stage    = document.getElementById('filterStage').value;
  var industry = document.getElementById('filterIndustry').value;
  var segment  = document.getElementById('filterSegment').value;
  var sort     = document.getElementById('filterSort').value;

  if (currentView === 'list') {
    var tbody = document.getElementById('leadsTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-400">Cargando…</td></tr>';
    var r = await api('api/leads.php', { action: 'list_leads', search, stage, industry, segment, sort, limit: pageSize, offset: currentPage * pageSize });
    if (!r || !r.ok) { tbody.innerHTML = '<tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-400">Error al cargar leads.</td></tr>'; return; }
    if (!r.leads || r.leads.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-400">No hay leads con esos filtros.</td></tr>';
    } else {
      tbody.innerHTML = r.leads.map(function(l) {
        var name = escapeHtml((l.first_name || '') + ' ' + (l.last_name || '')).trim();
        var badge = stageBadge(l.stage);
        var nextDate = l.next_action_date ? '<span class="text-xs text-slate-400 block">' + l.next_action_date.substring(0,10) + '</span>' : '';
        return '<tr class="border-b border-slate-50 hover:bg-slate-50/50 transition cursor-pointer" onclick="openLeadModal(' + l.id + ')">'
          + '<td class="px-5 py-3 font-medium text-slate-900">' + name + '</td>'
          + '<td class="px-5 py-3 text-slate-600">' + escapeHtml(l.company || '—') + '</td>'
          + '<td class="px-5 py-3 text-slate-500 text-xs">' + escapeHtml(l.role || '—') + '</td>'
          + '<td class="px-5 py-3">' + segmentBadge(l.segment) + '</td>'
          + '<td class="px-5 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + badge + '">' + escapeHtml(stageLabel(l.stage)) + '</span></td>'
          + '<td class="px-5 py-3 text-slate-700 font-medium">' + (l.score || 0) + '</td>'
          + '<td class="px-5 py-3 text-slate-600 text-xs line-clamp-1">' + escapeHtml(l.next_action || '—') + nextDate + '</td>'
          + '<td class="px-5 py-3"><a href="index.php?page=lead&id=' + l.id + '" onclick="event.stopPropagation()" class="text-xs text-indigo-600 hover:underline">Detalle</a></td>'
          + '</tr>';
      }).join('');
    }
    var pag = document.getElementById('paginationBar');
    var total = r.total || 0;
    var from  = currentPage * pageSize + 1;
    var to    = Math.min(from + pageSize - 1, total);
    pag.innerHTML = '<span>' + (total > 0 ? from + '–' + to + ' de ' + total : '0') + ' leads</span>'
      + '<div class="flex gap-2">'
      + (currentPage > 0 ? '<button onclick="currentPage--;loadLeads()" class="px-2 py-1 rounded ring-1 ring-slate-200 text-slate-600 hover:bg-slate-50">Anterior</button>' : '')
      + (to < total ? '<button onclick="currentPage++;loadLeads()" class="px-2 py-1 rounded ring-1 ring-slate-200 text-slate-600 hover:bg-slate-50">Siguiente</button>' : '')
      + '</div>';
  } else {
    // Kanban
    var stages = ['prospecto','contactado','interesado','propuesta','negociacion','ganado'];
    for (var s = 0; s < stages.length; s++) {
      (function(st) {
        api('api/leads.php', { action: 'list_leads', stage: st, search: search, industry: industry, limit: 50, offset: 0 }).then(function(r) {
          var col = document.getElementById('kanban_col_' + st);
          var cnt = document.getElementById('kanban_count_' + st);
          if (!r || !r.ok || !r.leads) { col.innerHTML = '<div class="text-xs text-slate-400 p-2">Error</div>'; return; }
          cnt.textContent = '(' + r.leads.length + ')';
          if (r.leads.length === 0) { col.innerHTML = '<div class="text-xs text-slate-400 text-center py-3">Sin leads</div>'; return; }
          col.innerHTML = r.leads.map(function(l) {
            var name = escapeHtml((l.first_name || '') + ' ' + (l.last_name || '')).trim();
            return '<div class="bg-white rounded-lg ring-1 ring-slate-200 p-3 cursor-pointer hover:shadow-sm transition" onclick="openLeadModal(' + l.id + ')">'
              + '<div class="font-medium text-sm text-slate-900 line-clamp-1">' + name + '</div>'
              + '<div class="text-xs text-slate-500 mt-0.5">' + escapeHtml(l.company || '') + '</div>'
              + (l.next_action ? '<div class="text-[11px] text-slate-400 mt-1.5 line-clamp-1">' + escapeHtml(l.next_action) + '</div>' : '')
              + '</div>';
          }).join('');
        });
      })(stages[s]);
    }
  }
}

// Modal lead
async function openLeadModal(id) {
  document.getElementById('leadModalTitle').textContent = id ? 'Editar lead' : 'Nuevo lead';
  document.getElementById('lm_id').value = id || '';
  document.getElementById('lm_delete_btn').classList.toggle('hidden', !id);
  var fields = ['first_name','last_name','company','role','industry','company_size','city','country','email','phone','whatsapp_phone','stage','source','score','segment','region','linkedin_url','next_action','next_action_date','notes'];
  if (id) {
    var r = await api('api/leads.php', { action: 'get_lead', id: id });
    if (r && r.ok && r.lead) {
      fields.forEach(function(f) {
        var el = document.getElementById('lm_' + f);
        if (el) el.value = r.lead[f] || '';
      });
    }
  } else {
    fields.forEach(function(f) {
      var el = document.getElementById('lm_' + f);
      if (el) el.value = f === 'country' ? 'México' : (f === 'stage' ? 'prospecto' : (f === 'source' ? 'manual' : (f === 'score' ? '0' : '')));
    });
  }
  document.getElementById('leadModal').classList.remove('hidden');
}
function closeLeadModal() {
  document.getElementById('leadModal').classList.add('hidden');
}
async function saveLead() {
  var btn = document.getElementById('saveLeadBtn');
  var restore = loading(btn, 'Guardando…');
  var id = document.getElementById('lm_id').value;
  var data = { action: 'save_lead' };
  if (id) data.id = parseInt(id);
  var fields = ['first_name','last_name','company','role','industry','company_size','city','country','email','phone','whatsapp_phone','stage','source','score','segment','region','linkedin_url','next_action','next_action_date','notes'];
  fields.forEach(function(f) {
    var el = document.getElementById('lm_' + f);
    if (el) data[f] = el.value;
  });
  if (!data.first_name) { restore(); toast('El nombre es obligatorio.', 'error'); return; }
  var r = await api('api/leads.php', data);
  restore();
  if (r && r.ok) { toast(id ? 'Lead actualizado.' : 'Lead creado.', 'ok'); closeLeadModal(); loadLeads(); }
  else toast(r.error || 'Error al guardar.', 'error');
}
async function deleteLead() {
  var id = document.getElementById('lm_id').value;
  if (!id || !confirm('¿Eliminar este lead y todas sus actividades?')) return;
  var r = await api('api/leads.php', { action: 'delete_lead', id: parseInt(id) });
  if (r && r.ok) { toast('Lead eliminado.', 'ok'); closeLeadModal(); loadLeads(); }
  else toast(r.error || 'Error al eliminar.', 'error');
}
document.getElementById('leadModal').addEventListener('click', function(e) { if (e.target === this) closeLeadModal(); });

function stageLabel(s) {
  var l = { prospecto:'Prospecto', contactado:'Contactado', interesado:'Interesado', propuesta:'Propuesta', negociacion:'Negociación', ganado:'Ganado', perdido:'Perdido', pausado:'Pausado' };
  return l[s] || s;
}
function stageBadge(s) {
  var b = { prospecto:'bg-slate-100 text-slate-600 ring-slate-200', contactado:'bg-sky-50 text-sky-700 ring-sky-200', interesado:'bg-violet-50 text-violet-700 ring-violet-200', propuesta:'bg-amber-50 text-amber-700 ring-amber-200', negociacion:'bg-orange-50 text-orange-700 ring-orange-200', ganado:'bg-emerald-50 text-emerald-700 ring-emerald-200', perdido:'bg-red-50 text-red-600 ring-red-200', pausado:'bg-slate-100 text-slate-400 ring-slate-200' };
  return b[s] || 'bg-slate-100 text-slate-600 ring-slate-200';
}
function segmentBadge(s) {
  if (!s) return '<span class="text-xs text-slate-300">—</span>';
  var m = { A:'bg-emerald-50 text-emerald-700 ring-emerald-200', B:'bg-sky-50 text-sky-700 ring-sky-200', C:'bg-violet-50 text-violet-700 ring-violet-200', D:'bg-amber-50 text-amber-700 ring-amber-200', E:'bg-slate-100 text-slate-500 ring-slate-200' };
  return '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold ring-1 ' + (m[s] || m.E) + '" title="Segmento ' + escapeHtml(s) + '">' + escapeHtml(s) + '</span>';
}

async function openBaseReport() {
  var m = document.getElementById('reportModal');
  var b = document.getElementById('reportBody');
  m.classList.remove('hidden');
  b.innerHTML = '<div class="text-center py-10 text-slate-400">Generando reporte con IA… (puede tardar unos segundos)</div>';
  var r = await api('api/leads.php', { action: 'base_report' });
  b.innerHTML = (r && r.ok) ? mdToHtml(r.report) : '<div class="text-red-600">' + escapeHtml(r.error || 'Error al generar el reporte.') + '</div>';
}
document.getElementById('reportModal').addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });

loadLeads();
</script>
