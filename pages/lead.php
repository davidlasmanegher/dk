<?php
/** Detalle de un lead */
$lead_id = (int)($_GET['id'] ?? 0);
if (!$lead_id) { header('Location: index.php?page=leads'); exit; }

try {
    $lead = db()->prepare("SELECT * FROM leads WHERE id = ?");
    $lead->execute([$lead_id]);
    $leadData = $lead->fetch();
} catch (Throwable $e) { $leadData = null; }

if (!$leadData) { echo '<div class="text-center py-12 text-slate-500">Lead no encontrado.</div>'; return; }
?>
<div class="space-y-5 max-w-5xl">

  <!-- Header -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5 flex items-start justify-between gap-4">
    <div class="flex items-center gap-4">
      <div class="h-14 w-14 rounded-xl bg-indigo-100 text-indigo-700 grid place-items-center font-bold text-xl shrink-0">
        <?= e(mb_strtoupper(mb_substr($leadData['first_name'], 0, 1) . mb_substr($leadData['last_name'] ?? '', 0, 1))) ?>
      </div>
      <div>
        <h2 class="text-lg font-bold text-slate-900">
          <?= e($leadData['first_name'] . ' ' . ($leadData['last_name'] ?? '')) ?>
        </h2>
        <p class="text-sm text-slate-500">
          <?= e($leadData['role'] ?? '') ?>
          <?php if ($leadData['company']): ?> &middot; <?= e($leadData['company']) ?><?php endif; ?>
        </p>
        <div class="flex items-center gap-2 mt-1.5">
          <?php
          $stages = require_once('/dev/null') ?: null;
          // Inline stage badge
          $stageBadges = ['prospecto'=>'bg-slate-100 text-slate-600 ring-slate-200','contactado'=>'bg-sky-50 text-sky-700 ring-sky-200','interesado'=>'bg-violet-50 text-violet-700 ring-violet-200','propuesta'=>'bg-amber-50 text-amber-700 ring-amber-200','negociacion'=>'bg-orange-50 text-orange-700 ring-orange-200','ganado'=>'bg-emerald-50 text-emerald-700 ring-emerald-200','perdido'=>'bg-red-50 text-red-600 ring-red-200','pausado'=>'bg-slate-100 text-slate-400 ring-slate-200'];
          $stageLabels = ['prospecto'=>'Prospecto','contactado'=>'Contactado','interesado'=>'Interesado','propuesta'=>'Propuesta','negociacion'=>'Negociación','ganado'=>'Ganado','perdido'=>'Perdido','pausado'=>'Pausado'];
          $sb = $stageBadges[$leadData['stage']] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
          $sl = $stageLabels[$leadData['stage']] ?? ucfirst($leadData['stage']);
          ?>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 <?= $sb ?>"><?= e($sl) ?></span>
          <?php if ($leadData['industry']): ?>
            <span class="text-xs text-slate-400"><?= e($leadData['industry']) ?></span>
          <?php endif; ?>
          <span class="text-xs font-semibold text-slate-700">Score: <?= (int)$leadData['score'] ?></span>
        </div>
      </div>
    </div>
    <div class="flex gap-2 shrink-0">
      <?php if ($leadData['linkedin_url']): ?>
        <a href="<?= e($leadData['linkedin_url']) ?>" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg ring-1 ring-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
          LinkedIn
        </a>
      <?php endif; ?>
      <button onclick="editLead()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16M6 16l9-9 3 3-9 9H6v-3z"/></svg>
        Editar
      </button>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">

    <!-- Actividades -->
    <div class="lg:col-span-2 space-y-4">

      <!-- Nueva actividad -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Registrar actividad</h3>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
            <select id="act_type" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
              <option value="email">Email</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="linkedin">LinkedIn</option>
              <option value="llamada">Llamada</option>
              <option value="reunion">Reunion</option>
              <option value="nota">Nota interna</option>
              <option value="propuesta">Propuesta</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dirección</label>
            <select id="act_direction" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
              <option value="out">Saliente (yo)</option>
              <option value="in">Entrante (lead)</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="block text-xs font-medium text-slate-600 mb-1">Asunto / Titulo</label>
          <input type="text" id="act_subject" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500" placeholder="Ej: Primer contacto vía LinkedIn">
        </div>
        <div class="mb-3">
          <label class="block text-xs font-medium text-slate-600 mb-1">Cuerpo / Notas</label>
          <textarea id="act_body" rows="4" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none" placeholder="Contenido del mensaje o resumen de la llamada…"></textarea>
        </div>
        <div class="flex items-center gap-2">
          <button id="saveActBtn" onclick="saveActivity()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            Guardar actividad
          </button>
          <button onclick="generateMessage()" class="px-4 py-2 rounded-lg ring-1 ring-indigo-300 text-indigo-700 text-sm font-medium hover:bg-indigo-50 transition flex items-center gap-1.5">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Generar con IA
          </button>
        </div>
      </div>

      <!-- Historial -->
      <div class="bg-white rounded-xl ring-1 ring-slate-200">
        <div class="px-5 py-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-slate-900">Historial de actividades</h3>
        </div>
        <div id="activitiesList" class="divide-y divide-slate-50">
          <div class="px-5 py-4 text-sm text-slate-400 text-center">Cargando…</div>
        </div>
      </div>
    </div>

    <!-- Panel derecho: info del lead -->
    <div class="space-y-4">
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Información de contacto</h3>
        <dl class="space-y-3 text-sm">
          <?php if ($leadData['email']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">Email</dt>
              <dd class="mt-0.5 text-slate-900"><?= e($leadData['email']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($leadData['phone']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">Teléfono</dt>
              <dd class="mt-0.5 text-slate-900"><?= e($leadData['phone']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($leadData['whatsapp_phone']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">WhatsApp</dt>
              <dd class="mt-0.5 text-slate-900"><?= e($leadData['whatsapp_phone']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($leadData['city'] || $leadData['country']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">Ubicación</dt>
              <dd class="mt-0.5 text-slate-900"><?= e(trim($leadData['city'] . ', ' . $leadData['country'], ', ')) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($leadData['company_size']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">Tamaño empresa</dt>
              <dd class="mt-0.5 text-slate-900"><?= e($leadData['company_size']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($leadData['source']): ?>
            <div>
              <dt class="text-xs font-medium text-slate-500">Fuente</dt>
              <dd class="mt-0.5 text-slate-900"><?= e($leadData['source']) ?></dd>
            </div>
          <?php endif; ?>
        </dl>
      </div>

      <?php if ($leadData['next_action'] || $leadData['next_action_date']): ?>
        <div class="bg-amber-50 ring-1 ring-amber-200 rounded-xl p-4">
          <h3 class="text-xs font-semibold text-amber-800 mb-2">Proxima acción</h3>
          <?php if ($leadData['next_action']): ?>
            <p class="text-sm text-amber-900"><?= e($leadData['next_action']) ?></p>
          <?php endif; ?>
          <?php if ($leadData['next_action_date']): ?>
            <p class="text-xs text-amber-700 mt-1"><?= e($leadData['next_action_date']) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($leadData['notes']): ?>
        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
          <h3 class="text-sm font-semibold text-slate-900 mb-2">Notas</h3>
          <p class="text-sm text-slate-600 leading-relaxed"><?= e($leadData['notes']) ?></p>
        </div>
      <?php endif; ?>

      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Generar contenido para este lead</h3>
        <a href="index.php?page=contenido&lead_id=<?= $leadData['id'] ?>"
           class="block text-center px-4 py-2.5 rounded-lg ring-1 ring-indigo-300 text-indigo-700 text-sm font-medium hover:bg-indigo-50 transition">
          Crear mensaje / propuesta
        </a>
      </div>
    </div>
  </div>
</div>

<script>
var leadId = <?= (int)$leadData['id'] ?>;

async function loadActivities() {
  var r = await api('api/leads.php', { action: 'list_activities', lead_id: leadId });
  var container = document.getElementById('activitiesList');
  if (!r || !r.ok || !r.activities || r.activities.length === 0) {
    container.innerHTML = '<div class="px-5 py-6 text-center text-sm text-slate-400">Sin actividades registradas.</div>';
    return;
  }
  var typeIcon = {
    email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    whatsapp: 'M3 12a9 9 0 1018 0 9 9 0 00-18 0zM12 8v4l3 2',
    linkedin: 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z',
    llamada: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    reunion: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    nota: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    propuesta: 'M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v4h4',
  };
  container.innerHTML = r.activities.map(function(a) {
    var icon = typeIcon[a.type] || 'M9 12h6m-6 4h6';
    var dirColor = a.direction === 'out' ? 'text-indigo-600' : 'text-emerald-600';
    return '<div class="px-5 py-4">'
      + '<div class="flex items-start gap-3">'
      + '<div class="h-8 w-8 rounded-full bg-slate-100 grid place-items-center shrink-0 mt-0.5">'
      + '<svg class="h-4 w-4 ' + dirColor + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="' + icon + '"/></svg>'
      + '</div>'
      + '<div class="flex-1 min-w-0">'
      + '<div class="flex items-center justify-between gap-2">'
      + '<span class="text-sm font-medium text-slate-900">' + escapeHtml(a.subject || a.type) + '</span>'
      + '<span class="text-xs text-slate-400 shrink-0">' + (a.sent_at || '').substring(0,16) + '</span>'
      + '</div>'
      + (a.body ? '<p class="text-sm text-slate-600 mt-1 line-clamp-3">' + escapeHtml(a.body) + '</p>' : '')
      + '</div>'
      + '</div>'
      + '</div>';
  }).join('');
}

async function saveActivity() {
  var btn = document.getElementById('saveActBtn');
  var restore = loading(btn, 'Guardando…');
  var r = await api('api/leads.php', {
    action:    'add_activity',
    lead_id:   leadId,
    type:      document.getElementById('act_type').value,
    direction: document.getElementById('act_direction').value,
    subject:   document.getElementById('act_subject').value.trim(),
    body:      document.getElementById('act_body').value.trim(),
  });
  restore();
  if (r && r.ok) {
    toast('Actividad registrada.', 'ok');
    document.getElementById('act_subject').value = '';
    document.getElementById('act_body').value    = '';
    loadActivities();
  } else {
    toast(r.error || 'Error al guardar.', 'error');
  }
}

async function generateMessage() {
  var btn = event.target.closest('button');
  var restore = loading(btn, 'Generando…');
  var r = await api('api/content.php', {
    action:  'generate',
    type:    'mensaje_prospecto',
    lead_id: leadId,
  });
  restore();
  if (r && r.ok && r.body) {
    document.getElementById('act_body').value    = r.body;
    document.getElementById('act_subject').value = r.title || 'Mensaje generado con IA';
    toast('Mensaje generado.', 'ok');
  } else {
    toast(r.error || 'Error al generar.', 'error');
  }
}

function editLead() {
  window.location.href = 'index.php?page=leads';
}

loadActivities();
</script>
