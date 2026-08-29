<?php use App\Core\View; ?>
<?php $u = $user ?? []; $action = $user ? url('/users/' . $user['id']) : url('/users'); ?>
<div class="card"><div class="card-body">
  <?php if (!$user): ?>
    <p class="text-muted small">Un email d'invitation sera envoyé à cette adresse ; la personne choisira elle-même son mot de passe en l'acceptant.</p>
  <?php endif; ?>
  <form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nom complet</label><input type="text" name="name" class="form-control" value="<?= View::e($u['name'] ?? '') ?>" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= View::e($u['email'] ?? '') ?>" required></div>
      <?php if ($user): ?>
      <div class="col-md-6">
        <label class="form-label">Mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" name="password" class="form-control">
      </div>
      <?php endif; ?>
      <div class="col-md-3">
        <label class="form-label">Rôle</label>
        <select name="role" class="form-select">
          <?php foreach (['admin' => 'Administrateur', 'manager' => 'Manager', 'staff' => 'Staff'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($u['role'] ?? 'staff') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($user): ?>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= ($u['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_active">Compte actif</label>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary"><?= $user ? 'Enregistrer' : "Envoyer l'invitation" ?></button>
      <a href="<?= url('/users') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>
