<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Utilisateurs</h2>
  <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvel utilisateur</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= View::e($u['name']) ?></td>
          <td><?= View::e($u['email']) ?></td>
          <td><span class="badge bg-secondary-subtle text-dark"><?= View::e($u['role']) ?></span></td>
          <td><?= $u['is_active'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
          <td class="text-end">
            <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <form method="post" action="<?= url('/users/' . $u['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Supprimer</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
