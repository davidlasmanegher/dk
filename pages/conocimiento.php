<?php
/** Base de conocimiento de SISTEL: casos, propuestas, one-pager, guía ejecutiva. */
$has_openai = strlen(trim((string)setting('openai_api_key', ''))) > 10;
?>
<div class="max-w-4xl space-y-5">

  <div class="bg-indigo-50 ring-1 ring-indigo-200 rounded-xl px-5 py-4 text-sm text-indigo-900">
    <strong class="font-semibold">Cerebro de conocimiento</strong> —
    Cargá acá los casos de éxito, propuestas, one-pager y guías de SISTEL. El clon los usa como
    referencia real (prueba social, datos y casos) al redactar outreach y responder mensajes.
    <?php if ($has_openai): ?>
      <span class="block mt-1 text-xs text-emerald-700">Búsqueda semántica activa (embeddings).</span>
    <?php else: ?>
      <span class="block mt-1 text-xs text-amber-700">Sin OpenAI API key: búsqueda por texto (FULLTEXT). Agregá la key en Ajustes para búsqueda semántica.</span>
    <?php endif; ?>
  </div>

  <div class="grid lg:grid-cols-5 gap-5">
    <!-- Form alta -->
    <form id="kForm" class="lg:col-span-2 bg-white rounded-xl ring-1 ring-slate-200 p-5 space-y-4 self-start" onsubmit="return addDoc(event)">
      <h3 class="text-sm font-semibold text-slate-900">Agregar documento</h3>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
        <select id="k_type" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <option value="caso">Caso de éxito</option>
          <option value="propuesta">Propuesta</option>
          <option value="one_pager">One-pager / credenciales</option>
          <option value="guia">Guía ejecutiva</option>
          <option value="producto">Producto / servicio</option>
          <option value="nota">Nota / otro</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Título</label>
        <input type="text" id="k_title" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Caso J&J México — Universidad Comercial">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Contenido</label>
        <textarea id="k_content" rows="10" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-y" placeholder="Pegá el caso, propuesta o documento completo…"></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Fuente (opcional)</label>
        <input type="text" id="k_source" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="URL o referencia">
      </div>
      <button type="submit" id="kBtn" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">Agregar al cerebro</button>
    </form>

    <!-- Lista -->
    <div class="lg:col-span-3 bg-white rounded-xl ring-1 ring-slate-200">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">Documentos cargados</h3>
        <span id="kCount" class="text-xs text-slate-400"></span>
      </div>
      <div id="kList" class="divide-y divide-slate-50">
        <div class="px-5 py-6 text-center text-sm text-slate-400">Cargando…</div>
      </div>
    </div>
  </div>
</div>

<script>
var K_TYPES = { caso:'Caso de éxito', propuesta:'Propuesta', one_pager:'One-pager', guia:'Guía ejecutiva', producto:'Producto', nota:'Nota' };

async function loadDocs() {
  var r = await api('api/knowledge.php', { action: 'list' });
  var list = document.getElementById('kList');
  if (!r || !r.ok) { list.innerHTML = '<div class="px-5 py-6 text-center text-sm text-slate-400">Error al cargar.</div>'; return; }
  document.getElementById('kCount').textContent = (r.docs ? r.docs.length : 0) + ' documentos';
  if (!r.docs || !r.docs.length) {
    list.innerHTML = '<div class="px-5 py-10 text-center text-sm text-slate-400">Sin documentos. Cargá el primer caso o propuesta.</div>';
    return;
  }
  list.innerHTML = r.docs.map(function(doc) {
    return '<div class="px-5 py-3 flex items-center justify-between gap-3">'
      + '<div class="min-w-0">'
      + '<div class="text-sm font-medium text-slate-900 truncate">' + escapeHtml(doc.title || '(sin título)') + '</div>'
      + '<div class="text-xs text-slate-500">' + escapeHtml(K_TYPES[doc.type] || doc.type) + ' · ' + doc.chunks + ' pasajes · ' + (doc.created_at || '').substring(0,10) + '</div>'
      + '</div>'
      + '<button onclick="delDoc(' + doc.id + ')" class="text-xs text-red-600 hover:underline shrink-0">Borrar</button>'
      + '</div>';
  }).join('');
}

async function addDoc(e) {
  e.preventDefault();
  var title = document.getElementById('k_title').value.trim();
  var content = document.getElementById('k_content').value.trim();
  if (!title || !content) { toast('Título y contenido son obligatorios.', 'error'); return false; }
  var restore = loading(document.getElementById('kBtn'), 'Procesando…');
  var r = await api('api/knowledge.php', {
    action: 'add',
    type: document.getElementById('k_type').value,
    title: title,
    content: content,
    source: document.getElementById('k_source').value.trim(),
  });
  restore();
  if (r && r.ok) {
    toast('Documento agregado (' + r.chunks + ' pasajes' + (r.embeddings ? ', con embeddings' : '') + ').', 'ok');
    document.getElementById('k_title').value = '';
    document.getElementById('k_content').value = '';
    document.getElementById('k_source').value = '';
    loadDocs();
  } else { toast(r.error || 'Error al agregar.', 'error'); }
  return false;
}

async function delDoc(id) {
  if (!confirm('¿Borrar este documento del cerebro?')) return;
  var r = await api('api/knowledge.php', { action: 'delete', id: id });
  if (r && r.ok) { toast('Documento borrado.', 'ok'); loadDocs(); }
  else { toast(r.error || 'Error.', 'error'); }
}

loadDocs();
</script>
