    </main>
  </div>
</div>

<!-- Toast container (los toasts se insertan aquí via JS) -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

<!-- PWA: registro del service worker (offline + instalable) -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('sw.js', { scope: './' }).catch(function () {});
  });
}
</script>
</body>
</html>
