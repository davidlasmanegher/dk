<?php
/** Campañas de prospección estratégica. */
?>
<div class="space-y-5">

  <!-- Encabezado -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex flex-wrap items-center gap-3">
    <div class="text-sm text-slate-500">
      Daniel ejecuta una estrategia: elige los mejores leads de cada foco, prepara el primer contacto y te lo deja para aprobar.
    </div>
    <button onclick="classifySectors()" id="classifyBtn"
            class="ml-auto flex items-center gap-1.5 px-4 py-2 rounded-lg ring-1 ring-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18l-7 8v6l-4 2v-8z"/></svg>
      Clasificar sectores
    </button>
    <button onclick="openCampModal(0)"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      Nueva campaña
    </button>
  </div>

  <!-- Lista -->
  <div id="campGrid" class="grid gap-4 lg:grid-cols-2">
    <div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<!-- Modal nueva/editar campaña -->
<div id="campModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 id="campModalTitle" class="text-base font-semibold text-slate-900">Nueva campaña</h2>
      <button onclick="closeCampModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
      <input type="hidden" id="c_id">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nombre de la campaña *</label>
        <input type="text" id="c_name" placeholder="Ej: Farma México Q3"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Sector / foco</label>
          <input type="text" id="c_sector" placeholder="farma" onchange="previewPool()" onkeyup="debouncePreview()"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <p class="text-[11px] text-slate-400 mt-1">Busca en industria y nombre de empresa.</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Región (opcional)</label>
          <input type="text" id="c_region" placeholder="México, CDMX, Monterrey…" onchange="previewPool()" onkeyup="debouncePreview()"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Segmentos objetivo (vacío = todos)</label>
        <div class="flex flex-wrap gap-2" id="c_segments">
          <?php foreach (['A','B','C','D','E'] as $s): ?>
            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-sm cursor-pointer hover:bg-slate-50">
              <input type="checkbox" value="<?= $s ?>" class="c_seg accent-indigo-600" onchange="previewPool()"> <?= $s ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Objetivo del primer contacto</label>
        <textarea id="c_objective" rows="2" placeholder="Abrir conversación sobre cómo aportar al modelo de aprendizaje del sector."
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"></textarea>
      </div>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Cupo diario</label>
          <input type="number" id="c_quota" min="1" max="100" value="10"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Canal</label>
          <select id="c_channel" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="auto">Automático</option>
            <option value="email">Email</option>
            <option value="whatsapp">WhatsApp</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Cadencia de seguimiento</label>
          <select id="c_sequence" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="0">Sin cadencia</option>
          </select>
        </div>
      </div>
      <div id="c_pool" class="rounded-lg bg-indigo-50 ring-1 ring-indigo-100 px-4 py-2.5 text-sm text-indigo-800 hidden"></div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeCampModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="c_save" onclick="saveCampaign()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
    </footer>
  </div>
</div>

<script>
var campSequences = [];

async function loadSequences() {
  var r = await api('api/campaigns.php', { action: 'sequences' });
  if (r && r.ok) campSequences = r.sequences || [];
}

