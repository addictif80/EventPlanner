<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Prise de rendez-vous en ligne</h2>
  <a href="<?= url('/appointments') ?>" class="btn btn-outline-secondary btn-sm">Voir les rendez-vous</a>
</div>

<div class="card mb-3"><div class="card-body">
  <p class="text-muted small mb-2">Votre page publique de réservation — partagez ce lien sur votre site, vos réseaux ou votre signature email :</p>
  <div class="input-group">
    <input type="text" class="form-control" value="<?= View::e($publicUrl) ?>" readonly id="booking-url">
    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('booking-url').value)"><i class="bi bi-clipboard"></i> Copier</button>
    <a href="<?= View::e($publicUrl) ?>" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i></a>
  </div>
</div></div>

<div class="card"><div class="card-body">
  <form method="post" action="<?= url('/booking-settings') ?>">
    <?= csrf_field() ?>
    <div class="form-check form-switch mb-3">
      <input type="checkbox" class="form-check-input" id="is_enabled" name="is_enabled" value="1" <?= !empty($settings['is_enabled']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_enabled">Page de réservation active</label>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3"><label class="form-label">Durée du créneau (min)</label><input type="number" min="5" name="slot_duration_minutes" class="form-control" value="<?= View::e((string)($settings['slot_duration_minutes'] ?? 30)) ?>"></div>
      <div class="col-md-3"><label class="form-label">Battement entre créneaux (min)</label><input type="number" min="0" name="buffer_minutes" class="form-control" value="<?= View::e((string)($settings['buffer_minutes'] ?? 0)) ?>"></div>
      <div class="col-md-3"><label class="form-label">Délai de prévenance (h)</label><input type="number" min="0" name="min_notice_hours" class="form-control" value="<?= View::e((string)($settings['min_notice_hours'] ?? 24)) ?>"></div>
      <div class="col-md-3"><label class="form-label">Réservable jusqu'à (jours)</label><input type="number" min="1" name="max_advance_days" class="form-control" value="<?= View::e((string)($settings['max_advance_days'] ?? 60)) ?>"></div>
    </div>

    <div class="mb-3">
      <label class="form-label">Type de rendez-vous</label>
      <input type="text" name="location_type" class="form-control" style="max-width:300px;" value="<?= View::e($settings['location_type'] ?? 'Téléphone') ?>" placeholder="Téléphone, visio, en agence...">
    </div>

    <h3 class="h6 mt-4">Horaires d'ouverture</h3>
    <table class="table table-sm align-middle" style="max-width:560px;">
      <?php foreach ($days as $day => $label): $w = $settings['weekly_hours'][$day] ?? null; ?>
      <tr>
        <td style="width:110px;"><div class="form-check">
          <input type="checkbox" class="form-check-input" name="open_<?= $day ?>" value="1" <?= $w ? 'checked' : '' ?>>
          <label class="form-check-label"><?= $label ?></label>
        </div></td>
        <td><input type="time" name="start_<?= $day ?>" class="form-control form-control-sm" value="<?= View::e($w['start'] ?? '09:00') ?>"></td>
        <td>—</td>
        <td><input type="time" name="end_<?= $day ?>" class="form-control form-control-sm" value="<?= View::e($w['end'] ?? '18:00') ?>"></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <div class="mb-3">
      <label class="form-label">Message affiché après réservation (optionnel)</label>
      <textarea name="meeting_instructions" class="form-control" rows="2" placeholder="Ex : Vous recevrez un lien de visioconférence par email."><?= View::e($settings['meeting_instructions'] ?? '') ?></textarea>
    </div>

    <button class="btn btn-primary">Enregistrer</button>
  </form>
</div></div>
