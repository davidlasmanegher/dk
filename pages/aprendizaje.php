<?php
/** Aprendizaje — qué aprende Daniel de las ediciones y aprobaciones del equipo. */
?>
<div class="space-y-5">

  <div class="bg-indigo-50 ring-1 ring-indigo-200 rounded-xl px-5 py-4 text-sm text-indigo-900">
    <strong class="font-semibold">Cómo aprende Daniel</strong> &mdash;
    Cada mensaje que <strong>aprobás</strong> o <strong>editás</strong> antes de enviar queda como ejemplo.
    Daniel usa los más recientes como referencia de estilo en sus próximas redacciones: mejora solo, con tu criterio.
  </div>

  <div id="learnStats" class="grid gap-4 sm:grid-cols-3">
    <div class="col-span-3 text-center py-6 text-sm text-slate-400">Cargando…</div>
  </div>

  <div class="grid gap-4 lg:grid-cols-2">
    <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Lo que Daniel <span class="text-emerald-600">suma</span> cuando lo corregís</h3>
      <div id="topAdded" class="flex flex-wrap gap-1.5"><span class="text-xs text-slate-400">—</span></div>
    </div>
    <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Lo que el equipo le <span class="text-red-600">quita</span></h3>
      <div id="topRemoved" class="flex flex-wrap gap-1.5"><span class="text-xs text-slate-400">—</span></div>
    </div>
  </div>

  <div>
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Ejemplos recientes</h3>
    <div id="learnList" class="space-y-3">
      <div class="bg-white rounded-xl ring-1 ring-slate-200 py-10 text-center text-sm text-slate-400">Cargando…</div>
    </div>
  </div>

</div>

<script>
function statCard(value, label, sub) {
  return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">'
    + '<div class="text-2xl font-bold text-slate-900">' + value + '</div>'
    + '<div class="text-sm font-medium text-slate-700 mt-0.5">' + label + '</div>'
    + (sub ? '<div class="text-xs text-slate-400 mt-0.5">' + sub + '</div>' : '') + '</div>';
}
function chips(arr, cls) {
  if (!arr || !arr.length) return '<span class="text-xs text-slate-400">Sin datos todavía.</span>';
  return arr.map(function(w) { return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 ' + cls + '">' + escapeHtml(w) + '</span>'; }).join('');
}
var srcLabel = { inbox:'Respuesta', campaign:'Campaña', outreach:'Primer contacto', secuencia:'Secuencia', follow_up:'Seguimiento' };

async function loadLearning() {
  var r = await api('api/learning.php', { action: 'stats' });
  if (!r || !r.ok) { document.getElementById('learnStats').innerHTML = '<div class="col-span-3 text-center py-6 text-sm text-slate-400">Error al cargar.</div>'; return; }
  var s = r.stats;
  document.getElementById('learnStats').innerHTML =
      statCard(s.total, 'Ejemplos aprendidos', 'mensajes aprobados o editados')
    + statCard(s.kept_pct + '%', 'Enviados tal cual', 'la IA acertó sin cambios')
    + statCard((s.total - s.kept_as_is), 'Ajustados por vos', 'donde corregiste el estilo');
  document.getElementById('topAdded').innerHTML   = chips(s.top_added, 'bg-emerald-50 text-emerald-700 ring-emerald-200');
  document.getElementById('topRemoved').innerHTML = chips(s.top_removed, 'bg-red-50 text-red-700 ring-red-200');

  var list = document.getElementById('learnList');
  if (!r.examples || !r.examples.length) {
    list.innerHTML = '<div class="bg-white rounded-xl ring-1 ring-slate-200 py-12 text-center text-sm text-slate-400">Todavía no hay ejemplos. Apenas apruebes o edites mensajes en la Bandeja o el Agente, Daniel empieza a aprender.</div>';
    return;
  }
  list.innerHTML = r.examples.map(function(e) {
    var kept = e.kept_as_is == 1;
    var badge = kept ? '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200">Enviado tal cual</span>'
                     : '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 bg-amber-50 text-amber-700 ring-amber-200">Editado por vos</span>';
    var who = (e.lead_name && e.lead_name.trim()) ? ' · ' + escapeHtml(e.lead_name.trim()) : '';
    var src = '<span class="text-xs text-indigo-600 font-medium">' + (srcLabel[e.source] || e.source) + '</span>';
    var ctx = e.context ? '<div class="text-xs text-slate-400 mb-2 line-clamp-2">Situación: ' + escapeHtml(e.context) + '</div>' : '';
    var bodies = kept
      ? '<div class="text-sm text-slate-700 rounded-lg bg-slate-50 ring-1 ring-slate-100 p-3 whitespace-pre-line">' + escapeHtml(e.final_version || '') + '</div>'
      : '<div class="grid sm:grid-cols-2 gap-2">'
        + '<div><div class="text-[11px] font-medium text-slate-400 mb-1">Versión IA</div><div class="text-xs text-slate-500 rounded-lg bg-slate-50 ring-1 ring-slate-100 p-2.5 whitespace-pre-line line-clamp-6">' + escapeHtml(e.ai_version || '') + '</div></div>'
        + '<div><div class="text-[11px] font-medium text-emerald-600 mb-1">Versión final (enviada)</div><div class="text-xs text-slate-700 rounded-lg bg-emerald-50 ring-1 ring-emerald-100 p-2.5 whitespace-pre-line line-clamp-6">' + escapeHtml(e.final_version || '') + '</div></div>'
        + '</div>';
    return '<div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">'
      + '<div class="flex items-center gap-2 mb-2">' + src + who + '<span class="ml-auto">' + badge + '</span></div>'
      + ctx + bodies + '</div>';
  }).join('');
}
loadLearning();
</script>
