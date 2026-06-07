<?php
/** Bandeja de entrada unificada (WhatsApp + email) estilo webmail: lista + lectura. */
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-hidden flex" style="height:calc(100vh - 7.5rem)">

  <!-- Panel izquierdo: lista -->
  <div id="listPane" class="w-full lg:w-96 shrink-0 border-r border-slate-200 flex lg:flex flex-col">
    <!-- Filtros -->
    <div class="px-3 py-2.5 border-b border-slate-200 flex items-center gap-2">
      <div class="flex bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
        <button data-status="pendiente"  onclick="setFilter('pendiente', this)"  class="filterTab px-2.5 py-1.5 rounded-md bg-white text-slate-900 shadow-sm">Pendientes</button>
        <button data-status="respondido" onclick="setFilter('respondido', this)" class="filterTab px-2.5 py-1.5 rounded-md text-slate-500">Respondidos</button>
        <button data-status="descartado" onclick="setFilter('descartado', this)" class="filterTab px-2.5 py-1.5 rounded-md text-slate-500">Archivados</button>
      </div>
      <button onclick="fetchEmail()" id="fetchEmailBtn" title="Revisar correo ahora"
              class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg ring-1 ring-slate-300 text-slate-600 text-xs font-medium hover:bg-slate-50 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-6.2-8.5M21 3v6h-6"/></svg>
        <span class="hidden sm:inline">Revisar</span>
      </button>
    </div>
    <!-- Lista -->
    <div id="inboxList" class="flex-1 overflow-y-auto">
      <div class="py-10 text-center text-sm text-slate-400">Cargando…</div>
    </div>
  </div>

  <!-- Panel derecho: lectura -->
  <div id="detailPane" class="hidden lg:flex flex-1 flex-col bg-slate-50/50">
    <div id="detailEmpty" class="flex-1 flex flex-col items-center justify-center text-slate-400 px-6 text-center">
      <svg class="h-12 w-12 mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><path d="M22 6l-10 7L2 6"/></svg>
      <p class="text-sm">Seleccioná un mensaje para leerlo y responder.</p>
    </div>
    <div id="detailContent" class="hidden flex-1 flex-col min-h-0"></div>
  </div>

</div>

<script>
var INBOX = { msgs: [], sel: 0, status: 'pendiente' };

