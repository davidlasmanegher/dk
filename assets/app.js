/* Utilidades compartidas del Agente DK */

window.escapeHtml = function (s) {
  return (s || '').replace(/[&<>"']/g, function(c) {
    return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
  });
};

/* CSRF token: lee el <meta name="csrf-token"> del layout. */
window.csrfToken = function () {
  var m = document.querySelector('meta[name="csrf-token"]');
  return m ? m.getAttribute('content') : '';
};

/* POST JSON a un endpoint; devuelve el JSON parseado. */
window.api = async function (path, data) {
  var headers = { 'Content-Type': 'application/json' };
  var t = window.csrfToken();
  if (t) headers['X-CSRF-Token'] = t;
  var res = await fetch(path, {
    method: 'POST',
    headers: headers,
    credentials: 'same-origin',
    body: JSON.stringify(data || {}),
  });
  if (res.status === 401) {
    window.location.href = 'index.php';
    return { ok: false, error: 'Sesión expirada.' };
  }
  if (res.status === 403) {
    window.toast && window.toast('Sesión desactualizada. Recargando…', 'info');
    setTimeout(function() { window.location.reload(); }, 1800);
    return { ok: false, error: 'Sesión desactualizada.' };
  }
  var json;
  try { json = await res.json(); } catch (e) { json = { ok: false, error: 'Respuesta inválida del servidor.' }; }
  if (!res.ok && json.ok === undefined) json.ok = false;
  return json;
};

/* Notificación tipo toast. */
window.toast = function (msg, type) {
  type = type || 'info';
  var colors = { info: 'bg-slate-900', ok: 'bg-emerald-600', error: 'bg-red-600' };
  var el = document.createElement('div');
  el.className = 'pointer-events-auto px-4 py-3 rounded-lg text-white text-sm shadow-lg ' + (colors[type] || colors.info);
  el.textContent = msg;
  var container = document.getElementById('toastContainer');
  if (container) container.appendChild(el);
  else document.body.appendChild(el);
  setTimeout(function() { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; }, 2600);
  setTimeout(function() { el.remove(); }, 3000);
};

/* Botón con estado de carga. Devuelve función para restaurar. */
window.loading = function (btn, label) {
  label = label || 'Procesando…';
  if (!btn) return function() {};
  var original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<svg class="h-4 w-4 spin inline -mt-0.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 109 9" stroke-linecap="round"/></svg>' + escapeHtml(label);
  return function() { btn.disabled = false; btn.innerHTML = original; };
};

/* Render mínimo de Markdown a HTML. */
window.mdToHtml = function (md) {
  if (!md) return '';
  var lines = md.replace(/\r/g, '').split('\n');
  var html = '', i = 0;
  var inline = function(t) {
    return escapeHtml(t)
      .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">$1</a>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/`(.+?)`/g, '<code>$1</code>');
  };
  while (i < lines.length) {
    var line = lines[i];
    var m;
    if ((m = line.match(/^(#{1,3})\s+(.*)$/))) {
      var lv = m[1].length;
      html += '<h' + lv + ' class="font-semibold mt-4 mb-1">' + inline(m[2]) + '</h' + lv + '>';
      i++; continue;
    }
    if (/^\s*[-*]\s+/.test(line)) {
      html += '<ul class="list-disc pl-5 my-2">';
      while (i < lines.length && /^\s*[-*]\s+/.test(lines[i])) {
        html += '<li>' + inline(lines[i].replace(/^\s*[-*]\s+/, '')) + '</li>';
        i++;
      }
      html += '</ul>'; continue;
    }
    if (/^\s*\d+\.\s+/.test(line)) {
      html += '<ol class="list-decimal pl-5 my-2">';
      while (i < lines.length && /^\s*\d+\.\s+/.test(lines[i])) {
        html += '<li>' + inline(lines[i].replace(/^\s*\d+\.\s+/, '')) + '</li>';
        i++;
      }
      html += '</ol>'; continue;
    }
    if (line.trim() === '') { i++; continue; }
    html += '<p class="my-1">' + inline(line) + '</p>';
    i++;
  }
  return html;
};

/* Cerrar sesión. */
window.dkLogout = async function () {
  try { await api('api/auth.php', { action: 'logout' }); } catch (e) {}
  window.location.href = 'index.php?page=login';
};
