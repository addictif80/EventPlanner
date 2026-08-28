/**
 * Reusable notification bell: polling feed + mark-read + Web Push opt-in.
 * One instance is wired up per layout (staff app, super admin, client
 * portal) with different endpoint bases — see EventPlannerNotifications.init().
 */
(function () {
  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
    return output;
  }

  function csrfToken(root) {
    var input = root.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
  }

  function icon(type) {
    var map = {
      quote: 'bi-file-earmark-text',
      invoice: 'bi-receipt',
      payment: 'bi-cash-coin',
      message: 'bi-chat-dots',
      ticket: 'bi-life-preserver',
      alert: 'bi-exclamation-triangle',
      rgpd: 'bi-shield-lock',
      system: 'bi-gear',
    };
    return map[type] || 'bi-bell';
  }

  function timeAgo(iso) {
    var diff = (Date.now() - new Date(iso.replace(' ', 'T') + 'Z').getTime()) / 1000;
    if (diff < 60) return "à l'instant";
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    return Math.floor(diff / 86400) + ' j';
  }

  function init(config) {
    var root = document.getElementById('notif-root');
    if (!root) return;

    var toggle = root.querySelector('#notif-toggle');
    var panel = root.querySelector('#notif-panel');
    var badge = root.querySelector('#notif-badge');
    var list = root.querySelector('#notif-list');
    var markAllForm = root.querySelector('#notif-mark-all');

    function render(data) {
      if (data.unread > 0) {
        badge.textContent = data.unread > 9 ? '9+' : data.unread;
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }

      list.innerHTML = '';
      if (data.items.length === 0) {
        list.innerHTML = '<div class="text-center text-muted small py-4">Aucune notification.</div>';
        return;
      }

      data.items.forEach(function (n) {
        var a = document.createElement('a');
        a.href = n.link || '#';
        a.className = 'dropdown-item d-flex gap-2 py-2 border-bottom' + (n.is_read ? '' : ' bg-light');
        a.innerHTML =
          '<i class="bi ' + icon(n.type) + ' mt-1 text-primary"></i>' +
          '<div class="flex-grow-1"><div class="small fw-semibold">' + n.title + '</div>' +
          '<div class="small text-muted">' + n.message + '</div>' +
          '<div class="small text-muted">' + timeAgo(n.created_at) + '</div></div>';
        a.addEventListener('click', function () {
          if (!n.is_read) {
            fetch(config.markReadUrl.replace('__ID__', n.id), {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'csrf_token=' + encodeURIComponent(csrfToken(root)),
            });
          }
        });
        list.appendChild(a);
      });
    }

    function poll() {
      fetch(config.feedUrl).then(function (r) { return r.json(); }).then(render).catch(function () {});
    }

    if (toggle && panel) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        panel.classList.toggle('show');
        if (panel.classList.contains('show')) poll();
      });
      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) panel.classList.remove('show');
      });
    }

    if (markAllForm) {
      markAllForm.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch(config.markAllReadUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'csrf_token=' + encodeURIComponent(csrfToken(root)),
        }).then(poll);
      });
    }

    poll();
    setInterval(poll, 20000);

    if (config.pushSubscribeUrl && 'serviceWorker' in navigator && 'PushManager' in window) {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
      navigator.serviceWorker.ready.then(function (registration) {
        registration.pushManager.getSubscription().then(function (existing) {
          if (existing) return;
          if (Notification.permission === 'denied') return;

          var enableBtn = root.querySelector('#notif-enable-push');
          if (!enableBtn) return;
          enableBtn.classList.remove('d-none');
          enableBtn.addEventListener('click', function () {
            Notification.requestPermission().then(function (permission) {
              if (permission !== 'granted') return;
              fetch(config.vapidKeyUrl).then(function (r) { return r.json(); }).then(function (data) {
                return registration.pushManager.subscribe({
                  userVisibleOnly: true,
                  applicationServerKey: urlBase64ToUint8Array(data.key),
                });
              }).then(function (subscription) {
                var raw = subscription.toJSON();
                fetch(config.pushSubscribeUrl, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: 'csrf_token=' + encodeURIComponent(csrfToken(root)) +
                    '&endpoint=' + encodeURIComponent(raw.endpoint) +
                    '&p256dh=' + encodeURIComponent(raw.keys.p256dh) +
                    '&auth=' + encodeURIComponent(raw.keys.auth),
                }).then(function () { enableBtn.classList.add('d-none'); });
              });
            });
          });
        });
      }).catch(function () {});
    }
  }

  window.EventPlannerNotifications = { init: init };
})();