function chanMeta(c) {
  return c === 'whatsapp'
    ? { label: 'WhatsApp', avatar: 'bg-emerald-100 text-emerald-700', badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200' }
    : { label: 'Email',    avatar: 'bg-sky-100 text-sky-700',         badge: 'bg-sky-50 text-sky-700 ring-sky-200' };
}
function whoOf(m) { return (m.lead_name && m.lead_name.trim()) ? m.lead_name.trim() : (m.from_addr || 'Desconocido'); }
function initialOf(s) { s = (s || '?').trim(); return (s[0] || '?').toUpperCase(); }
function fmtWhen(s) {
  s = (s || '').replace(' ', 'T'); var d = new Date(s);
  if (isNaN(d)) return (s || '').substring(0, 16).replace('T', ' ');
  var now = new Date(), sameDay = d.toDateString() === now.toDateString();
  return sameDay ? d.toTimeString().substring(0, 5) : (d.getDate() + '/' + (d.getMonth() + 1));
}

function setFilter(status, btn) {
  INBOX.status = status; INBOX.sel = 0;
  document.querySelectorAll('.filterTab').forEach(function(b) { b.className = 'filterTab px-2.5 py-1.5 rounded-md text-slate-500'; });
  btn.className = 'filterTab px-2.5 py-1.5 rounded-md bg-white text-slate-900 shadow-sm';
  renderDetail(); loadInbox();
}

async function loadInbox() {
  var cont = document.getElementById('inboxList');
  cont.innerHTML = '<div class="py-10 text-center text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/inbox.php', { action: 'list', status: INBOX.status });
  if (!r || !r.ok) { cont.innerHTML = '<div class="py-10 text-center text-sm text-slate-400">Error al cargar.</div>'; return; }
  INBOX.msgs = r.messages || [];
  if (!INBOX.msgs.length) {
    cont.innerHTML = '<div class="py-12 px-6 text-center text-sm text-slate-400">No hay mensajes acá.</div>';
    renderDetail(); return;
  }
  cont.innerHTML = INBOX.msgs.map(function(m) {
    var cm = chanMeta(m.channel), who = escapeHtml(whoOf(m));
    var active = (m.id == INBOX.sel);
    var subj = escapeHtml(m.subject || '(sin asunto)');
    var prev = escapeHtml((m.body || '').replace(/\s+/g, ' ').substring(0, 80));
    var obj = (m.has_objection == 1) ? '<span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-400 align-middle" title="Posible objeción"></span>' : '';
    return '<div onclick="selectMsg(' + m.id + ')" class="px-3 py-3 border-b border-slate-100 cursor-pointer flex gap-3 ' + (active ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50 border-l-2 border-l-transparent') + '">'
      + '<div class="h-9 w-9 shrink-0 rounded-full grid place-items-center text-sm font-semibold ' + cm.avatar + '">' + initialOf(whoOf(m)) + '</div>'
      + '<div class="min-w-0 flex-1">'
      +   '<div class="flex items-center justify-between gap-2">'
      +     '<span class="text-sm font-semibold text-slate-900 truncate">' + who + obj + '</span>'
      +     '<span class="text-[11px] text-slate-400 shrink-0">' + fmtWhen(m.created_at) + '</span>'
      +   '</div>'
      +   '<div class="text-xs font-medium text-slate-700 truncate">' + subj + '</div>'
      +   '<div class="text-xs text-slate-400 truncate">' + prev + '</div>'
      + '</div></div>';
  }).join('');
}

function selectMsg(id) {
  INBOX.sel = id;
  renderDetail();
  // resaltar en lista sin recargar
  loadHighlight();
  // móvil: mostrar detalle
  if (window.innerWidth < 1024) {
    document.getElementById('listPane').classList.add('hidden');
    document.getElementById('detailPane').classList.remove('hidden');
    document.getElementById('detailPane').classList.add('flex');
  }
}
function loadHighlight() {
  // re-render ligero de la lista para marcar el activo
  var cont = document.getElementById('inboxList');
  if (cont && INBOX.msgs.length) {
    var prev = cont.scrollTop;
    // reusar loadInbox-render sin fetch
    cont.querySelectorAll('[onclick^="selectMsg"]').forEach(function(el) {
      var isSel = el.getAttribute('onclick').indexOf('(' + INBOX.sel + ')') !== -1;
      el.className = 'px-3 py-3 border-b border-slate-100 cursor-pointer flex gap-3 ' + (isSel ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'hover:bg-slate-50 border-l-2 border-l-transparent');
    });
    cont.scrollTop = prev;
  }
}

function backToList() {
  document.getElementById('detailPane').classList.add('hidden');
  document.getElementById('detailPane').classList.remove('flex');
  document.getElementById('listPane').classList.remove('hidden');
}

function renderDetail() {
  var empty = document.getElementById('detailEmpty');
  var box = document.getElementById('detailContent');
  var m = INBOX.msgs.filter(function(x) { return x.id == INBOX.sel; })[0];
  if (!m) { empty.classList.remove('hidden'); box.classList.add('hidden'); box.classList.remove('flex'); return; }
  empty.classList.add('hidden'); box.classList.remove('hidden'); box.classList.add('flex');

  var cm = chanMeta(m.channel), who = escapeHtml(whoOf(m));
  var comp = m.lead_company ? escapeHtml(m.lead_company) : '';
  var when = (m.created_at || '').substring(0, 16).replace('T', ' ');
  var obj = (m.has_objection == 1) ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 bg-amber-50 text-amber-700 ring-amber-200">Posible objeción</span>' : '';

  var header = '<div class="px-5 py-4 border-b border-slate-200 bg-white">'
    + '<button onclick="backToList()" class="lg:hidden mb-2 text-xs text-indigo-600 font-medium">&larr; Volver</button>'
    + '<div class="flex items-start gap-3">'
    +   '<div class="h-10 w-10 shrink-0 rounded-full grid place-items-center text-base font-semibold ' + cm.avatar + '">' + initialOf(whoOf(m)) + '</div>'
    +   '<div class="min-w-0 flex-1">'
    +     '<div class="flex items-center gap-2 flex-wrap"><span class="font-semibold text-slate-900">' + who + '</span>'
    +       '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + cm.badge + '">' + cm.label + '</span>' + obj + '</div>'
    +     '<div class="text-xs text-slate-500">' + (comp ? comp + ' · ' : '') + escapeHtml(m.from_addr || '') + '</div>'
    +   '</div>'
    +   '<span class="text-xs text-slate-400 shrink-0">' + when + '</span>'
    + '</div>'
    + '<div class="mt-3 text-base font-semibold text-slate-900">' + escapeHtml(m.subject || '(sin asunto)') + '</div>'
    + '</div>';

  var incoming = '<div class="px-5 py-4 overflow-y-auto flex-1 min-h-0">'
    + '<div class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">' + escapeHtml(m.body || '') + '</div></div>';

  var footer = '';
  if (m.status === 'pendiente') {
    if (!m.lead_id) {
      footer = '<div class="px-5 py-4 border-t border-slate-200 bg-white">'
        + '<div class="text-xs text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-lg p-3 mb-3">Este mensaje no está asociado a ningún contacto del CRM (' + escapeHtml(m.from_addr || '') + '). Vinculá o creá el lead para que Daniel pueda responder.</div>'
        + '<button onclick="discardMsg()" class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50">Archivar</button></div>';
    } else {
      footer = '<div class="px-5 py-4 border-t border-slate-200 bg-white">'
        + '<div class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 mb-2">'
        +   '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Respuesta sugerida por Daniel (editala libremente)</div>'
        + '<textarea id="replyBox" rows="5" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y mb-3">' + escapeHtml(m.reply_draft || '') + '</textarea>'
        + '<div class="flex items-center gap-2 flex-wrap">'
        +   '<button onclick="approveMsg()" id="approveBtn" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">'
        +     '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>Aprobar y enviar</button>'
        +   '<button onclick="regenMsg()" id="regenBtn" class="px-4 py-2 rounded-lg ring-1 ring-indigo-300 text-indigo-700 text-sm font-medium hover:bg-indigo-50">Regenerar</button>'
        +   '<button onclick="discardMsg()" class="ml-auto px-4 py-2 rounded-lg ring-1 ring-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50">Archivar</button>'
        + '</div></div>';
    }
  } else if (m.status === 'respondido') {
    footer = '<div class="px-5 py-4 border-t border-slate-200 bg-white">'
      + '<div class="text-xs font-semibold text-emerald-700 mb-2">Respuesta enviada</div>'
      + '<div class="text-sm text-slate-700 rounded-lg bg-emerald-50 ring-1 ring-emerald-100 p-3 whitespace-pre-line">' + escapeHtml(m.reply_draft || '') + '</div></div>';
  } else {
    footer = '<div class="px-5 py-4 border-t border-slate-200 bg-white text-sm text-slate-400">Mensaje archivado.</div>';
  }

  box.innerHTML = header + incoming + footer;
}

async function approveMsg() {
  var ta = document.getElementById('replyBox');
  var body = ta ? ta.value.trim() : '';
  if (!body) { toast('La respuesta está vacía.', 'error'); return; }
  var m = INBOX.msgs.filter(function(x) { return x.id == INBOX.sel; })[0];
  var canal = (m && m.channel === 'whatsapp') ? 'WhatsApp' : 'correo';
  if (!confirm('¿Enviar esta respuesta al prospecto por ' + canal + '? Es real e inmediata.')) return;
  var restore = loading(document.getElementById('approveBtn'), 'Enviando…');
  var r = await api('api/inbox.php', { action: 'approve', id: INBOX.sel, body: body });
  restore();
  if (r && r.ok) { toast('Respuesta enviada.', 'ok'); INBOX.sel = 0; backToList(); loadInbox(); }
  else { toast((r && r.error) || 'No se pudo enviar.', 'error'); }
}

async function regenMsg() {
  var restore = loading(document.getElementById('regenBtn'), 'Regenerando…');
  var r = await api('api/inbox.php', { action: 'regenerate', id: INBOX.sel });
  restore();
  if (r && r.ok) { var ta = document.getElementById('replyBox'); if (ta) ta.value = r.reply; toast('Respuesta regenerada.', 'ok'); }
  else { toast((r && r.error) || 'No se pudo regenerar.', 'error'); }
}

async function discardMsg() {
  if (!confirm('¿Archivar este mensaje?')) return;
  var r = await api('api/inbox.php', { action: 'discard', id: INBOX.sel });
  if (r && r.ok) { toast('Archivado.', 'ok'); INBOX.sel = 0; backToList(); loadInbox(); }
  else { toast((r && r.error) || 'Error.', 'error'); }
}

async function fetchEmail() {
  var restore = loading(document.getElementById('fetchEmailBtn'), '…');
  var r = await api('api/inbox.php', { action: 'fetch_email' });
  restore();
  if (r && r.ok) { toast('Correos nuevos: ' + (r.processed || 0), 'ok'); loadInbox(); }
  else { toast((r && r.error) || 'No se pudo revisar el correo.', 'error'); }
}

loadInbox();
</script>
