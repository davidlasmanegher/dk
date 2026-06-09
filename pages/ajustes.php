<?php
/** Ajustes: API keys y configuración del sistema */
?>
<div class="max-w-3xl space-y-5">

  <!-- Claude -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">Claude (Anthropic)</h2>
    <div class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">API Key de Claude</label>
        <div class="flex gap-2">
          <input type="password" id="claude_api_key" value="<?= e(setting('claude_api_key', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-indigo-500"
                 placeholder="sk-ant-…">
          <button onclick="toggleVis(this)" data-target="claude_api_key"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Modelo</label>
        <select id="claude_model" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <?php
          $models = ['claude-sonnet-4-5', 'claude-opus-4-5', 'claude-haiku-4-5', 'claude-sonnet-4-6'];
          $current = setting('claude_model', 'claude-sonnet-4-5');
          foreach ($models as $m): ?>
            <option value="<?= e($m) ?>" <?= $m === $current ? 'selected' : '' ?>><?= e($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button onclick="saveGroup('claude')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- OpenAI -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">OpenAI (imágenes)</h2>
    <div class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">API Key de OpenAI</label>
        <div class="flex gap-2">
          <input type="password" id="openai_api_key" value="<?= e(setting('openai_api_key', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-indigo-500"
                 placeholder="sk-…">
          <button onclick="toggleVis(this)" data-target="openai_api_key"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
      <button onclick="saveGroup('openai')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- Whapi -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">WhatsApp (Whapi)</h2>
    <div class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Token Whapi</label>
        <div class="flex gap-2">
          <input type="password" id="whapi_token" value="<?= e(setting('whapi_token', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-indigo-500">
          <button onclick="toggleVis(this)" data-target="whapi_token"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">URL de instancia</label>
        <input type="url" id="whapi_instance_url" value="<?= e(setting('whapi_instance_url', 'https://gate.whapi.cloud')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Teléfono propietario (con código de país)</label>
        <input type="tel" id="whapi_owner_phone" value="<?= e(setting('whapi_owner_phone', '')) ?>"
               placeholder="+52 55 1234 5678"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <button onclick="saveGroup('whapi')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- SMTP -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">Correo (SMTP)</h2>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Host SMTP</label>
        <input type="text" id="smtp_host" value="<?= e(setting('smtp_host', '')) ?>"
               placeholder="smtp.gmail.com"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Puerto</label>
        <input type="text" id="smtp_port" value="<?= e(setting('smtp_port', '587')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Usuario SMTP</label>
        <input type="text" id="smtp_user" value="<?= e(setting('smtp_user', '')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña SMTP</label>
        <div class="flex gap-2">
          <input type="password" id="smtp_pass" value="<?= e(setting('smtp_pass', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <button onclick="toggleVis(this)" data-target="smtp_pass"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nombre remitente</label>
        <input type="text" id="smtp_from_name" value="<?= e(setting('smtp_from_name', 'Daniel Khan · SISTEL')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Email remitente</label>
        <input type="email" id="smtp_from_email" value="<?= e(setting('smtp_from_email', '')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
    </div>
    <div class="mt-4">
      <button onclick="saveGroup('smtp')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- Bandeja de entrada: correo IMAP + webhook WhatsApp -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">Bandeja de entrada</h2>
    <p class="text-xs text-slate-500 mb-4">Correo entrante por IMAP y recepción de WhatsApp por webhook.</p>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Host IMAP</label>
        <input type="text" id="imap_host" value="<?= e(setting('imap_host', '')) ?>"
               placeholder="outlook.office365.com"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Puerto IMAP</label>
        <input type="text" id="imap_port" value="<?= e(setting('imap_port', '993')) ?>"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Usuario IMAP (correo)</label>
        <input type="text" id="imap_user" value="<?= e(setting('imap_user', '')) ?>"
               placeholder="daniel.khan@sistel.co"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña IMAP (o app password)</label>
        <div class="flex gap-2">
          <input type="password" id="imap_pass" value="<?= e(setting('imap_pass', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <button onclick="toggleVis(this)" data-target="imap_pass"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
    </div>
    <div class="mt-4">
      <label class="block text-xs font-medium text-slate-600 mb-1">Token del webhook de WhatsApp</label>
      <div class="flex gap-2">
        <input type="text" id="whapi_webhook_token" value="<?= e(setting('whapi_webhook_token', '')) ?>"
               class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-indigo-500">
      </div>
      <p class="text-xs text-slate-400 mt-1.5">Pegá esta URL en tu cuenta Whapi (Settings → Webhooks):<br>
        <code class="text-slate-600">https://www.sisteltools.com/dk/api/whapi_webhook.php?token=<span id="webhookTokenEcho"><?= e(setting('whapi_webhook_token', 'TU_TOKEN')) ?></span></code></p>
    </div>
    <div class="mt-4">
      <button onclick="saveGroup('inbox')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- LinkedIn -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">LinkedIn</h2>
    <p class="text-xs text-slate-500 mb-4">Publicación en la página de SISTEL México vía API oficial. Requiere una LinkedIn App con permiso de organización (<code class="text-slate-600">w_organization_social</code>).</p>
    <div class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Access Token</label>
        <div class="flex gap-2">
          <input type="password" id="linkedin_token" value="<?= e(setting('linkedin_token', '')) ?>"
                 class="flex-1 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-indigo-500">
          <button onclick="toggleVis(this)" data-target="linkedin_token"
                  class="px-3 py-2 rounded-lg ring-1 ring-slate-300 text-xs text-slate-600 hover:bg-slate-50 shrink-0">Mostrar</button>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Author URN</label>
        <input type="text" id="linkedin_author_urn" value="<?= e(setting('linkedin_author_urn', '')) ?>"
               placeholder="urn:li:organization:XXXX"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        <p class="text-xs text-slate-400 mt-1.5">Para publicar en la página de SISTEL México: <code class="text-slate-600">urn:li:organization:</code> + el ID numérico de la página. Para tu perfil personal: <code class="text-slate-600">urn:li:person:</code> + tu ID.</p>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Versión de la API (AAAAMM)</label>
        <input type="text" id="linkedin_version" value="<?= e(setting('linkedin_version', '202506')) ?>"
               class="w-40 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        <p class="text-xs text-slate-400 mt-1.5">Si LinkedIn rechaza por versión, poné la vigente que aparece en tu portal de desarrollador.</p>
      </div>
      <button onclick="saveGroup('linkedin')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- Firma de los correos -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">Firma de los correos</h2>
    <p class="text-xs text-slate-500 mb-4">Firma HTML (sin imágenes) al pie de cada correo de Daniel. Editá los datos y se actualiza en todos los envíos.</p>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Nombre</label>
        <input type="text" id="signature_name" value="<?= e(setting('signature_name', 'Daniel Khan')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Cargo</label>
        <input type="text" id="signature_role" value="<?= e(setting('signature_role', 'Consultor Sr. en Aprendizaje Corporativo')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
        <input type="text" id="signature_email" value="<?= e(setting('signature_email', '')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Teléfono</label>
        <input type="text" id="signature_phone" value="<?= e(setting('signature_phone', '')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Sitio web</label>
        <input type="text" id="signature_web" value="<?= e(setting('signature_web', '')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">LinkedIn (texto o URL)</label>
        <input type="text" id="signature_linkedin" value="<?= e(setting('signature_linkedin', '')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div class="col-span-2">
        <label class="block text-xs font-medium text-slate-600 mb-1">Dirección</label>
        <input type="text" id="signature_address" value="<?= e(setting('signature_address', '')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Empresa</label>
        <input type="text" id="signature_company" value="<?= e(setting('signature_company', 'SISTEL')) ?>" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Sello "Empresa B"</label>
        <select id="signature_bcorp" class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
          <option value="1" <?= setting('signature_bcorp', '1') === '1' ? 'selected' : '' ?>>Mostrar</option>
          <option value="0" <?= setting('signature_bcorp', '1') === '0' ? 'selected' : '' ?>>Ocultar</option>
        </select>
      </div>
    </div>
    <div class="mt-4">
      <button onclick="saveGroup('firma')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

  <!-- Agente autónomo -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">Agente autónomo</h2>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-medium text-slate-900">Modo automático</div>
          <div class="text-xs text-slate-500 mt-0.5">Permite que el agente ejecute tareas programadas sin intervención manual.</div>
        </div>
        <button id="autoModeToggle" onclick="toggleAutoMode()"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition <?= setting('agent_auto_mode', '0') === '1' ? 'bg-indigo-600' : 'bg-slate-200' ?>">
          <span id="autoModeKnob" class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition <?= setting('agent_auto_mode', '0') === '1' ? 'translate-x-6' : 'translate-x-1' ?>"></span>
        </button>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Límite de tareas por día</label>
        <input type="number" id="agent_daily_limit" min="1" max="100" value="<?= e(setting('agent_daily_limit', '20')) ?>"
               class="w-32 rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <button onclick="saveGroup('agent')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
        Guardar
      </button>
    </div>
  </div>

</div>

<script>
function toggleVis(btn) {
  var input = document.getElementById(btn.dataset.target);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'Ocultar';
  } else {
    input.type = 'password';
    btn.textContent = 'Mostrar';
  }
}

