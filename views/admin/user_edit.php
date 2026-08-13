<?php use App\Core\View; ?>
<div class="card" style="max-width:520px;">
  <div class="card-body">
    <p class="text-muted small mb-3">Organisation : <?= View::e($user['organization_name']) ?></p>
    <form method="post" action="<?= url('/admin/users/' . $user['id']) ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nom</label><input type="text" class="form-control" value="<?= View::e($user['name']) ?>" disabled></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="text" class="form-control" value="<?= View::e($user['email']) ?>" disabled></div>
      <div class="form-check mb-2">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Compte actif</label>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_super_admin" value="1" class="form-check-input" id="is_super_admin" <?= $user['is_super_admin'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_super_admin">Super administrateur (accès plateforme complet)</label>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/admin/users') ?>" class="btn btn-link">Annuler</a>
    </form>
  </div>
</div>
