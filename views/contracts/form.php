<?php use App\Core\View; use App\Models\Client; ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= url('/contracts') ?>">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Titre</label>
        <input type="text" name="title" class="form-control" value="Contrat de prestation" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $selectedClientId == $c['id'] ? 'selected' : '' ?>><?= View::e(Client::displayName($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Événement lié (optionnel)</label>
        <select name="event_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>" <?= $selectedEventId == $e['id'] ? 'selected' : '' ?>><?= View::e($e['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label class="form-label">Contenu du contrat</label>
    <textarea name="content" class="form-control" rows="16"><?= View::e($defaultContent) ?></textarea>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Créer le contrat</button>
      <a href="<?= url('/contracts') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
