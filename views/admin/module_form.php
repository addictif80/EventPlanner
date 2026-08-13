<?php use App\Core\View; ?>
<div class="card" style="max-width:560px;">
  <div class="card-body">
    <form method="post" action="<?= $module ? url('/admin/offers/modules/' . $module['id']) : url('/admin/offers/modules') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nom du module</label><input type="text" name="name" class="form-control" value="<?= View::e($module['name'] ?? '') ?>" required></div>
      <div class="mb-3">
        <label class="form-label">Clé technique</label>
        <input type="text" name="module_key" class="form-control" value="<?= View::e($module['module_key'] ?? '') ?>" placeholder="ex. contracts" <?= $module ? 'readonly' : '' ?>>
        <div class="form-text">Identifiant utilisé dans le code pour vérifier l'accès. Laissez vide pour le générer depuis le nom.</div>
      </div>
      <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= View::e($module['description'] ?? '') ?></textarea></div>
      <div class="mb-3"><label class="form-label">Prix mensuel (€, 0 = gratuit)</label><input type="text" name="monthly_price" class="form-control" value="<?= View::e((string)($module['monthly_price'] ?? '0')) ?>" required></div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= ($module['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Module actif (disponible à l'achat)</label>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/admin/offers') ?>" class="btn btn-link">Annuler</a>
    </form>
  </div>
</div>
