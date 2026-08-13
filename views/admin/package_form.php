<?php use App\Core\View; $moduleIds = $package['module_ids'] ?? []; ?>
<div class="card" style="max-width:560px;">
  <div class="card-body">
    <form method="post" action="<?= $package ? url('/admin/offers/packages/' . $package['id']) : url('/admin/offers/packages') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nom du package</label><input type="text" name="name" class="form-control" value="<?= View::e($package['name'] ?? '') ?>" required></div>
      <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= View::e($package['description'] ?? '') ?></textarea></div>
      <div class="mb-3"><label class="form-label">Prix mensuel du package (€)</label><input type="text" name="monthly_price" class="form-control" value="<?= View::e((string)($package['monthly_price'] ?? '0')) ?>" required></div>
      <div class="mb-3">
        <label class="form-label">Modules inclus dans ce package</label>
        <div class="row">
          <?php foreach ($modules as $m): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" name="module_ids[]" value="<?= $m['id'] ?>" id="pmod<?= $m['id'] ?>" <?= in_array($m['id'], $moduleIds, true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="pmod<?= $m['id'] ?>"><?= View::e($m['name']) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= ($package['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Package actif (disponible à l'achat)</label>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/admin/offers') ?>" class="btn btn-link">Annuler</a>
    </form>
  </div>
</div>
