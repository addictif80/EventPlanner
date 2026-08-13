<?php use App\Core\View; ?>
<?php $c = $client ?? []; $action = $client ? url('/clients/' . $client['id']) : url('/clients'); ?>
<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Type de client</label>
          <select name="type" class="form-select">
            <option value="particulier" <?= ($c['type'] ?? '') === 'particulier' ? 'selected' : '' ?>>Particulier</option>
            <option value="entreprise" <?= ($c['type'] ?? '') === 'entreprise' ? 'selected' : '' ?>>Entreprise</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Prénom</label>
          <input type="text" name="first_name" class="form-control" value="<?= View::e($c['first_name'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label">Nom</label>
          <input type="text" name="last_name" class="form-control" value="<?= View::e($c['last_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nom de l'entreprise</label>
          <input type="text" name="company_name" class="form-control" value="<?= View::e($c['company_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Tags</label>
          <input type="text" name="tags" class="form-control" placeholder="VIP, récurrent..." value="<?= View::e($c['tags'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= View::e($c['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Téléphone</label>
          <input type="text" name="phone" class="form-control" value="<?= View::e($c['phone'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Adresse</label>
          <input type="text" name="address" class="form-control" value="<?= View::e($c['address'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Code postal</label>
          <input type="text" name="postal_code" class="form-control" value="<?= View::e($c['postal_code'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label">Ville</label>
          <input type="text" name="city" class="form-control" value="<?= View::e($c['city'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Pays</label>
          <input type="text" name="country" class="form-control" value="<?= View::e($c['country'] ?? 'France') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3"><?= View::e($c['notes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Enregistrer</button>
        <a href="<?= url('/clients') ?>" class="btn btn-outline-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>
