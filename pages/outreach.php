<?php
/** Outreach — secuencias y campañas multi-paso */
?>
<div class="space-y-5">

  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-4 flex items-center justify-between">
    <div>
      <h2 class="text-sm font-semibold text-slate-900">Secuencias de outreach</h2>
      <p class="text-xs text-slate-500 mt-0.5">Configura flujos multi-paso para prospectar en LinkedIn, WhatsApp o email.</p>
    </div>
    <button onclick="openSeqModal()"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      Nueva secuencia
    </button>
  </div>

  <div id="sequencesList" class="grid gap-4 lg:grid-cols-2">
    <div class="col-span-2 text-center py-10 text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<!-- Modal secuencia -->
<div id="seqModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900" id="seqModalTitle">Nueva secuencia</h2>
      <button onclick="closeSeqModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
      <input type="hidden" id="seq_id">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label>
        <input type="text" id="seq_name" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Ej: Prospección directores RRHH México">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Descripción</label>
        <textarea id="seq_description" rows="2" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Etapa del lead target</label>
          <select id="seq_target_stage" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="">Cualquiera</option>
            <option value="prospecto">Prospecto</option>
            <option value="contactado">Contactado</option>
            <option value="interesado">Interesado</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Industria target</label>
          <input type="text" id="seq_target_industry" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Manufactura">
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Pasos (JSON)</label>
        <p class="text-xs text-slate-400 mb-1.5">Array de pasos: [{day, type, subject_template, body_template}]</p>
        <textarea id="seq_steps_json" rows="6" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none font-mono text-xs"
                  placeholder='[{"day":0,"type":"linkedin","subject_template":"Conexión en LinkedIn","body_template":"Hola {{nombre}}, me gustaría conectar…"},{"day":3,"type":"email","subject_template":"Seguimiento","body_template":"Hola {{nombre}}, te escribo de parte de SISTEL…"}]'></textarea>
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="seq_active" class="accent-indigo-600" checked>
        <label for="seq_active" class="text-sm text-slate-700">Secuencia activa</label>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeSeqModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="saveSeqBtn" onclick="saveSequence()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
    </footer>
  </div>
</div>

<script>
async function loadSequences() {
  var r = await api('api/leads.php', { action: 'list_sequences' });
  var container = document.getElementById('sequencesList');
  if (!r || !r.ok || !r.sequences || r.sequences.length === 0) {
    container.innerHTML = '<div class="col-span-2 bg-white rounded-xl ring-1 ring-slate-200 py-12 text-center text-sm text-slate-400">Sin secuencias. Crea la primera.</div>';
    return;
  }
  container.innerHTML = r.sequences.map(function(s) {
    var steps = 0;
    try { steps = JSON.parse(s.steps_json || '[]').length; } catch(e) {}
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">'
      + '<div class="flex items-start justify-between gap-2">'
      + '<div>'
      + '<div class="font-medium text-slate-900">' + escapeHtml(s.name) + '</div>'
      + (s.description ? '<div class="text-xs text-slate-500 mt-0.5 line-clamp-2">' + escapeHtml(s.description) + '</div>' : '')
      + '</div>'
      + '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + (s.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-400 ring-slate-200') + '">' + (s.active ? 'Activa' : 'Inactiva') + '</span>'
      + '</div>'
      + '<div class="flex items-center gap-3 mt-3 text-xs text-slate-500">'
      + '<span>' + steps + ' pasos</span>'
      + (s.target_stage ? '<span>' + escapeHtml(s.target_stage) + '</span>' : '')
      + (s.target_industry ? '<span>' + escapeHtml(s.target_industry) + '</span>' : '')
      + '</div>'
      + '<div class="mt-3 flex gap-2">'
      + '<button onclick="editSequence(' + s.id + ')" class="text-xs text-indigo-600 hover:underline">Editar</button>'
      + '</div>'
      + '</div>';
  }).join('');
}

function openSeqModal(id) {
  document.getElementById('seqModalTitle').textContent = id ? 'Editar secuencia' : 'Nueva secuencia';
  document.getElementById('seq_id').value = id || '';
  if (!id) {
    ['seq_name','seq_description','seq_target_stage','seq_target_industry','seq_steps_json'].forEach(function(f) {
      var el = document.getElementById(f);
      if (el) el.value = '';
    });
    document.getElementById('seq_active').checked = true;
  }
  document.getElementById('seqModal').classList.remove('hidden');
}
function closeSeqModal() { document.getElementById('seqModal').classList.add('hidden'); }

async function editSequence(id) {
  var r = await api('api/leads.php', { action: 'get_sequence', id: id });
  if (!r || !r.ok || !r.sequence) { toast('Error al cargar.', 'error'); return; }
  var s = r.sequence;
  document.getElementById('seq_id').value             = s.id;
  document.getElementById('seq_name').value           = s.name || '';
  document.getElementById('seq_description').value    = s.description || '';
  document.getElementById('seq_target_stage').value   = s.target_stage || '';
  document.getElementById('seq_target_industry').value= s.target_industry || '';
  document.getElementById('seq_steps_json').value     = s.steps_json || '[]';
  document.getElementById('seq_active').checked       = !!s.active;
  document.getElementById('seqModalTitle').textContent = 'Editar secuencia';
  document.getElementById('seqModal').classList.remove('hidden');
}

async function saveSequence() {
  var btn = document.getElementById('saveSeqBtn');
  var restore = loading(btn, 'Guardando…');
  var id = document.getElementById('seq_id').value;
  var data = {
    action:           'save_sequence',
    name:             document.getElementById('seq_name').value.trim(),
    description:      document.getElementById('seq_description').value.trim(),
    target_stage:     document.getElementById('seq_target_stage').value,
    target_industry:  document.getElementById('seq_target_industry').value.trim(),
    steps_json:       document.getElementById('seq_steps_json').value.trim(),
    active:           document.getElementById('seq_active').checked ? 1 : 0,
  };
  if (id) data.id = parseInt(id);
  var r = await api('api/leads.php', data);
  restore();
  if (r && r.ok) { toast('Secuencia guardada.', 'ok'); closeSeqModal(); loadSequences(); }
  else toast(r.error || 'Error.', 'error');
}

document.getElementById('seqModal').addEventListener('click', function(e) { if (e.target === this) closeSeqModal(); });
loadSequences();
</script>
