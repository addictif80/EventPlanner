<?php use App\Core\View; ?>
<?php $p = $product ?? []; $action = $product ? url('/products/' . $product['id']) : url('/products'); ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" value="<?= View::e($p['name'] ?? '') ?>" required></div>
      <div class="col-md-3"><label class="form-label">Catégorie</label><input type="text" name="category" class="form-control" value="<?= View::e($p['category'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Unité</label><input type="text" name="unit" class="form-control" value="<?= View::e($p['unit'] ?? 'unité') ?>"></div>
      <div class="col-md-3"><label class="form-label">Prix unitaire HT</label><input type="text" name="unit_price" class="form-control" value="<?= View::e((string)($p['unit_price'] ?? '0')) ?>"></div>
      <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= View::e($p['description'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/products') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
