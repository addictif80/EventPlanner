<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-0"><?= View::e($organization['name']) ?></h2>
    <span class="badge bg-<?= $organization['status'] === 'suspended' ? 'danger' : 'success' ?>"><?= View::e($organization['status']) ?></span>
  </div>
  <form method="post" action="<?= url('/admin/organizations/' . $organization['id'] . '/status') ?>" onsubmit="return confirm('Confirmer le changement de statut de cette organisation ?');">
    <?= csrf_field() ?>
    <?php if ($organization['status'] === 'suspended'): ?>
      <button class="btn btn-success">Réactiver l'organisation</button>
    <?php else: ?>
      <button class="btn btn-danger">Suspendre l'organisation</button>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header">Utilisateurs de cette organisation</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Actif</th><th>Super admin</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= View::e($u['name']) ?></td>
          <td><?= View::e($u['email']) ?></td>
          <td><?= View::e($u['role']) ?></td>
          <td><?= $u['is_active'] ? 'Oui' : 'Non' ?></td>
          <td><?= $u['is_super_admin'] ? 'Oui' : 'Non' ?></td>
          <td class="text-end"><a href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Gérer</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
