<?php use App\Core\View; ?>
<?php $i = $item ?? []; $action = $item ? url('/equipment/' . $item['id']) : url('/equipment'); ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" value="<?= View::e($i['name'] ?? '') ?>" required></div>
      <div class="col-md-3"><label class="form-label">Catégorie</label><input type="text" name="category" class="form-control" value="<?= View::e($i['category'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Quantité totale</label><input type="number" name="total_quantity" class="form-control" value="<?= View::e((string)($i['total_quantity'] ?? 1)) ?>"></div>
      <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= View::e($i['notes'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/equipment') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
