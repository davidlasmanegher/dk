<?php
/** Chat con Daniel — copiloto conversacional (pantalla principal). */
?>
<div class="flex flex-col max-w-3xl mx-auto" style="height:calc(100vh - 8.5rem)">

  <div id="chatMsgs" class="flex-1 overflow-y-auto space-y-4 pb-4 pr-1">
    <div class="text-center text-sm text-slate-400 py-10" id="chatEmpty">
      <div class="h-14 w-14 rounded-2xl bg-indigo-600 text-white grid place-items-center font-bold text-xl mx-auto mb-3">DK</div>
      <p class="font-medium text-slate-600">Hola, soy Daniel.</p>
      <p>Pedime lo que necesites: revisar el pipeline, buscar leads, redactar un mensaje o un post, inscribir a alguien en una secuencia…</p>
    </div>
  </div>

  <div id="chatChips" class="flex flex-wrap gap-2 mb-3">
    <button class="chip" onclick="quick('¿Cómo está el pipeline hoy?')">¿Cómo está el pipeline?</button>
    <button class="chip" onclick="quick('Mostrame los 5 leads más prioritarios')">Top 5 prioritarios</button>
    <button class="chip" onclick="quick('Buscá leads de RH en empresas grandes')">Leads de RH</button>
    <button class="chip" onclick="quick('Redactá un post de LinkedIn sobre rotación y onboarding')">Redactá un post</button>
  </div>

  <form onsubmit="return sendMsg(event)" class="flex items-end gap-2 bg-white rounded-2xl ring-1 ring-slate-200 p-2 shadow-sm">
    <textarea id="chatInput" rows="1" placeholder="Escribile a Daniel…"
              class="flex-1 resize-none border-0 focus:ring-0 text-sm px-2 py-2 max-h-32 focus:outline-none"
              oninput="autoGrow(this)" onkeydown="if(event.key==='Enter'&&!event.shiftKey){sendMsg(event);}"></textarea>
    <button id="chatSend" type="submit" class="h-9 w-9 rounded-xl bg-indigo-600 text-white grid place-items-center hover:bg-indigo-700 transition shrink-0">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
    </button>
  </form>
  <p class="text-center text-[11px] text-slate-400 mt-2">Daniel prepara y consulta; lo que sale a un cliente queda en borrador para tu aprobación.</p>
</div>

<style>
  .chip { font-size: 12px; padding: 6px 12px; border-radius: 9999px; background:#fff; border:1px solid #e2e8f0; color:#475569; transition:.15s; }
  .chip:hover { background:#f8fafc; border-color:#c7d2fe; color:#4338ca; }
  .spin2 { animation: spin 1s linear infinite; }
</style>

<script>
var chatBusy = false;

function autoGrow(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,128)+'px'; }
function quick(t){ var i=document.getElementById('chatInput'); i.value=t; autoGrow(i); i.focus(); }

function bubble(role, text, steps) {
  var wrap = document.createElement('div');
  if (role === 'user') {
    wrap.className = 'flex justify-end';
    wrap.innerHTML = '<div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 text-sm whitespace-pre-line">' + escapeHtml(text) + '</div>';
  } else {
    var stepHtml = '';
    if (steps && steps.length) {
      var names = steps.map(function(s){ return ({buscar_leads:'consultó leads',resumen_pipeline:'revisó el pipeline',ver_lead:'abrió un lead',buscar_conocimiento:'buscó en los casos',guardar_contenido:'guardó contenido',guardar_borrador_outreach:'guardó un borrador',inscribir_secuencia:'inscribió en secuencia'})[s.tool] || s.tool; });
      stepHtml = '<div class="text-[11px] text-slate-400 mt-1 pl-1">Acciones: ' + escapeHtml(names.join(' · ')) + '</div>';
    }
    wrap.className = 'flex gap-2.5';
    wrap.innerHTML = '<div class="h-8 w-8 rounded-lg bg-indigo-600 text-white grid place-items-center font-bold text-xs shrink-0 mt-0.5">DK</div>'
      + '<div class="max-w-[85%]"><div class="bg-white ring-1 ring-slate-200 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm text-slate-700">' + mdToHtml(text) + '</div>' + stepHtml + '</div>';
  }
  return wrap;
}

function scrollDown(){ var c=document.getElementById('chatMsgs'); c.scrollTop=c.scrollHeight; }

async function loadHistory() {
  var r = await api('api/chat.php', { action: 'history' });
  if (!r || !r.ok || !r.messages || !r.messages.length) return;
  document.getElementById('chatEmpty').classList.add('hidden');
  document.getElementById('chatChips').classList.add('hidden');
  var c = document.getElementById('chatMsgs');
  r.messages.forEach(function(m){ c.appendChild(bubble(m.role, m.content, null)); });
  scrollDown();
}

async function sendMsg(e) {
  if (e) e.preventDefault();
  if (chatBusy) return false;
  var input = document.getElementById('chatInput');
  var text = input.value.trim();
  if (!text) return false;
  chatBusy = true;
  document.getElementById('chatEmpty').classList.add('hidden');
  document.getElementById('chatChips').classList.add('hidden');
  var c = document.getElementById('chatMsgs');
  c.appendChild(bubble('user', text, null));
  input.value = ''; autoGrow(input);

  var thinking = document.createElement('div');
  thinking.className = 'flex gap-2.5';
  thinking.innerHTML = '<div class="h-8 w-8 rounded-lg bg-indigo-600 text-white grid place-items-center font-bold text-xs shrink-0">DK</div>'
    + '<div class="bg-white ring-1 ring-slate-200 rounded-2xl rounded-tl-sm px-4 py-3 text-sm text-slate-400"><svg class="h-4 w-4 spin2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 109 9" stroke-linecap="round"/></svg> Daniel está trabajando…</div>';
  c.appendChild(thinking); scrollDown();

  var r = await api('api/chat.php', { action: 'send', message: text });
  thinking.remove();
  if (r && r.ok) { c.appendChild(bubble('assistant', r.reply, r.steps)); }
  else { c.appendChild(bubble('assistant', (r && r.error) ? r.error : 'No pude procesar eso.', null)); }
  scrollDown();
  chatBusy = false;
  return false;
}

loadHistory();
</script>
