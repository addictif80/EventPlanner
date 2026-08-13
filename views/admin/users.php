<?php use App\Core\View; ?>
<form class="mb-3" method="get" action="<?= url('/admin/users') ?>">
  <input type="text" name="q" class="form-control" style="max-width:320px;" placeholder="Rechercher nom, email, organisation..." value="<?= View::e($search ?? '') ?>">
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Email</th><th>Organisation</th><th>Rôle</th><th>Actif</th><th>Super admin</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= View::e($u['name']) ?></td>
          <td><?= View::e($u['email']) ?></td>
          <td><a href="<?= url('/admin/organizations/' . $u['organization_id']) ?>"><?= View::e($u['organization_name']) ?></a></td>
          <td><?= View::e($u['role']) ?></td>
          <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
          <td><?= $u['is_super_admin'] ? '<span class="badge bg-dark">Super admin</span>' : '' ?></td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <a href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Gérer</a>
            <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/impersonate') ?>" onsubmit="return confirm('Se connecter en tant que ' + <?= json_encode($u['name']) ?> + ' ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-dark"><i class="bi bi-incognito"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?><tr><td colspan="7" class="text-muted text-center py-4">Aucun utilisateur.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
