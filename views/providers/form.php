<?php use App\Core\View; ?>
<?php $p = $provider ?? []; $action = $provider ? url('/providers/' . $provider['id']) : url('/providers'); ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" value="<?= View::e($p['name'] ?? '') ?>" required></div>
      <div class="col-md-6"><label class="form-label">Catégorie</label><input type="text" name="category" class="form-control" placeholder="Traiteur, DJ, Photographe..." value="<?= View::e($p['category'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Contact</label><input type="text" name="contact_name" class="form-control" value="<?= View::e($p['contact_name'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= View::e($p['email'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Téléphone</label><input type="text" name="phone" class="form-control" value="<?= View::e($p['phone'] ?? '') ?>"></div>
      <div class="col-md-3">
        <label class="form-label">Note</label>
        <select name="rating" class="form-select">
          <option value="">—</option>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= (($p['rating'] ?? null) == $i) ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= View::e($p['notes'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/providers') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
