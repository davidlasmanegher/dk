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

  <!-- LinkedIn -->
  <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">LinkedIn</h2>
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
               placeholder="urn:li:person:XXXX"
               class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
      </div>
      <button onclick="saveGroup('linkedin')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
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
  linkedin:['linkedin_token', 'linkedin_author_urn'],
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