var autoMode = <?= setting('agent_auto_mode', '0') === '1' ? 'true' : 'false' ?>;
function toggleAutoMode() {
  autoMode = !autoMode;
  var toggle = document.getElementById('autoModeToggle');
  var knob   = document.getElementById('autoModeKnob');
  toggle.className = 'relative inline-flex h-6 w-11 items-center rounded-full transition ' + (autoMode ? 'bg-indigo-600' : 'bg-slate-200');
  knob.className   = 'inline-block h-4 w-4 transform rounded-full bg-white shadow transition ' + (autoMode ? 'translate-x-6' : 'translate-x-1');
}

var groups = {
  claude:  ['claude_api_key', 'claude_model'],
  openai:  ['openai_api_key'],
  whapi:   ['whapi_token', 'whapi_instance_url', 'whapi_owner_phone'],
  smtp:    ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email'],
  inbox:   ['imap_host', 'imap_port', 'imap_user', 'imap_pass', 'whapi_webhook_token'],
  linkedin:['linkedin_token', 'linkedin_author_urn', 'linkedin_version'],
  firma:   ['signature_name','signature_role','signature_email','signature_phone','signature_web','signature_linkedin','signature_address','signature_company','signature_bcorp'],
  agent:   ['agent_daily_limit'],
};

async function saveGroup(group) {
  var keys = groups[group];
  if (!keys) return;
  var btn = event.target;
  var restore = loading(btn, 'Guardando…');
  var data = { action: 'save_settings' };
  keys.forEach(function(k) {
    var el = document.getElementById(k);
    if (el) data[k] = el.value;
  });
  if (group === 'agent') {
    data['agent_auto_mode'] = autoMode ? '1' : '0';
  }
  var r = await api('api/agent.php', data);
  restore();
  if (r && r.ok) toast('Ajustes guardados.', 'ok');
  else toast(r.error || 'Error al guardar.', 'error');
}
</script>
