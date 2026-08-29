<?php use App\Core\View; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h2 class="h5 mb-0">Check-in — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id'] . '/tickets') ?>" class="btn btn-outline-secondary btn-sm">← Billetterie</a>
</div>

<div class="card mb-3" style="max-width:480px;">
  <div class="card-body text-center py-3">
    <div class="text-muted small text-uppercase">Entrées validées</div>
    <div class="fs-1 fw-bold"><span id="checkin-count"><?= (int) $stats['checked_in'] ?></span> <span class="text-muted fs-4">/ <span id="checkin-total"><?= (int) $stats['total'] ?></span></span></div>
    <div class="progress mt-2" style="height:8px;">
      <div id="checkin-progress" class="progress-bar bg-success" style="width: <?= $stats['total'] > 0 ? round($stats['checked_in'] / $stats['total'] * 100) : 0 ?>%"></div>
    </div>
  </div>
</div>

<div id="checkin-flash">
  <?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageStatus === 'success' ? 'success' : ($messageStatus === 'warning' ? 'warning' : 'danger') ?> fs-5"><?= View::e($message) ?></div>
  <?php endif; ?>
</div>

<div class="card" style="max-width:480px;"><div class="card-body">
  <form method="post" action="<?= url('/events/' . $event['id'] . '/checkin') ?>" id="checkin-form">
    <?= csrf_field() ?>
    <label class="form-label">Code du billet</label>
    <input type="text" name="code" id="checkin-code" class="form-control form-control-lg text-uppercase mb-3" autofocus autocomplete="off" placeholder="Ex : A1B2C3D4E5">
    <button type="button" id="scan-btn" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-qr-code-scan"></i> Scanner un QR code</button>
    <button class="btn btn-primary w-100 btn-lg">Valider l'entrée</button>
  </form>

  <div id="scan-panel" class="mt-3 d-none">
    <video id="scan-video" playsinline muted style="width:100%; border-radius:6px; background:#000;"></video>
    <p class="text-muted small text-center mt-2 mb-0">Visez le QR code du billet avec la caméra. Le scan reste actif : enchaînez les entrées sans rouvrir la caméra.</p>
    <button type="button" id="scan-stop" class="btn btn-outline-secondary w-100 mt-2">Arrêter le scan</button>
  </div>
</div></div>

<div class="card mt-3" style="max-width:480px;">
  <div class="card-header py-2 small fw-semibold">Dernières entrées</div>
  <ul id="checkin-recent" class="list-group list-group-flush">
    <?php foreach ($stats['recent'] as $r): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center py-2 small">
      <span><?= View::e($r['holder_name'] ?: $r['code']) ?> <span class="text-muted">— <?= View::e($r['category_name']) ?></span></span>
      <i class="bi bi-check-circle-fill text-success"></i>
    </li>
    <?php endforeach; ?>
    <?php if (empty($stats['recent'])): ?><li id="checkin-recent-empty" class="list-group-item text-muted small py-3 text-center">Aucune entrée validée pour l'instant.</li><?php endif; ?>
  </ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
(function () {
  var scanBtn = document.getElementById('scan-btn');
  var stopBtn = document.getElementById('scan-stop');
  var panel = document.getElementById('scan-panel');
  var video = document.getElementById('scan-video');
  var codeInput = document.getElementById('checkin-code');
  var form = document.getElementById('checkin-form');
  var flash = document.getElementById('checkin-flash');
  var countEl = document.getElementById('checkin-count');
  var totalEl = document.getElementById('checkin-total');
  var progressEl = document.getElementById('checkin-progress');
  var recentList = document.getElementById('checkin-recent');
  var stream = null;
  var rafId = null;
  var scanning = false;
  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d', { willReadFrequently: true });

  function csrfToken() {
    var input = form.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
  }

  function renderStats(data) {
    countEl.textContent = data.checked_in;
    totalEl.textContent = data.total;
    progressEl.style.width = (data.total > 0 ? Math.round(data.checked_in / data.total * 100) : 0) + '%';

    if (data.recent && data.recent.length) {
      recentList.innerHTML = '';
      data.recent.forEach(function (r) {
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 small';
        li.innerHTML = '<span>' + escapeHtml(r.holder_name || r.code) + ' <span class="text-muted">— ' + escapeHtml(r.category_name) + '</span></span><i class="bi bi-check-circle-fill text-success"></i>';
        recentList.appendChild(li);
      });
    }
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function showFlash(status, message) {
    var cls = status === 'success' ? 'success' : (status === 'warning' ? 'warning' : 'danger');
    flash.innerHTML = '<div class="alert alert-' + cls + ' fs-5">' + escapeHtml(message) + '</div>';
  }

  function vibrate(pattern) {
    if (navigator.vibrate) navigator.vibrate(pattern);
  }

  function submitCode(code) {
    if (!code) return;
    var body = 'csrf_token=' + encodeURIComponent(csrfToken()) + '&code=' + encodeURIComponent(code);
    fetch(form.action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: body,
    }).then(function (r) { return r.json(); }).then(function (data) {
      showFlash(data.status, data.message);
      renderStats(data);
      codeInput.value = '';
      vibrate(data.status === 'success' ? 80 : [60, 60, 60]);
      resumeScan();
    }).catch(function () {
      showFlash('error', "Échec de la validation (connexion réseau). Réessayez.");
      resumeScan();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    submitCode(codeInput.value.trim().toUpperCase());
  });

  function resumeScan() {
    if (stream && !scanning) {
      scanning = true;
      rafId = requestAnimationFrame(tick);
    }
  }

  function stopScan() {
    scanning = false;
    if (rafId) cancelAnimationFrame(rafId);
    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    stream = null;
    panel.classList.add('d-none');
  }

  function tick() {
    if (!scanning) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var result = window.jsQR ? jsQR(imageData.data, imageData.width, imageData.height) : null;
      if (result && result.data) {
        scanning = false;
        submitCode(result.data.toUpperCase().trim());
        return;
      }
    }
    rafId = requestAnimationFrame(tick);
  }

  scanBtn.addEventListener('click', function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert("Votre navigateur ne permet pas d'accéder à la caméra. Utilisez la saisie manuelle du code.");
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
      stream = s;
      video.srcObject = s;
      video.play();
      panel.classList.remove('d-none');
      scanning = true;
      rafId = requestAnimationFrame(tick);
    }).catch(function () {
      alert("Impossible d'accéder à la caméra. Vérifiez les autorisations de votre navigateur.");
    });
  });

  stopBtn.addEventListener('click', stopScan);
  window.addEventListener('beforeunload', stopScan);
})();
</script>