async function loadCampaigns() {
  var grid = document.getElementById('campGrid');
  grid.innerHTML = '<div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/campaigns.php', { action: 'list' });
  if (!r || !r.ok || !r.campaigns || r.campaigns.length === 0) {
    grid.innerHTML = '<div class="col-span-2 text-center py-12 text-sm text-slate-400">Sin campañas todavía. Creá la primera y Daniel empieza a prospectar ese foco.</div>';
    return;
  }
  var stColors = { activa:'bg-emerald-50 text-emerald-700 ring-emerald-200', pausada:'bg-amber-50 text-amber-700 ring-amber-200', finalizada:'bg-slate-100 text-slate-500 ring-slate-200' };
  grid.innerHTML = r.campaigns.map(function(c) {
    var sc = stColors[c.status] || 'bg-slate-100 text-slate-600 ring-slate-200';
    var foco = [c.sector, c.segments ? 'Seg ' + c.segments : '', c.region].filter(Boolean).map(escapeHtml).join(' · ') || 'Todos los prospectos';
    var last = c.last_run_at ? ('Último barrido: ' + String(c.last_run_at).substring(0,16)) : 'Aún no corrió';
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">'
      + '<div class="flex items-start justify-between gap-2 mb-1">'
      +   '<div class="font-semibold text-slate-900">' + escapeHtml(c.name) + '</div>'
      +   '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + sc + '">' + escapeHtml(c.status) + '</span>'
      + '</div>'
      + '<div class="text-xs text-indigo-600 font-medium mb-3">' + foco + ' · ' + (c.daily_quota|0) + '/día</div>'
      + '<div class="grid grid-cols-4 gap-2 text-center mb-3">'
      +   metric(c.pool|0, 'en pool') + metric(c.total_enrolled|0, 'tocados') + metric(c.total_contacted|0, 'contactados') + metric(c.pending_approval|0, 'por aprobar')
      + '</div>'
      + '<div class="text-[11px] text-slate-400 mb-3">' + escapeHtml(last) + '</div>'
      + '<div class="flex flex-wrap items-center gap-2">'
      +   '<button onclick="runCampaign(' + c.id + ')" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">Correr ahora</button>'
      +   '<button onclick="toggleCampaign(' + c.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">' + (c.status === 'activa' ? 'Pausar' : 'Activar') + '</button>'
      +   '<button onclick="openCampModal(' + c.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</button>'
      +   '<button onclick="deleteCampaign(' + c.id + ')" class="ml-auto text-xs text-red-600 hover:underline">Eliminar</button>'
      + '</div>'
      + '</div>';
  }).join('');
}

function metric(val, label) {
  return '<div><div class="text-lg font-semibold text-slate-900">' + val + '</div><div class="text-[10px] text-slate-400 uppercase tracking-wide">' + label + '</div></div>';
}

function fillSequenceSelect(selected) {
  var sel = document.getElementById('c_sequence');
  sel.innerHTML = '<option value="0">Sin cadencia</option>';
  campSequences.forEach(function(s) {
    var o = document.createElement('option');
    o.value = s.id; o.textContent = s.name + ' (' + s.step_count + ' pasos)';
    if (String(s.id) === String(selected)) o.selected = true;
    sel.appendChild(o);
  });
}

async function openCampModal(id) {
  document.getElementById('campModalTitle').textContent = id ? 'Editar campaña' : 'Nueva campaña';
  document.getElementById('c_pool').classList.add('hidden');
  document.querySelectorAll('.c_seg').forEach(function(x){ x.checked = false; });
  if (id) {
    var r = await api('api/campaigns.php', { action: 'get', id: id });
    if (!r || !r.ok) { toast('No se pudo cargar.', 'error'); return; }
    var c = r.campaign;
    document.getElementById('c_id').value = c.id;
    document.getElementById('c_name').value = c.name || '';
    document.getElementById('c_sector').value = c.sector || '';
    document.getElementById('c_region').value = c.region || '';
    document.getElementById('c_objective').value = c.objective || '';
    document.getElementById('c_quota').value = c.daily_quota || 10;
    document.getElementById('c_channel').value = c.channel || 'auto';
    (c.segments || '').split(',').forEach(function(s){
      var box = document.querySelector('.c_seg[value="' + s.trim() + '"]'); if (box) box.checked = true;
    });
    fillSequenceSelect(c.sequence_id || 1);
  } else {
    document.getElementById('c_id').value = '';
    document.getElementById('c_name').value = '';
    document.getElementById('c_sector').value = '';
    document.getElementById('c_region').value = '';
    document.getElementById('c_objective').value = '';
    document.getElementById('c_quota').value = 10;
    document.getElementById('c_channel').value = 'auto';
    fillSequenceSelect(1);
  }
  document.getElementById('campModal').classList.remove('hidden');
  previewPool();
}
function closeCampModal() { document.getElementById('campModal').classList.add('hidden'); }

function readSegments() {
  return Array.from(document.querySelectorAll('.c_seg:checked')).map(function(x){ return x.value; }).join(',');
}

var previewTimer = null;
function debouncePreview() { clearTimeout(previewTimer); previewTimer = setTimeout(previewPool, 400); }

