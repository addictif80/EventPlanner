<?php use App\Core\View; use App\Models\Client; ?>
<?php $e = $event ?? []; $action = $event ? url('/events/' . $event['id']) : url('/events'); ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Titre de l'événement</label>
        <input type="text" name="title" class="form-control" value="<?= View::e($e['title'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="event_type_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($eventTypes as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($e['event_type_id'] ?? null) == $t['id'] ? 'selected' : '' ?>><?= View::e($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Statut</label>
        <select name="status" class="form-select">
          <?php foreach (['draft' => 'Brouillon', 'confirmed' => 'Confirmé', 'in_progress' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($e['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (($e['client_id'] ?? $selectedClientId) == $c['id']) ? 'selected' : '' ?>><?= View::e(Client::displayName($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Lieu (fiche)</label>
        <select name="venue_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($venues as $v): ?>
            <option value="<?= $v['id'] ?>" <?= ($e['venue_id'] ?? null) == $v['id'] ? 'selected' : '' ?>><?= View::e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date de début</label>
        <input type="date" name="event_date" class="form-control" value="<?= View::e($e['event_date'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date de fin</label>
        <input type="date" name="end_date" class="form-control" value="<?= View::e($e['end_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Nombre d'invités</label>
        <input type="number" name="guests_count" class="form-control" value="<?= View::e((string)($e['guests_count'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Adresse / lieu (texte libre)</label>
        <input type="text" name="location" class="form-control" value="<?= View::e($e['location'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Budget prévisionnel</label>
        <input type="text" name="budget" class="form-control" value="<?= View::e((string)($e['budget'] ?? '')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= View::e($e['description'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/events') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
