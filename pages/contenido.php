<?php
/** Piezas de contenido para LinkedIn y outreach */
$lead_id_param = (int)($_GET['lead_id'] ?? 0);
?>
<div class="space-y-5">

  <!-- Filtros y acciones -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex flex-wrap items-center gap-3">
    <select id="filterType" onchange="loadContent()"
            class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      <option value="">Todos los tipos</option>
      <option value="post_linkedin">Post LinkedIn</option>
      <option value="articulo">Artículo</option>
      <option value="newsletter">Newsletter</option>
      <option value="mensaje_prospecto">Mensaje a prospecto</option>
      <option value="email_seguimiento">Email de seguimiento</option>
      <option value="propuesta">Propuesta</option>
    </select>
    <select id="filterContentStatus" onchange="loadContent()"
            class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      <option value="">Todos los estados</option>
      <option value="borrador">Borrador</option>
      <option value="revision">En revisión</option>
      <option value="publicado">Publicado</option>
      <option value="archivado">Archivado</option>
    </select>
    <button onclick="openGenModal()"
            class="ml-auto flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Generar contenido
    </button>
  </div>

  <!-- Lista de contenido -->
  <div id="contentGrid" class="grid gap-4 lg:grid-cols-2">
    <div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<!-- Modal: generar contenido -->
<div id="genModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900">Generar contenido con IA</h2>
      <button onclick="closeGenModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de contenido *</label>
          <select id="gen_type" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="post_linkedin">Post LinkedIn</option>
            <option value="mensaje_prospecto">Mensaje a prospecto</option>
            <option value="email_seguimiento">Email de seguimiento</option>
            <option value="propuesta">Resumen de propuesta</option>
            <option value="articulo">Artículo / Newsletter</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Lead relacionado (opcional)</label>
          <select id="gen_lead_id" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="">— ninguno —</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Contexto / instrucciones adicionales</label>
        <textarea id="gen_context" rows="4"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"
                  placeholder="Ej: El lead es Director de RH en empresa de manufactura con 800 empleados. Están evaluando migrar de su LMS actual. El objetivo es solicitar una llamada de 20 min."></textarea>
      </div>

      <!-- Resultado -->
      <div id="genResult" class="hidden">
        <div class="border-t border-slate-200 pt-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-700">Contenido generado</span>
            <button onclick="copyGenResult()" class="text-xs text-indigo-600 hover:underline">Copiar</button>
          </div>
          <div id="genResultTitle" class="text-sm font-semibold text-slate-900 mb-2"></div>
          <div id="genResultBody" class="prose text-sm bg-slate-50 rounded-lg p-4 whitespace-pre-wrap max-h-64 overflow-y-auto"></div>
          <div class="mt-3 flex items-center gap-2">
            <button onclick="saveGenResult()"
                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
              Guardar pieza
            </button>
            <button onclick="generateContent()"
                    class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
              Regenerar
            </button>
          </div>
        </div>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2.5">
      <button onclick="closeGenModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cerrar</button>
      <button id="genBtn" onclick="generateContent()"
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Generar
      </button>
    </footer>
  </div>
</div>

<!-- Modal: ver/editar pieza -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.4);backdrop-filter:blur(3px)">
  <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col">
    <header class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
      <input type="text" id="vm_title" class="flex-1 text-base font-semibold bg-transparent border-none outline-none text-slate-900 mr-4">
      <button onclick="closeViewModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
    </header>
    <div class="px-6 py-4 overflow-y-auto flex-1">
      <input type="hidden" id="vm_id">
      <textarea id="vm_body" rows="16"
                class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-indigo-500 resize-none font-mono"></textarea>
      <div class="mt-3 flex items-center gap-3">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
          <select id="vm_status" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
            <option value="borrador">Borrador</option>
            <option value="revision">En revisión</option>
            <option value="publicado">Publicado</option>
            <option value="archivado">Archivado</option>
          </select>
        </div>
      </div>
    </div>
    <footer class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-between">
      <button onclick="deletePiece()" class="text-sm text-red-600 hover:underline">Eliminar</button>
      <div class="flex gap-2.5">
        <button onclick="closeViewModal()" class="px-4 py-2 rounded-lg ring-1 ring-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</button>
        <button id="vm_save" onclick="savePiece()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar</button>
      </div>
    </footer>
  </div>
</div>

<script>
var generatedData = null;

// Cargar leads en select
(async function() {
  var r = await api('api/leads.php', { action: 'list_leads', limit: 200, offset: 0 });
  var sel = document.getElementById('gen_lead_id');
  if (r && r.ok && r.leads) {
    r.leads.forEach(function(l) {
      var opt = document.createElement('option');
      opt.value = l.id;
      var name = (l.first_name + ' ' + (l.last_name || '')).trim();
      opt.textContent = name + (l.company ? ' — ' + l.company : '');
      sel.appendChild(opt);
    });
  }
  // Si venimos con lead_id en URL, preseleccionar
  var urlLead = <?= $lead_id_param ?>;
  if (urlLead) {
    sel.value = urlLead;
    openGenModal();
    document.getElementById('gen_type').value = 'mensaje_prospecto';
  }
})();

