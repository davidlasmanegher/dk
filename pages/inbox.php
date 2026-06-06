<?php
/** Bandeja de entrada unificada (WhatsApp + email) con respuestas sugeridas por IA. */
?>
<div class="space-y-4">

  <div class="bg-white rounded-xl ring-1 ring-slate-200 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
      <select id="inboxStatus" onchange="loadInbox()" class="rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        <option value="pendiente">Pendientes</option>
        <option value="respondido">Respondidos</option>
        <option value="descartado">Descartados</option>
      </select>
      <span class="text-xs text-slate-500">El clon prepara la respuesta; vos la aprobás, editás o descartás.</span>
    </div>
    <button onclick="fetchEmail()" id="fetchEmailBtn"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg ring-1 ring-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      Revisar correo ahora
    </button>
  </div>

  <div id="inboxList" class="space-y-3">
    <div class="bg-white rounded-xl ring-1 ring-slate-200 py-10 text-center text-sm text-slate-400">Cargando…</div>
  </div>

</div>

<script>
function inboxChannelBadge(c) {
  var m = { whatsapp:'bg-emerald-50 text-emerald-700 ring-emerald-200', email:'bg-sky-50 text-sky-700 ring-sky-200' };
  var label = c === 'whatsapp' ? 'WhatsApp' : 'Email';
  return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + (m[c] || m.email) + '">' + label + '</span>';
}

async function loadInbox() {
  var status = document.getElementById('inboxStatus').value;
  var cont = document.getElementById('inboxList');
  cont.innerHTML = '<div class="bg-white rounded-xl ring-1 ring-slate-200 py-10 text-center text-sm text-slate-400">Cargando…</div>';
  var r = await api('api/inbox.php', { action: 'list', status: status });
  if (!r || !r.ok) { cont.innerHTML = '<div class="bg-white rounded-xl ring-1 ring-slate-200 py-10 text-center text-sm text-slate-400">Error al cargar.</div>'; return; }
  if (!r.messages || !r.messages.length) {
    cont.innerHTML = '<div class="bg-white rounded-xl ring-1 ring-slate-200 py-12 text-center text-sm text-slate-400">No hay mensajes ' + status + 's.</div>';
    return;
  }
  cont.innerHTML = r.messages.map(function(m) {
    var who = escapeHtml(m.lead_name && m.lead_name.trim() ? m.lead_name : (m.from_addr || 'Desconocido'));
    var comp = m.lead_company ? ' · ' + escapeHtml(m.lead_company) : '';
    var when = (m.created_at || '').substring(0, 16);
    var obj = (m.has_objection == 1) ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 bg-amber-50 text-amber-700 ring-amber-200">Posible objeción</span>' : '';
    var noLead = !m.lead_id;

    var head = '<div class="flex items-center justify-between gap-2 mb-2">'
      + '<div class="flex items-center gap-2 flex-wrap">' + inboxChannelBadge(m.channel)
      + '<span class="text-sm font-medium text-slate-900">' + who + comp + '</span>' + obj + '</div>'
      + '<span class="text-xs text-slate-400 shrink-0">' + when + '</span></div>';

    var incoming = '<div class="text-sm text-slate-700 rounded-lg bg-slate-50 ring-1 ring-slate-100 p-3 mb-3 whitespace-pre-line">' + escapeHtml(m.body || '') + '</div>';

    var inner;
    if (m.status === 'pendiente') {
      if (noLead) {
        inner = '<div class="text-xs text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-lg p-2 mb-2">Sin contacto asociado (' + escapeHtml(m.from_addr || '') + '). Creá o vinculá el lead para responder.</div>'
          + '<div class="flex gap-2"><button onclick="discardReply(' + m.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-slate-600 text-xs font-medium hover:bg-slate-50">Descartar</button></div>';
      } else {
        inner = '<label class="block text-xs font-medium text-slate-500 mb-1">Respuesta sugerida (editable)</label>'
          + '<textarea id="reply_' + m.id + '" rows="4" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y mb-2">' + escapeHtml(m.reply_draft || '') + '</textarea>'
          + '<div class="flex items-center gap-2 flex-wrap">'
          + '<button onclick="approveReply(' + m.id + ',\'' + m.channel + '\')" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">Aprobar y enviar</button>'
          + '<button onclick="regenReply(' + m.id + ')" id="regen_' + m.id + '" class="px-3 py-1.5 rounded-lg ring-1 ring-indigo-300 text-indigo-700 text-xs font-medium hover:bg-indigo-50">Regenerar</button>'
          + '<button onclick="discardReply(' + m.id + ')" class="px-3 py-1.5 rounded-lg ring-1 ring-slate-300 text-slate-600 text-xs font-medium hover:bg-slate-50">Descartar</button>'
          + '</div>';
      }
    } else if (m.status === 'respondido') {
      inner = '<label class="block text-xs font-medium text-slate-500 mb-1">Respuesta enviada</label>'
        + '<div class="text-sm text-slate-700 rounded-lg bg-emerald-50 ring-1 ring-emerald-100 p-3 whitespace-pre-line">' + escapeHtml(m.reply_draft || '') + '</div>';
    } else {
      inner = '<div class="text-xs text-slate-400">Descartado.</div>';
    }

    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">' + head + incoming + inner + '</div>';
  }).join('');
}

async function approveReply(id, channel) {
  var ta = document.getElementById('reply_' + id);
  var body = ta ? ta.value.trim() : '';
  if (!body) { toast('La respuesta está vacía.', 'error'); return; }
  var canal = channel === 'whatsapp' ? 'WhatsApp' : 'email';
  if (!confirm('¿Enviar esta respuesta al prospecto por ' + canal + '? Es real e inmediata.')) return;
  var r = await api('api/inbox.php', { action: 'approve', id: id, body: body });
  if (r && r.ok) { toast('Respuesta enviada.', 'ok'); loadInbox(); }
  else { toast(r.error || 'No se pudo enviar.', 'error'); }
}

async function regenReply(id) {
  var restore = loading(document.getElementById('regen_' + id), 'Regenerando…');
  var r = await api('api/inbox.php', { action: 'regenerate', id: id });
  restore();
  if (r && r.ok) { var ta = document.getElementById('reply_' + id); if (ta) ta.value = r.reply; toast('Respuesta regenerada.', 'ok'); }
  else { toast(r.error || 'No se pudo regenerar.', 'error'); }
}

async function discardReply(id) {
  if (!confirm('¿Descartar este mensaje?')) return;
  var r = await api('api/inbox.php', { action: 'discard', id: id });
  if (r && r.ok) { toast('Descartado.', 'ok'); loadInbox(); }
  else { toast(r.error || 'Error.', 'error'); }
}

async function fetchEmail() {
  var restore = loading(document.getElementById('fetchEmailBtn'), 'Revisando…');
  var r = await api('api/inbox.php', { action: 'fetch_email' });
  restore();
  if (r && r.ok) { toast('Correos nuevos: ' + (r.processed || 0), 'ok'); loadInbox(); }
  else { toast(r.error || 'No se pudo revisar el correo.', 'error'); }
}

loadInbox();
</script>
