<?php
/** Secuencias de outreach — constructor de flujos visual (estilo Botpress) con Drawflow. */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.css">
<script src="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.js"></script>
<style>
  #flowCanvas { width:100%; height:440px; border-radius:12px; border:1px solid #e2e8f0;
    background:#f8fafc; background-image:radial-gradient(#e2e8f0 1px, transparent 1px); background-size:20px 20px; }
  #flowCanvas .drawflow-node { background:transparent; border:0; padding:0; box-shadow:none; width:230px; }
  #flowCanvas .drawflow-node.selected .seq-card { box-shadow:0 0 0 2px #6366f1; }
  .seq-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); font-size:13px; }
  .seq-card .seq-head { padding:8px 10px; font-weight:600; color:#fff; display:flex; align-items:center; gap:6px; font-size:12px; }
  .seq-head.h-start { background:#0f172a; }
  .seq-head.h-step  { background:#6366f1; }
  .seq-body { padding:10px; display:flex; flex-direction:column; gap:7px; }
  .seq-body label { font-size:11px; color:#64748b; display:block; margin-bottom:2px; }
  .seq-body input, .seq-body select { width:100%; border:1px solid #cbd5e1; border-radius:7px; padding:5px 7px; font-size:12px; outline:none; background:#fff; }
  .seq-body input:focus, .seq-body select:focus { border-color:#6366f1; }
  .seq-row2 { display:flex; gap:7px; }
  #flowCanvas .drawflow .connection .main-path { stroke:#94a3b8; stroke-width:2px; }
</style>

<div class="space-y-5">

  <div class="bg-indigo-50 ring-1 ring-indigo-200 rounded-xl px-5 py-4 text-sm text-indigo-900">
    <strong class="font-semibold">Constructor de rutinas</strong> &mdash;
    Cada tarjeta es un toque (correo, WhatsApp, LinkedIn). El lazo arranca en <strong>Inicio</strong> y sigue las flechas.
    Agregá pasos, definí el día y el objetivo de cada uno. Daniel redacta el mensaje cuando llega el día; vos aprobás.
  </div>

  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex items-center">
    <span class="text-sm text-slate-500">Tus rutinas de seguimiento. Se usan en las campañas y al inscribir un lead.</span>
    <button onclick="openSeqModal(0)"
            class="ml-auto flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      Nueva rutina
    </button>
  </div>

  <div id="seqGrid" class="grid gap-4 lg:grid-cols-2">
    <div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<!-- Modal editor de flujo -->
<div id="seqModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-5xl max-h-[92vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 id="seqModalTitle" class="text-base font-semibold text-slate-900">Nueva rutina</h2>
      <button onclick="closeSeqModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
      <input type="hidden" id="s_id">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre *</label>
          <input type="text" id="s_name" placeholder="Ej: Cadencia consultiva México"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Descripción (opcional)</label>
          <input type="text" id="s_desc" placeholder="Para qué sirve"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button onclick="addStepFromButton()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Agregar paso
        </button>
        <button onclick="removeSelected()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-slate-600 text-xs font-medium hover:bg-slate-50">
          Quitar paso seleccionado
        </button>
        <span class="text-[11px] text-slate-400 ml-1">Arrastrá las tarjetas para acomodarlas. El orden lo dan las flechas.</span>
      </div>

      <div id="flowCanvas"></div>

      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" id="s_active" class="accent-indigo-600" checked> Rutina activa (disponible para campañas e inscripción)
      </label>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeSeqModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
      <button id="s_save" onclick="saveSequence()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
    </footer>
  </div>
</div>

<script>
var chanLabel = { email:'Email', whatsapp:'WhatsApp', linkedin:'LinkedIn' };
var chanColor = { email:'bg-sky-50 text-sky-700 ring-sky-200', whatsapp:'bg-emerald-50 text-emerald-700 ring-emerald-200', linkedin:'bg-indigo-50 text-indigo-700 ring-indigo-200' };
var editor = null, lastNodeId = null, posX = 40, posY = 60, selectedNodeId = null;

// ── Lista de rutinas ─────────────────────────────────────────────────────────
async function loadSequences() {
  var grid = document.getElementById('seqGrid');
  grid.innerHTML = '<div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/sequences.php', { action: 'list' });
  if (!r || !r.ok || !r.sequences || r.sequences.length === 0) {
    grid.innerHTML = '<div class="col-span-2 text-center py-12 text-sm text-slate-400">Sin rutinas todavía. Creá la primera.</div>';
    return;
  }
  grid.innerHTML = r.sequences.map(function(s) {
    var badge = s.active == 1 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200';
    var estado = s.active == 1 ? 'activa' : 'pausada';
    var steps = (s.steps || []).map(function(st, i) {
      var cc = chanColor[st.channel] || 'bg-slate-100 text-slate-600 ring-slate-200';
      var arrow = i > 0 ? '<span class="text-slate-300">→</span>' : '';
      return arrow + '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ' + cc + '">D' + (st.day|0) + '·' + (chanLabel[st.channel] || st.channel) + '</span>';
    }).join(' ');
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">'
      + '<div class="flex items-start justify-between gap-2 mb-1">'
      +   '<div class="font-semibold text-slate-900">' + escapeHtml(s.name) + '</div>'
      +   '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + badge + '">' + estado + '</span>'
      + '</div>'
      + (s.description ? '<div class="text-xs text-slate-500 mb-3">' + escapeHtml(s.description) + '</div>' : '<div class="mb-3"></div>')
      + '<div class="flex items-center gap-1.5 flex-wrap mb-4">' + (steps || '<span class="text-xs text-slate-400">Sin pasos.</span>') + '</div>'
      + '<div class="flex items-center gap-2 border-t border-slate-100 pt-3">'
      +   '<button onclick="openSeqModal(' + s.id + ')" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">Editar flujo</button>'
      +   '<button onclick="toggleSeq(' + s.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">' + (s.active == 1 ? 'Pausar' : 'Activar') + '</button>'
      +   '<button onclick="deleteSeq(' + s.id + ')" class="ml-auto text-xs text-red-600 hover:underline">Eliminar</button>'
      + '</div></div>';
  }).join('');
}

// ── Editor de flujo (Drawflow) ───────────────────────────────────────────────
function ensureEditor() {
  if (editor) return;
  editor = new Drawflow(document.getElementById('flowCanvas'));
  editor.reroute = true;
  editor.start();
  editor.on('nodeSelected', function(id) { selectedNodeId = id; });
  editor.on('nodeUnselected', function() { selectedNodeId = null; });
}

// Último nodo de la cadena (desde Inicio siguiendo las flechas).
function findLastChainId() {
  var dump = editor.export().drawflow.Home.data, startId = null;
  Object.keys(dump).forEach(function(k) { if (dump[k].name === 'inicio') startId = k; });
  var cur = startId, guard = 0;
  while (cur && guard++ < 60) {
    var conns = (dump[cur].outputs && dump[cur].outputs.output_1) ? dump[cur].outputs.output_1.connections : [];
    if (!conns || !conns.length) break;
    cur = conns[0].node;
  }
  return cur;
}
function addStepFromButton() { addStepNode(0, 'email', '', findLastChainId()); }

function startNodeHtml() {
  return '<div class="seq-card"><div class="seq-head h-start">&#9654; Inicio</div>'
    + '<div class="seq-body"><div style="font-size:12px;color:#64748b">El lead entra a la rutina</div></div></div>';
}
function stepNodeHtml() {
  return '<div class="seq-card"><div class="seq-head h-step">&#9993; Paso</div>'
    + '<div class="seq-body">'
    + '<div class="seq-row2"><div style="width:70px"><label>Día</label><input type="number" df-day min="0" value="0"></div>'
    + '<div style="flex:1"><label>Canal</label><select df-channel><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="linkedin">LinkedIn</option></select></div></div>'
    + '<div><label>Objetivo del mensaje</label><input df-goal placeholder="Ej: Presentación con prueba social"></div>'
    + '</div></div>';
}

function addStartNode(x, y) {
  return editor.addNode('inicio', 0, 1, x, y, 'nodeStart', {}, startNodeHtml());
}
function addStepNode(day, channel, goal, connectTo) {
  var id = editor.addNode('paso', 1, 1, posX, posY, 'nodeStep',
                          { day: (day || 0), channel: (channel || 'email'), goal: (goal || '') }, stepNodeHtml());
  var from = (connectTo === undefined || connectTo === null) ? lastNodeId : connectTo;
  if (from !== null && from !== undefined) {
    try { editor.addConnection(from, id, 'output_1', 'input_1'); } catch (e) {}
  }
  lastNodeId = id;
  posX += 260;
  if (posX > 1000) { posX = 40; posY += 230; }
  return id;
}

function removeSelected() {
  if (!selectedNodeId) { toast('Tocá una tarjeta de paso para seleccionarla (queda con borde índigo) y luego quitala.', 'error'); return; }
  var node = editor.getNodeFromId(selectedNodeId);
  if (node && node.name === 'inicio') { toast('El nodo Inicio no se puede quitar.', 'error'); return; }
  editor.removeNodeId('node-' + selectedNodeId);
  selectedNodeId = null;
  toast('Paso quitado.', 'ok');
}

async function openSeqModal(id) {
  document.getElementById('seqModalTitle').textContent = id ? 'Editar rutina' : 'Nueva rutina';
  document.getElementById('seqModal').classList.remove('hidden');
  ensureEditor();
  editor.clear();
  lastNodeId = null; posX = 40; posY = 60;

  if (id) {
    var r = await api('api/sequences.php', { action: 'get', id: id });
    if (!r || !r.ok) { toast('No se pudo cargar.', 'error'); return; }
    var s = r.sequence;
    document.getElementById('s_id').value = s.id;
    document.getElementById('s_name').value = s.name || '';
    document.getElementById('s_desc').value = s.description || '';
    document.getElementById('s_active').checked = (s.active == 1);
    lastNodeId = addStartNode(40, 60);
    posX = 300; posY = 60;
    (s.steps || []).forEach(function(st) { addStepNode(st.day, st.channel, st.goal); });
    if (!(s.steps || []).length) addStepNode(0, 'email', '');
  } else {
    document.getElementById('s_id').value = '';
    document.getElementById('s_name').value = '';
    document.getElementById('s_desc').value = '';
    document.getElementById('s_active').checked = true;
    lastNodeId = addStartNode(40, 60);
    posX = 300; posY = 60;
    addStepNode(0, 'email', '');
  }
}
function closeSeqModal() { document.getElementById('seqModal').classList.add('hidden'); }

// Recorre el flujo desde Inicio siguiendo las flechas y arma los pasos en orden.
function collectSteps() {
  var dump = editor.export().drawflow.Home.data;
  var startId = null;
  Object.keys(dump).forEach(function(k) { if (dump[k].name === 'inicio') startId = k; });
  var steps = [], cur = startId, guard = 0;
  while (cur && guard++ < 60) {
    var node = dump[cur];
    var conns = (node.outputs && node.outputs.output_1) ? node.outputs.output_1.connections : [];
    if (!conns || !conns.length) break;
    var nextId = conns[0].node;
    var nd = dump[nextId];
    if (nd && nd.name === 'paso') {
      var el = document.getElementById('node-' + nextId);
      var day = el ? el.querySelector('[df-day]').value : nd.data.day;
      var ch  = el ? el.querySelector('[df-channel]').value : nd.data.channel;
      var goal = el ? el.querySelector('[df-goal]').value : nd.data.goal;
      if ((goal || '').trim() !== '') steps.push({ day: parseInt(day) || 0, channel: ch || 'email', goal: goal.trim() });
    }
    cur = nextId;
  }
  return steps;
}

async function saveSequence() {
  var name = document.getElementById('s_name').value.trim();
  if (!name) { toast('Ponele un nombre.', 'error'); return; }
  var steps = collectSteps();
  if (!steps.length) { toast('Agregá al menos un paso con objetivo, conectado al flujo.', 'error'); return; }
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
  if (r && r.ok) { toast('Rutina guardada.', 'ok'); closeSeqModal(); loadSequences(); }
  else toast((r && r.error) || 'Error al guardar.', 'error');
}

async function toggleSeq(id) {
  var r = await api('api/sequences.php', { action: 'toggle', id: id });
  if (r && r.ok) { toast('Rutina ' + r.status + '.', 'ok'); loadSequences(); }
  else toast('Error.', 'error');
}
async function deleteSeq(id) {
  if (!confirm('¿Eliminar esta rutina? Las campañas que la usaban quedarán sin cadencia.')) return;
  var r = await api('api/sequences.php', { action: 'delete', id: id });
  if (r && r.ok) { toast('Rutina eliminada.', 'ok'); loadSequences(); }
  else toast('Error.', 'error');
}

document.getElementById('seqModal').addEventListener('click', function(e) { if (e.target === this) closeSeqModal(); });
loadSequences();
</script>
