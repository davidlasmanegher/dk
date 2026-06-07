<?php
/** Secuencias de outreach — editor visual de cadencias (sin JSON). */
?>
<div class="space-y-5">

  <div class="bg-indigo-50 ring-1 ring-indigo-200 rounded-xl px-5 py-4 text-sm text-indigo-900">
    <strong class="font-semibold">¿Qué es una secuencia?</strong> &mdash;
    Es la serie de toques que Daniel hace en el tiempo para un prospecto (correo, WhatsApp, LinkedIn).
    Definí cada paso: <strong>en qué día</strong>, <strong>por qué canal</strong> y <strong>con qué objetivo</strong>.
    Daniel redacta cada mensaje en su voz cuando llega el día; vos solo aprobás.
  </div>

  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex items-center">
    <span class="text-sm text-slate-500">Tus cadencias de seguimiento. Se usan en las campañas y al inscribir un lead.</span>
    <button onclick="openSeqModal(0)"
            class="ml-auto flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      Nueva secuencia
    </button>
  </div>

  <div id="seqGrid" class="grid gap-4 lg:grid-cols-2">
    <div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<!-- Modal editor -->
<div id="seqModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 id="seqModalTitle" class="text-base font-semibold text-slate-900">Nueva secuencia</h2>
      <button onclick="closeSeqModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
      <input type="hidden" id="s_id">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label>
        <input type="text" id="s_name" placeholder="Ej: Cadencia consultiva México"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Descripción (opcional)</label>
        <input type="text" id="s_desc" placeholder="Para qué sirve esta secuencia"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-slate-600">Pasos de la secuencia</label>
          <button onclick="addStepRow()" class="text-xs font-medium text-indigo-600 hover:underline flex items-center gap-1">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Agregar paso
          </button>
        </div>
        <div class="flex items-center gap-2 px-1 mb-1 text-[11px] font-medium text-slate-400 uppercase tracking-wide">
          <span class="w-16 shrink-0">Día</span>
          <span class="w-32 shrink-0">Canal</span>
          <span class="flex-1">Objetivo del mensaje</span>
          <span class="w-6 shrink-0"></span>
        </div>
        <div id="stepsList" class="space-y-2"></div>
        <p class="text-[11px] text-slate-400 mt-2">El "día" es cuántos días después del inicio se envía ese paso (0 = el mismo día).</p>
      </div>

      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" id="s_active" class="accent-indigo-600" checked> Secuencia activa (disponible para campañas e inscripción)
      </label>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeSeqModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="s_save" onclick="saveSequence()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
    </footer>
  </div>
</div>

<script>
var CHANNELS = [['email','Email'],['whatsapp','WhatsApp'],['linkedin','LinkedIn']];
var chanLabel = { email:'Email', whatsapp:'WhatsApp', linkedin:'LinkedIn' };
var chanColor = { email:'bg-sky-50 text-sky-700 ring-sky-200', whatsapp:'bg-emerald-50 text-emerald-700 ring-emerald-200', linkedin:'bg-indigo-50 text-indigo-700 ring-indigo-200' };

async function loadSequences() {
  var grid = document.getElementById('seqGrid');
  grid.innerHTML = '<div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/sequences.php', { action: 'list' });
  if (!r || !r.ok || !r.sequences || r.sequences.length === 0) {
    grid.innerHTML = '<div class="col-span-2 text-center py-12 text-sm text-slate-400">Sin secuencias todavía. Creá la primera.</div>';
    return;
  }
  grid.innerHTML = r.sequences.map(function(s) {
    var badge = s.active == 1 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200';
    var estado = s.active == 1 ? 'activa' : 'pausada';
    var steps = (s.steps || []).map(function(st) {
      var cc = chanColor[st.channel] || 'bg-slate-100 text-slate-600 ring-slate-200';
      return '<div class="flex items-center gap-2 text-sm">'
        + '<span class="shrink-0 w-12 text-xs text-slate-400">Día ' + (st.day|0) + '</span>'
        + '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ' + cc + '">' + (chanLabel[st.channel] || st.channel) + '</span>'
        + '<span class="text-slate-600 line-clamp-1">' + escapeHtml(st.goal || '') + '</span>'
        + '</div>';
    }).join('');
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">'
      + '<div class="flex items-start justify-between gap-2 mb-1">'
      +   '<div class="font-semibold text-slate-900">' + escapeHtml(s.name) + '</div>'
      +   '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + badge + '">' + estado + '</span>'
      + '</div>'
      + (s.description ? '<div class="text-xs text-slate-500 mb-3">' + escapeHtml(s.description) + '</div>' : '<div class="mb-3"></div>')
      + '<div class="space-y-1.5 mb-4">' + (steps || '<span class="text-xs text-slate-400">Sin pasos.</span>') + '</div>'
      + '<div class="flex items-center gap-2 border-t border-slate-100 pt-3">'
      +   '<button onclick="openSeqModal(' + s.id + ')" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">Editar</button>'
      +   '<button onclick="toggleSeq(' + s.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">' + (s.active == 1 ? 'Pausar' : 'Activar') + '</button>'
      +   '<button onclick="deleteSeq(' + s.id + ')" class="ml-auto text-xs text-red-600 hover:underline">Eliminar</button>'
      + '</div>'
      + '</div>';
  }).join('');
}