async function loadContent() {
  var type   = document.getElementById('filterType').value;
  var status = document.getElementById('filterContentStatus').value;
  var grid   = document.getElementById('contentGrid');
  grid.innerHTML = '<div class="col-span-2 text-center py-8 text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/content.php', { action: 'list_content', type: type, status: status, limit: 50 });
  if (!r || !r.ok || !r.pieces || r.pieces.length === 0) {
    grid.innerHTML = '<div class="col-span-2 text-center py-12 text-sm text-slate-400">Sin piezas de contenido. Genera la primera.</div>';
    return;
  }
  var typeLabels = { post_linkedin:'Post LinkedIn', articulo:'Artículo', newsletter:'Newsletter', mensaje_prospecto:'Mensaje prospecto', email_seguimiento:'Email seguimiento', propuesta:'Propuesta' };
  var statusColors = { borrador:'bg-amber-50 text-amber-700 ring-amber-200', revision:'bg-violet-50 text-violet-700 ring-violet-200', publicado:'bg-emerald-50 text-emerald-700 ring-emerald-200', archivado:'bg-slate-100 text-slate-400 ring-slate-200' };
  grid.innerHTML = r.pieces.map(function(p) {
    var sc = statusColors[p.status] || 'bg-slate-100 text-slate-600 ring-slate-200';
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5 cursor-pointer hover:shadow-sm transition" onclick="openViewModal(' + p.id + ')">'
      + '<div class="flex items-start justify-between gap-2 mb-2">'
      + '<div class="font-medium text-slate-900 line-clamp-1">' + escapeHtml(p.title || 'Sin título') + '</div>'
      + '<span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + sc + '">' + escapeHtml(p.status) + '</span>'
      + '</div>'
      + '<div class="text-xs text-indigo-600 font-medium mb-1.5">' + escapeHtml(typeLabels[p.type] || p.type) + '</div>'
      + '<p class="text-sm text-slate-600 line-clamp-3">' + escapeHtml(p.body || '') + '</p>'
      + '<div class="text-xs text-slate-400 mt-3">' + (p.created_at || '').substring(0,10) + '</div>'
      + '</div>';
  }).join('');
}

function openGenModal() {
  document.getElementById('genResult').classList.add('hidden');
  document.getElementById('genModal').classList.remove('hidden');
}
function closeGenModal() { document.getElementById('genModal').classList.add('hidden'); }

async function generateContent() {
  var btn = document.getElementById('genBtn');
  var restore = loading(btn, 'Generando…');
  document.getElementById('genResult').classList.add('hidden');
  var r = await api('api/content.php', {
    action:   'generate',
    type:     document.getElementById('gen_type').value,
    lead_id:  document.getElementById('gen_lead_id').value || null,
    context:  document.getElementById('gen_context').value.trim(),
  });
  restore();
  if (!r || !r.ok) { toast(r.error || 'Error al generar.', 'error'); return; }
  generatedData = r;
  document.getElementById('genResultTitle').textContent = r.title || '';
  document.getElementById('genResultBody').textContent  = r.body  || '';
  document.getElementById('genResult').classList.remove('hidden');
}

async function saveGenResult() {
  if (!generatedData) return;
  var r = await api('api/content.php', {
    action:  'save_content',
    type:    document.getElementById('gen_type').value,
    lead_id: document.getElementById('gen_lead_id').value || null,
    title:   generatedData.title || '',
    body:    generatedData.body  || '',
    status:  'borrador',
  });
  if (r && r.ok) { toast('Pieza guardada.', 'ok'); closeGenModal(); loadContent(); }
  else toast(r.error || 'Error al guardar.', 'error');
}

function copyGenResult() {
  var body = document.getElementById('genResultBody').textContent;
  navigator.clipboard.writeText(body).then(function() { toast('Copiado.', 'ok'); });
}

async function openViewModal(id) {
  var r = await api('api/content.php', { action: 'get_content', id: id });
  if (!r || !r.ok || !r.piece) { toast('Error al cargar.', 'error'); return; }
  var p = r.piece;
  document.getElementById('vm_id').value       = p.id;
  document.getElementById('vm_title').value    = p.title || '';
  document.getElementById('vm_body').value     = p.body  || '';
  document.getElementById('vm_status').value   = p.status || 'borrador';
  document.getElementById('viewModal').classList.remove('hidden');
}
function closeViewModal() { document.getElementById('viewModal').classList.add('hidden'); }

async function savePiece() {
  var btn = document.getElementById('vm_save');
  var restore = loading(btn, 'Guardando…');
  var r = await api('api/content.php', {
    action: 'save_content',
    id:     parseInt(document.getElementById('vm_id').value),
    title:  document.getElementById('vm_title').value,
    body:   document.getElementById('vm_body').value,
    status: document.getElementById('vm_status').value,
  });
  restore();
  if (r && r.ok) { toast('Guardado.', 'ok'); closeViewModal(); loadContent(); }
  else toast(r.error || 'Error.', 'error');
}

async function deletePiece() {
  var id = document.getElementById('vm_id').value;
  if (!id || !confirm('¿Eliminar esta pieza?')) return;
  var r = await api('api/content.php', { action: 'delete_content', id: parseInt(id) });
  if (r && r.ok) { toast('Eliminado.', 'ok'); closeViewModal(); loadContent(); }
  else toast(r.error || 'Error.', 'error');
}

document.getElementById('genModal').addEventListener('click', function(e) { if (e.target === this) closeGenModal(); });
document.getElementById('viewModal').addEventListener('click', function(e) { if (e.target === this) closeViewModal(); });

loadContent();
</script>