async function previewPool() {
  var box = document.getElementById('c_pool');
  var r = await api('api/campaigns.php', {
    action: 'preview',
    sector: document.getElementById('c_sector').value.trim(),
    segments: readSegments(),
    region: document.getElementById('c_region').value.trim(),
  });
  if (r && r.ok) {
    box.textContent = r.pool + ' leads (prospectos) coinciden con este foco y están disponibles.';
    box.classList.remove('hidden');
  }
}

async function saveCampaign() {
  var name = document.getElementById('c_name').value.trim();
  if (!name) { toast('Ponele un nombre.', 'error'); return; }
  var btn = document.getElementById('c_save');
  var restore = loading(btn, 'Guardando…');
  var r = await api('api/campaigns.php', {
    action: 'save',
    id: parseInt(document.getElementById('c_id').value) || 0,
    name: name,
    sector: document.getElementById('c_sector').value.trim(),
    segments: readSegments(),
    region: document.getElementById('c_region').value.trim(),
    objective: document.getElementById('c_objective').value.trim(),
    daily_quota: parseInt(document.getElementById('c_quota').value) || 10,
    channel: document.getElementById('c_channel').value,
    sequence_id: parseInt(document.getElementById('c_sequence').value) || 0,
  });
  restore();
  if (r && r.ok) { toast('Campaña guardada.', 'ok'); closeCampModal(); loadCampaigns(); }
  else toast((r && r.error) || 'Error al guardar.', 'error');
}

async function runCampaign(id) {
  if (!confirm('Daniel va a seleccionar los leads del día de esta campaña y preparar el primer contacto para que vos apruebes. ¿Seguimos?')) return;
  toast('Daniel está seleccionando y redactando…', 'ok');
  var r = await api('api/campaigns.php', { action: 'run', id: id });
  if (r && r.ok) {
    toast(r.prepared + ' borradores listos para aprobar en la Bandeja del Agente.', 'ok');
    loadCampaigns();
  } else {
    toast((r && r.error) || 'No se pudo correr la campaña.', 'error');
  }
}

async function toggleCampaign(id) {
  var r = await api('api/campaigns.php', { action: 'toggle', id: id });
  if (r && r.ok) { toast('Campaña ' + r.status + '.', 'ok'); loadCampaigns(); }
  else toast('Error.', 'error');
}

async function deleteCampaign(id) {
  if (!confirm('¿Eliminar esta campaña? Los leads ya contactados no se borran, solo la campaña.')) return;
  var r = await api('api/campaigns.php', { action: 'delete', id: id });
  if (r && r.ok) { toast('Campaña eliminada.', 'ok'); loadCampaigns(); }
  else toast('Error.', 'error');
}

async function classifySectors() {
  if (!confirm('Daniel va a inferir el sector de cada empresa de tu base con IA (Sanofi → farma, CEMEX → construcción…). Puede tardar un par de minutos. ¿Empezamos?')) return;
  var btn = document.getElementById('classifyBtn');
  btn.disabled = true;
  var st = await api('api/campaigns.php', { action: 'classify_status' });
  var total = (st && st.ok) ? st.total : 0;
  var prev = -1, guard = 0;
  while (guard++ < 300) {
    var r = await api('api/campaigns.php', { action: 'classify' });
    if (!r || !r.ok) { toast((r && r.error) || 'Error al clasificar.', 'error'); break; }
    var done = total - r.remaining;
    btn.textContent = 'Clasificando… ' + done + '/' + total;
    if (r.remaining <= 0) { toast('Base clasificada. Ya podés crear campañas por sector.', 'ok'); break; }
    if (r.remaining === prev) { toast('Clasificación parcial (' + done + '/' + total + '). Reintentá para completar.', 'ok'); break; }
    prev = r.remaining;
  }
  btn.disabled = false;
  btn.textContent = 'Clasificar sectores';
  loadCampaigns();
}

document.getElementById('campModal').addEventListener('click', function(e) { if (e.target === this) closeCampModal(); });

(async function() { await loadSequences(); loadCampaigns(); })();
</script>
