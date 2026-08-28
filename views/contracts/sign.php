<?php
use App\Core\View;
$logoUrl = org_logo_url($contract['organization_id'] ?? null, $company ?? []);
$brandColor = preg_match('/^#[0-9a-fA-F]{6}$/', $company['brand_color'] ?? '') ? $company['brand_color'] : '#3b56d9';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Signer le contrat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.btn-primary{ --bs-btn-bg: <?= $brandColor ?>; --bs-btn-border-color: <?= $brandColor ?>; --bs-btn-hover-bg: <?= shade_color($brandColor, -12) ?>; --bs-btn-hover-border-color: <?= shade_color($brandColor, -12) ?>; }</style>
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:700px;">
  <div class="card shadow-sm"><div class="card-body p-4">
    <?php if ($logoUrl): ?><img src="<?= View::e($logoUrl) ?>" alt="" style="max-height:56px; max-width:200px; margin-bottom:12px;"><?php endif; ?>
    <h1 class="h4 mb-3"><?= View::e($contract['title']) ?></h1>

    <?php foreach ((flashes()['error'] ?? []) as $m): ?>
      <div class="alert alert-danger"><?= View::e($m) ?></div>
    <?php endforeach; ?>

    <div class="border rounded p-3 mb-4" style="white-space: pre-wrap; font-family: Georgia, serif; max-height:350px; overflow-y:auto;"><?= View::e($contract['content']) ?></div>

    <form method="post" id="sign-form">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Nom complet du signataire</label>
        <input type="text" name="signer_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Signature</label>
        <canvas id="signature-pad" width="640" height="180" style="border:1px solid #ccc; width:100%; touch-action:none; background:#fff;"></canvas>
        <div class="mt-2">
          <button type="button" id="clear-signature" class="btn btn-sm btn-outline-secondary">Effacer</button>
        </div>
      </div>
      <input type="hidden" name="signature_data" id="signature_data">
      <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="consent" required>
        <label class="form-check-label" for="consent">Je reconnais avoir lu ce contrat et j'accepte de le signer électroniquement.</label>
      </div>
      <button class="btn btn-primary" type="submit">Signer le contrat</button>
    </form>
  </div></div>
</div>

<script>
const canvas = document.getElementById('signature-pad');
const ctx = canvas.getContext('2d');
ctx.lineWidth = 2;
ctx.lineCap = 'round';
ctx.strokeStyle = '#111';
let drawing = false;

function pos(e) {
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;
  const point = e.touches ? e.touches[0] : e;
  return { x: (point.clientX - rect.left) * scaleX, y: (point.clientY - rect.top) * scaleY };
}

function start(e) { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
function move(e) { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
function end() { drawing = false; }

canvas.addEventListener('mousedown', start);
canvas.addEventListener('mousemove', move);
window.addEventListener('mouseup', end);
canvas.addEventListener('touchstart', start);
canvas.addEventListener('touchmove', move);
canvas.addEventListener('touchend', end);

document.getElementById('clear-signature').addEventListener('click', () => {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
});

document.getElementById('sign-form').addEventListener('submit', (e) => {
  const blank = document.createElement('canvas');
  blank.width = canvas.width; blank.height = canvas.height;
  if (canvas.toDataURL() === blank.toDataURL()) {
    e.preventDefault();
    alert('Merci de signer dans le cadre prévu avant de valider.');
    return;
  }
  document.getElementById('signature_data').value = canvas.toDataURL('image/png');
});
</script>
</body>
</html>
