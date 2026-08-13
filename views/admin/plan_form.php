<?php use App\Core\View; $moduleIds = $plan['module_ids'] ?? []; ?>
<div class="card" style="max-width:640px;">
  <div class="card-body">
    <form method="post" action="<?= $plan ? url('/admin/offers/plans/' . $plan['id']) : url('/admin/offers/plans') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nom de l'offre</label><input type="text" name="name" class="form-control" value="<?= View::e($plan['name'] ?? '') ?>" required></div>
      <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= View::e($plan['description'] ?? '') ?></textarea></div>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Prix mensuel (€)</label><input type="text" name="monthly_price" class="form-control" value="<?= View::e((string)($plan['monthly_price'] ?? '0')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Membres max (vide = illimité)</label><input type="number" name="max_members" class="form-control" value="<?= View::e($plan['max_members'] !== null && isset($plan['max_members']) ? (string) $plan['max_members'] : '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Ordre d'affichage</label><input type="number" name="sort_order" class="form-control" value="<?= View::e((string)($plan['sort_order'] ?? 0)) ?>"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Modules inclus</label>
        <div class="row">
          <?php foreach ($modules as $m): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" name="module_ids[]" value="<?= $m['id'] ?>" id="mod<?= $m['id'] ?>" <?= in_array($m['id'], $moduleIds, true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="mod<?= $m['id'] ?>"><?= View::e($m['name']) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-check mb-2">
        <input type="checkbox" name="is_default_signup" value="1" class="form-check-input" id="is_default_signup" <?= !empty($plan['is_default_signup']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_default_signup">Offre par défaut à l'inscription</label>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= ($plan['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Offre active (visible aux organisations)</label>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/admin/offers') ?>" class="btn btn-link">Annuler</a>
    </form>
  </div>
</div>
