<?php
use App\Core\View;
$logoUrl = org_logo_url($settings['organization_id'] ?? null, $company ?? []);
$brandColor = preg_match('/^#[0-9a-fA-F]{6}$/', $company['brand_color'] ?? '') ? $company['brand_color'] : '#3b56d9';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Prendre rendez-vous<?= !empty($company['company_name']) ? ' — ' . View::e($company['company_name']) : '' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
.btn-primary{ --bs-btn-bg: <?= $brandColor ?>; --bs-btn-border-color: <?= $brandColor ?>; --bs-btn-hover-bg: <?= shade_color($brandColor, -12) ?>; --bs-btn-hover-border-color: <?= shade_color($brandColor, -12) ?>; }
.slot-btn.active{ background: <?= $brandColor ?>; color:#fff; border-color: <?= $brandColor ?>; }
</style>
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px;">
  <div class="card shadow-sm"><div class="card-body p-4">
    <?php if ($logoUrl): ?><img src="<?= View::e($logoUrl) ?>" alt="" style="max-height:56px; max-width:200px; margin-bottom:12px;"><?php else: ?><h5 class="mb-3"><?= View::e($company['company_name'] ?? '') ?></h5><?php endif; ?>

    <h1 class="h5 mb-1">Prendre rendez-vous</h1>
    <p class="text-muted small mb-4">Rendez-vous — <?= View::e($settings['location_type']) ?> — <?= (int) $settings['slot_duration_minutes'] ?> min</p>

    <?php foreach ((flashes()['error'] ?? []) as $m): ?>
      <div class="alert alert-danger"><?= View::e($m) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('/booking/' . $slug) ?>" id="booking-form">
      <?= csrf_field() ?>
      <input type="hidden" name="time" id="selected-time" required>

      <div class="mb-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" id="date-input" class="form-control" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . (int)$settings['max_advance_days'] . ' days')) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Créneaux disponibles</label>
        <div id="slots" class="d-flex flex-wrap gap-2"><span class="text-muted small">Choisissez une date.</span></div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Nom complet" required></div>
        <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-md-6"><input type="tel" name="phone" class="form-control" placeholder="Téléphone (optionnel)"></div>
        <div class="col-md-6"><input type="text" name="subject" class="form-control" placeholder="Sujet du rendez-vous (optionnel)"></div>
      </div>

      <button type="submit" id="submit-btn" class="btn btn-primary w-100 btn-lg" disabled>Confirmer le rendez-vous</button>
    </form>
  </div></div>
</div>

<script>
(function () {
  var dateInput = document.getElementById('date-input');
  var slotsEl = document.getElementById('slots');
  var timeInput = document.getElementById('selected-time');
  var submitBtn = document.getElementById('submit-btn');
  var slotsUrl = <?= json_encode(url('/booking/' . $slug . '/slots.json')) ?>;

  dateInput.addEventListener('change', function () {
    timeInput.value = '';
    submitBtn.disabled = true;
    slotsEl.innerHTML = '<span class="text-muted small">Chargement...</span>';
    fetch(slotsUrl + '?date=' + encodeURIComponent(dateInput.value)).then(function (r) { return r.json(); }).then(function (data) {
      slotsEl.innerHTML = '';
      if (!data.slots || data.slots.length === 0) {
        slotsEl.innerHTML = '<span class="text-muted small">Aucun créneau disponible ce jour-là.</span>';
        return;
      }
      data.slots.forEach(function (slot) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm slot-btn';
        btn.textContent = slot;
        btn.addEventListener('click', function () {
          document.querySelectorAll('.slot-btn').forEach(function (b) { b.classList.remove('active'); });
          btn.classList.add('active');
          timeInput.value = slot;
          submitBtn.disabled = false;
        });
        slotsEl.appendChild(btn);
      });
    });
  });
})();
</script>
</body>
</html>