function stepRowHtml(day, channel, goal) {
  var opts = CHANNELS.map(function(c) {
    return '<option value="' + c[0] + '"' + (c[0] === channel ? ' selected' : '') + '>' + c[1] + '</option>';
  }).join('');
  var div = document.createElement('div');
  div.className = 'step-row flex items-center gap-2';
  div.innerHTML =
      '<input type="number" min="0" value="' + (parseInt(day) || 0) + '" class="step-day w-16 shrink-0 rounded-lg ring-1 ring-slate-300 px-2 py-2 text-sm focus:outline-none focus:ring-indigo-500">'
    + '<select class="step-channel w-32 shrink-0 rounded-lg ring-1 ring-slate-300 px-2 py-2 text-sm focus:outline-none focus:ring-indigo-500">' + opts + '</select>'
    + '<input type="text" value="' + (goal ? escapeHtml(goal).replace(/"/g, "&quot;") : "") + '" placeholder="Ej: Presentación con prueba social" class="step-goal flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">'
    + '<button type="button" onclick="this.parentNode.remove()" class="w-6 h-6 shrink-0 grid place-items-center rounded text-slate-400 hover:text-red-600 hover:bg-red-50">&times;</button>';
  return div;
}

function addStepRow(day, channel, goal) {
  document.getElementById('stepsList').appendChild(stepRowHtml(day || 0, channel || 'email', goal || ''));
}

async function openSeqModal(id) {
  document.getElementById('seqModalTitle').textContent = id ? 'Editar secuencia' : 'Nueva secuencia';
  var list = document.getElementById('stepsList');
  list.innerHTML = '';
  if (id) {
    var r = await api('api/sequences.php', { action: 'get', id: id });
    if (!r || !r.ok) { toast('No se pudo cargar.', 'error'); return; }
    var s = r.sequence;
    document.getElementById('s_id').value = s.id;
    document.getElementById('s_name').value = s.name || '';
    document.getElementById('s_desc').value = s.description || '';
    document.getElementById('s_active').checked = (s.active == 1);
    (s.steps || []).forEach(function(st) { addStepRow(st.day, st.channel, st.goal); });
    if (!(s.steps || []).length) addStepRow();
  } else {
    document.getElementById('s_id').value = '';
    document.getElementById('s_name').value = '';
    document.getElementById('s_desc').value = '';
    document.getElementById('s_active').checked = true;
    addStepRow(0, 'email', '');
  }
  document.getElementById('seqModal').classList.remove('hidden');
}
function closeSeqModal() { document.getElementById('seqModal').classList.add('hidden'); }

function collectSteps() {
  var steps = [];
  document.querySelectorAll('#stepsList .step-row').forEach(function(row) {
    var goal = row.querySelector('.step-goal').value.trim();
    if (!goal) return;
    steps.push({
      day: parseInt(row.querySelector('.step-day').value) || 0,
      channel: row.querySelector('.step-channel').value,
      goal: goal,
    });
  });
  return steps;
}

async function saveSequence() {
  var name = document.getElementById('s_name').value.trim();
  if (!name) { toast('Ponele un nombre.', 'error'); return; }
  var steps = collectSteps();
  if (!steps.length) { toast('Agregá al menos un paso con objetivo.', 'error'); return; }
  var btn = document.getElementById('s_save');
  var restore = loading(btn, 'Guardando…');
  var r = await api('api/sequences.php', {
    action: 'save',
    id: parseInt(document.getElementById('s_id').value) || 0,
    name: name,
    description: document.getElementById('s_desc').value.trim(),
    active: document.getElementById('s_active').checked,
    steps: steps,
  });
  restore();
  if (r && r.ok) { toast('Secuencia guardada.', 'ok'); closeSeqModal(); loadSequences(); }
  else toast((r && r.error) || 'Error al guardar.', 'error');
}

async function toggleSeq(id) {
  var r = await api('api/sequences.php', { action: 'toggle', id: id });
  if (r && r.ok) { toast('Secuencia ' + r.status + '.', 'ok'); loadSequences(); }
  else toast('Error.', 'error');
}

async function deleteSeq(id) {
  if (!confirm('¿Eliminar esta secuencia? Las campañas que la usaban quedarán sin cadencia.')) return;
  var r = await api('api/sequences.php', { action: 'delete', id: id });
  if (r && r.ok) { toast('Secuencia eliminada.', 'ok'); loadSequences(); }
  else toast('Error.', 'error');
}

document.getElementById('seqModal').addEventListener('click', function(e) { if (e.target === this) closeSeqModal(); });
loadSequences();
</script>
