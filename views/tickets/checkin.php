<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Check-in — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id'] . '/tickets') ?>" class="btn btn-outline-secondary btn-sm">← Billetterie</a>
</div>

<?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageStatus === 'success' ? 'success' : ($messageStatus === 'warning' ? 'warning' : 'danger') ?> fs-5"><?= View::e($message) ?></div>
<?php endif; ?>

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
    <p class="text-muted small text-center mt-2 mb-0">Visez le QR code du billet avec la caméra.</p>
    <button type="button" id="scan-stop" class="btn btn-outline-secondary w-100 mt-2">Annuler le scan</button>
  </div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
(function () {
  var scanBtn = document.getElementById('scan-btn');
  var stopBtn = document.getElementById('scan-stop');
  var panel = document.getElementById('scan-panel');
  var video = document.getElementById('scan-video');
  var codeInput = document.getElementById('checkin-code');
  var form = document.getElementById('checkin-form');
  var stream = null;
  var rafId = null;
  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d', { willReadFrequently: true });

  function stopScan() {
    if (rafId) cancelAnimationFrame(rafId);
    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    stream = null;
    panel.classList.add('d-none');
  }

  function tick() {
    if (!stream) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var result = window.jsQR ? jsQR(imageData.data, imageData.width, imageData.height) : null;
      if (result && result.data) {
        codeInput.value = result.data.toUpperCase().trim();
        stopScan();
        form.submit();
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
      rafId = requestAnimationFrame(tick);
    }).catch(function () {
      alert("Impossible d'accéder à la caméra. Vérifiez les autorisations de votre navigateur.");
    });
  });

  stopBtn.addEventListener('click', stopScan);
  window.addEventListener('beforeunload', stopScan);
})();
</script>
