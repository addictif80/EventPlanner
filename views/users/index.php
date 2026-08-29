<?php use App\Core\Auth; use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Utilisateurs</h2>
  <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Inviter un membre</a>
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
          <td>
            <?php if (!$u['is_active'] && !empty($u['invite_token'])): ?>
              <span class="badge bg-warning text-dark">Invitation en attente</span>
            <?php elseif ($u['is_active']): ?>
              <span class="badge bg-success">Actif</span>
            <?php else: ?>
              <span class="badge bg-secondary">Suspendu</span>
            <?php endif; ?>
          </td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <?php if (!$u['is_active'] && !empty($u['invite_token'])): ?>
              <form method="post" action="<?= url('/users/' . $u['id'] . '/resend-invite') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary">Renvoyer l'invitation</button>
              </form>
            <?php endif; ?>
            <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <?php if ((int) $u['id'] !== Auth::id()): ?>
              <form method="post" action="<?= url('/users/' . $u['id'] . '/suspend') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-warning"><?= $u['is_active'] ? 'Suspendre' : 'Réactiver' ?></button>
              </form>
              <form method="post" action="<?= url('/users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
