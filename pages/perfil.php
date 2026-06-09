<?php
/** Perfil del agente — configura la "personalidad" de la IA */
try {
    $profile = db()->query("SELECT * FROM agent_profile WHERE id = 1")->fetch();
} catch (Throwable $e) { $profile = []; }
if (!$profile) $profile = [];
?>
<div class="max-w-5xl space-y-5">

  <div class="bg-indigo-50 ring-1 ring-indigo-200 rounded-xl px-5 py-4 text-sm text-indigo-900">
    <strong class="font-semibold">Perfil del Agente</strong> &mdash;
    Esta información se inyecta como contexto en todas las generaciones de IA: mensajes a prospectos,
    propuestas, posts de LinkedIn y tareas autónomas. Cuanto más completo, mejor se comporta el agente.
  </div>

  <div class="grid lg:grid-cols-3 gap-5">

    <!-- Formulario principal -->
    <form id="profileForm" class="lg:col-span-2 bg-white rounded-xl ring-1 ring-slate-200 p-6 space-y-5" onsubmit="saveProfile(event)">

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Nombre</label>
          <input type="text" name="name" value="<?= e($profile['name'] ?? 'Daniel Khan') ?>"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Cargo / Rol</label>
          <input type="text" name="role" value="<?= e($profile['role'] ?? '') ?>"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Empresa</label>
          <input type="text" name="company" value="<?= e($profile['company'] ?? 'SISTEL') ?>"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Mercado objetivo</label>
          <input type="text" name="market_focus" value="<?= e($profile['market_focus'] ?? 'México') ?>"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-medium text-slate-600 mb-1">LinkedIn URL</label>
          <input type="url" name="linkedin_url" value="<?= e($profile['linkedin_url'] ?? '') ?>"
                 placeholder="https://linkedin.com/in/danielkhan"
                 class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500">
        </div>
      </div>

      <div class="rounded-lg bg-amber-50 ring-1 ring-amber-100 p-4">
        <label class="block text-sm font-semibold text-slate-900 mb-1">La persona detrás del rol &mdash; quién es Daniel</label>
        <p class="text-xs text-slate-500 mb-2">Lo que lo hace humano, no solo un vendedor: su historia, sus valores, su forma de ser, qué lo mueve, cómo construye confianza. Cuanto más real, más auténtico suena en cada conversación.</p>
        <textarea name="persona" rows="10"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-indigo-500 resize-y"
                  placeholder="• Historia: de dónde viene Daniel, su recorrido, cómo llegó a SISTEL.&#10;• Valores y propósito: en qué cree sobre el aprendizaje y las personas, qué lo mueve.&#10;• Personalidad: cómo es en una charla (cálido, directo, curioso), su energía, su humor.&#10;• Cómo construye relación: cómo escucha, qué pregunta, cómo genera confianza.&#10;• Intereses / anécdotas: temas que le apasionan, referencias que lo hacen cercano.&#10;• Su 'por qué': la misión personal detrás de lo que hace."><?= e($profile['persona'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Cliente ideal — Target Market</label>
        <p class="text-xs text-slate-400 mb-1.5">¿A quién le vendes? Describe la industria, cargo, tamaño de empresa y dolor específico.</p>
        <textarea name="target_market" rows="4"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"><?= e($profile['target_market'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Propuesta de valor</label>
        <p class="text-xs text-slate-400 mb-1.5">¿Qué vende SISTEL? ¿Por qué es diferente? ¿Qué resultado concreto genera?</p>
        <textarea name="value_proposition" rows="4"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"><?= e($profile['value_proposition'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Estilo de comunicación</label>
        <p class="text-xs text-slate-400 mb-1.5">¿Cómo habla Daniel? Tono, formalidad, frases clave, lo que NO haría.</p>
        <textarea name="communication_style" rows="3"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"><?= e($profile['communication_style'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Manejo de objeciones</label>
        <p class="text-xs text-slate-400 mb-1.5">Lista las objeciones más frecuentes y cómo responderlas. Formato: "Objeción: X. Respuesta: Y." separadas por ||</p>
        <textarea name="objections_playbook" rows="5"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"><?= e($profile['objections_playbook'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Clientes para prueba social</label>
        <p class="text-xs text-slate-400 mb-1.5">Las ÚNICAS marcas que Daniel puede nombrar como clientes de SISTEL. No inventará otras. Separá por comas.</p>
        <textarea name="social_proof" rows="2"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none"><?= e($profile['social_proof'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Firma para emails</label>
        <textarea name="signature" rows="3"
                  class="w-full rounded-lg ring-1 ring-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 resize-none font-mono text-xs"
                  placeholder="Daniel Khan | Consultor Sr. en Aprendizaje Corporativo | SISTEL"><?= e($profile['signature'] ?? '') ?></textarea>
      </div>

      <div class="pt-2 flex items-center gap-3">
        <button type="submit" id="saveProfileBtn"
                class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
          Guardar perfil
        </button>
        <span id="saveMsg" class="hidden text-sm text-emerald-600 font-medium">Guardado.</span>
      </div>
    </form>

    <!-- Panel derecho: preview -->
    <div class="space-y-4">
      <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Cómo usa la IA este perfil</h3>
        <ul class="space-y-2.5 text-sm text-slate-600">
          <li class="flex items-start gap-2">
            <svg class="h-4 w-4 text-indigo-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span><strong>Mensajes a prospectos:</strong> genera el primer contacto adaptado al cargo y empresa del lead.</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="h-4 w-4 text-indigo-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span><strong>Emails de seguimiento:</strong> mantiene el tono de Daniel y respeta su forma de hablar.</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="h-4 w-4 text-indigo-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span><strong>Posts de LinkedIn:</strong> construye autoridad en el mercado mexicano de L&amp;D.</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="h-4 w-4 text-indigo-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span><strong>Objeciones:</strong> cuando el agente detecta una objeción en el historial, responde usando el playbook configurado.</span>
          </li>
        </ul>
      </div>

      <div class="bg-slate-800 rounded-xl p-5 text-xs font-mono text-slate-300 leading-relaxed">
        <div class="text-slate-500 mb-2">// System prompt (fragmento)</div>
        <div id="profilePreview">
          Eres el asistente de ventas de <span class="text-indigo-400"><?= e($profile['name'] ?? 'Daniel Khan') ?></span>,
          <?= e($profile['role'] ?? '') ?> en <?= e($profile['company'] ?? 'SISTEL') ?>.
          Mercado objetivo: <span class="text-emerald-400"><?= e(truncate($profile['target_market'] ?? '', 120)) ?></span>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
async function saveProfile(e) {
  e.preventDefault();
  var btn = document.getElementById('saveProfileBtn');
  var restore = loading(btn, 'Guardando…');
  var form = document.getElementById('profileForm');
  var data = { action: 'save_profile' };
  new FormData(form).forEach(function(v, k) { data[k] = v; });
  var r = await api('api/agent.php', data);
  restore();
  if (r && r.ok) {
    var msg = document.getElementById('saveMsg');
    msg.classList.remove('hidden');
    setTimeout(function() { msg.classList.add('hidden'); }, 2000);
    toast('Perfil guardado.', 'ok');
  } else {
    toast(r.error || 'Error al guardar.', 'error');
  }
}
</script>
