/** Install-to-home-screen banner (Android/desktop Chrome/Edge), plus a manual instructions banner for iOS Safari (no beforeinstallprompt event there). Never shown once already running as an installed app. */
(function () {
  var deferredPrompt = null;

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent) && !window.MSStream;
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    if (isStandalone()) return;
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
    var iosBanner = document.getElementById('pwa-ios-banner');
    var iosDismissBtn = document.getElementById('pwa-ios-dismiss-btn');

    var dismissed = false;
    try { dismissed = localStorage.getItem('ep_pwa_dismissed') === '1'; } catch (e) {}

    if (!isStandalone() && !dismissed && isIos() && iosBanner) {
      iosBanner.classList.remove('d-none');
    }

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

    if (iosDismissBtn && iosBanner) {
      iosDismissBtn.addEventListener('click', function () {
        iosBanner.classList.add('d-none');
        try { localStorage.setItem('ep_pwa_dismissed', '1'); } catch (e) {}
      });
    }
  });

  window.addEventListener('appinstalled', function () {
    var banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.add('d-none');
  });
})();
