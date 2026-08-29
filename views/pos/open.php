<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Ouvrir la caisse</h2>
  <a href="<?= url('/pos/sessions') ?>" class="btn btn-outline-secondary btn-sm">Historique des caisses</a>
</div>

<div class="card" style="max-width:480px;"><div class="card-body">
  <p class="text-muted small">Ouvrez une session de caisse pour commencer à encaisser sur place. Vous pourrez la clôturer à la fin de l'événement.</p>
  <form method="post" action="<?= url('/pos/open') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Événement lié (optionnel)</label>
      <select name="event_id" class="form-select">
        <option value="">—</option>
        <?php foreach ($events as $e): ?><option value="<?= $e['id'] ?>"><?= View::e($e['title']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Fond de caisse de départ</label>
      <input type="text" name="opening_float" class="form-control" value="0" required>
    </div>
    <button class="btn btn-primary w-100 btn-lg">Ouvrir la caisse</button>
  </form>
</div></div>
