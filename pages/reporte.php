<?php /** Reporte ejecutivo: estado de campañas + gestión comercial de Daniel. */ ?>
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-bold text-slate-900">Reporte de gestión</h1>
      <p class="text-sm text-slate-500">Cómo viene la prospección de Daniel — datos reales de campaña.</p>
    </div>
    <button onclick="location.reload()" class="text-xs font-medium text-slate-500 hover:text-indigo-600 ring-1 ring-slate-200 rounded-lg px-3 py-1.5">↻ Actualizar</button>
  </div>

  <!-- KPIs -->
  <div id="kpis" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach (['Contactados','Respuestas','Tasa de respuesta','Por aprobar'] as $i => $lbl): ?>
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
        <div class="text-2xl font-bold text-slate-900" id="kpi<?= $i ?>">—</div>
        <div class="text-xs text-slate-500"><?= e($lbl) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
      <!-- Campañas -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Campañas</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-slate-400 border-b border-slate-100">
              <th class="pb-2 font-medium">Campaña</th><th class="pb-2 font-medium">Estado</th>
              <th class="pb-2 font-medium text-right">Contactados</th><th class="pb-2 font-medium text-right">Respuestas</th>
              <th class="pb-2 font-medium text-right">Tasa</th><th class="pb-2 font-medium text-right">Por aprobar</th><th class="pb-2 font-medium text-right">Pool</th>
            </tr></thead>
            <tbody id="campRows"><tr><td colspan="7" class="py-4 text-center text-slate-400">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
      <!-- Embudo -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Embudo del pipeline</h2>
        <div id="funnel" class="space-y-2.5 text-sm text-slate-400">Cargando…</div>
      </div>
    </div>

    <!-- Actividad -->
    <div class="bg-white rounded-xl ring-1 ring-slate-200">
      <div class="px-4 py-3.5 border-b border-slate-100"><h2 class="text-sm font-semibold text-slate-900">Actividad de campaña</h2></div>
      <ul id="recent" class="divide-y divide-slate-50 text-sm"><li class="px-4 py-3 text-slate-400">Cargando…</li></ul>
    </div>
  </div>
</div>

<script>
var STAGE_ORDER = ['prospecto','contactado','interesado','propuesta','negociacion','ganado','perdido'];
function stageLabel(s){ return {prospecto:'Prospecto',contactado:'Contactado',interesado:'Interesado',propuesta:'Propuesta',negociacion:'Negociación',ganado:'Ganado',perdido:'Perdido',pausado:'Pausado'}[s]||s; }
function stageBar(s){ return {prospecto:'bg-slate-400',contactado:'bg-sky-500',interesado:'bg-violet-500',propuesta:'bg-amber-500',negociacion:'bg-orange-500',ganado:'bg-emerald-500',perdido:'bg-red-400'}[s]||'bg-slate-400'; }
function statusPill(s){ var m={activa:'bg-emerald-50 text-emerald-700',pausada:'bg-amber-50 text-amber-700',finalizada:'bg-slate-100 text-slate-600'}; return '<span class="text-xs px-2 py-0.5 rounded-full '+(m[s]||'bg-slate-100 text-slate-600')+'">'+escapeHtml(s)+'</span>'; }

(async function(){
  var r = await api('api/campaigns.php', { action: 'report_full' });
  if (!r || !r.ok) { document.getElementById('campRows').innerHTML = '<tr><td colspan="7" class="py-4 text-center text-red-500">No se pudo cargar.</td></tr>'; return; }
  var t = r.totals || {};
  document.getElementById('kpi0').textContent = t.contacted ?? 0;
  document.getElementById('kpi1').textContent = t.responses ?? 0;
  document.getElementById('kpi2').textContent = (t.reply_rate ?? 0) + '%';
  document.getElementById('kpi3').textContent = t.pending ?? 0;

  var rows = (r.campaigns || []).map(function(c){
    return '<tr class="border-b border-slate-50">'
      + '<td class="py-2.5 font-medium text-slate-800">' + escapeHtml(c.name) + '<div class="text-xs text-slate-400">' + escapeHtml(c.sector||'') + ' · ' + (c.daily_quota||0) + '/día</div></td>'
      + '<td class="py-2.5">' + statusPill(c.status) + '</td>'
      + '<td class="py-2.5 text-right font-semibold">' + (c.contacted||0) + '</td>'
      + '<td class="py-2.5 text-right font-semibold ' + ((c.responses||0)>0?'text-emerald-600':'text-slate-400') + '">' + (c.responses||0) + '</td>'
      + '<td class="py-2.5 text-right">' + (c.reply_rate||0) + '%</td>'
      + '<td class="py-2.5 text-right text-amber-600">' + (c.pending_approval||0) + '</td>'
      + '<td class="py-2.5 text-right text-slate-500">' + (c.pool||0) + '</td>'
      + '</tr>';
  }).join('');
  document.getElementById('campRows').innerHTML = rows || '<tr><td colspan="7" class="py-4 text-center text-slate-400">Sin campañas.</td></tr>';

  var fc = document.getElementById('funnel'); var fun = r.funnel || {}; var max = 1;
  STAGE_ORDER.forEach(function(k){ if((fun[k]||0)>max) max=fun[k]; });
  var fr = STAGE_ORDER.filter(function(k){ return (fun[k]||0)>0; });
  fc.innerHTML = fr.length ? fr.map(function(k){
    var v=fun[k]||0, pct=Math.max(3,Math.round(v/max*100));
    return '<div><div class="flex items-center justify-between text-xs mb-1"><span class="text-slate-600">'+stageLabel(k)+'</span><span class="font-semibold text-slate-900">'+v+'</span></div>'
      +'<div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full '+stageBar(k)+'" style="width:'+pct+'%"></div></div></div>';
  }).join('') : '<div class="text-slate-400 text-sm">Sin datos.</div>';

  var ul = document.getElementById('recent');
  if (!r.recent || !r.recent.length) { ul.innerHTML = '<li class="px-4 py-4 text-center text-slate-400">Sin actividad de campaña todavía.</li>'; }
  else ul.innerHTML = r.recent.map(function(a){
    var inc = a.direction === 'in';
    return '<li class="px-4 py-2.5">'
      + '<div class="flex items-center justify-between gap-2"><span class="text-xs font-medium text-slate-700">' + escapeHtml(a.nm||'—') + '</span><span class="text-[11px] text-slate-400">' + (a.sent_at||'').substring(5,16) + '</span></div>'
      + '<div class="text-xs ' + (inc?'text-emerald-600':'text-slate-500') + '">' + (inc?'↓ respondió':'↑ '+escapeHtml(a.type)) + ' · ' + escapeHtml((a.subject||a.body||'').substring(0,42)) + '</div>'
      + '</li>';
  }).join('');
})();
</script>
