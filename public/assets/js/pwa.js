/** Install-to-home-screen banner (Android/desktop Chrome/Edge). iOS has no beforeinstallprompt event. */
(function () {
  var deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    var dismissed = false;
    try { dismissed = localStorage.getItem('ep_pwa_dismissed') === '1'; } catch (err) {}
    var banner = document.getElementById('pwa-install-banner');
    if (banner && !dismissed) banner.classList.remove('d-none');
  });

  document.addEventListener('DOMContentLoaded', function () {
    var installBtn = document.getElementById('pwa-install-btn');
    var dismissBtn = document.getElementById('pwa-dismiss-btn');
    var banner = document.getElementById('pwa-install-banner');

    if (installBtn) {
      installBtn.addEventListener('click', function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.finally(function () {
          deferredPrompt = null;
          if (banner) banner.classList.add('d-none');
        });
      });
    }

    if (dismissBtn && banner) {
      dismissBtn.addEventListener('click', function () {
        banner.classList.add('d-none');
        try { localStorage.setItem('ep_pwa_dismissed', '1'); } catch (e) {}
      });
    }

    try {
      if (localStorage.getItem('ep_pwa_dismissed') === '1' && banner) {
        banner.classList.add('d-none');
      }
    } catch (e) {}
  });

  window.addEventListener('appinstalled', function () {
    var banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.add('d-none');
  });
})();
