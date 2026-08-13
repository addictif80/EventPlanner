<?php use App\Core\View; ?>
<?php $v = $venue ?? []; $action = $venue ? url('/venues/' . $venue['id']) : url('/venues'); ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" value="<?= View::e($v['name'] ?? '') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Capacité</label><input type="number" name="capacity" class="form-control" value="<?= View::e((string)($v['capacity'] ?? '')) ?>"></div>
      <div class="col-12"><label class="form-label">Adresse</label><input type="text" name="address" class="form-control" value="<?= View::e($v['address'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Code postal</label><input type="text" name="postal_code" class="form-control" value="<?= View::e($v['postal_code'] ?? '') ?>"></div>
      <div class="col-md-8"><label class="form-label">Ville</label><input type="text" name="city" class="form-control" value="<?= View::e($v['city'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Contact</label><input type="text" name="contact_name" class="form-control" value="<?= View::e($v['contact_name'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Téléphone</label><input type="text" name="contact_phone" class="form-control" value="<?= View::e($v['contact_phone'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control" value="<?= View::e($v['contact_email'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= View::e($v['notes'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/venues') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
