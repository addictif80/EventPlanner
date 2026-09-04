<?php use App\Core\View; ?>
<?php $statusLabels = ['pending' => 'En attente', 'accepted' => 'Acceptée', 'revoked' => 'Révoquée']; ?>
<?php $statusColors = ['pending' => 'warning', 'accepted' => 'success', 'revoked' => 'secondary']; ?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Inviter à créer une organisation</h3>
      <p class="text-muted small">Envoie un lien permettant à la personne invitée de créer sa propre organisation (indépendante des vôtres).</p>
      <form method="post" action="<?= url('/admin/invitations') ?>">
        <?= csrf_field() ?>
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Offre attribuée (optionnel)</label>
          <select name="plan_id" class="form-select">
            <option value="">Offre par défaut à l'inscription</option>
            <?php foreach ($plans as $p): ?>
              <option value="<?= $p['id'] ?>"><?= View::e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Note interne (optionnel)</label>
          <input type="text" name="note" class="form-control" placeholder="Ex : contact salon Heavent 2026">
        </div>
        <button class="btn btn-primary w-100">Envoyer l'invitation</button>
      </form>
    </div></div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-header py-2 fw-semibold">Invitations envoyées</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Email</th><th>Offre</th><th>Statut</th><th>Envoyée le</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($invites)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune invitation envoyée.</td></tr><?php endif; ?>
            <?php foreach ($invites as $i): ?>
            <tr>
              <td><?= View::e($i['email']) ?><?php if ($i['note']): ?><br><span class="text-muted small"><?= View::e($i['note']) ?></span><?php endif; ?></td>
              <td><?= View::e($i['plan_name'] ?? 'Par défaut') ?></td>
              <td>
                <span class="badge bg-<?= $statusColors[$i['status']] ?>"><?= $statusLabels[$i['status']] ?></span>
                <?php if ($i['status'] === 'accepted' && $i['organization_name']): ?>
                  <br><a href="<?= url('/admin/organizations/' . $i['accepted_organization_id']) ?>" class="small">→ <?= View::e($i['organization_name']) ?></a>
                <?php endif; ?>
              </td>
              <td><?= View::date($i['created_at'], 'd/m/Y') ?></td>
              <td class="text-end">
                <?php if ($i['status'] === 'pending'): ?>
                <form method="post" action="<?= url('/admin/invitations/' . $i['id'] . '/resend') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-link p-0 me-2">Renvoyer</button>
                </form>
                <form method="post" action="<?= url('/admin/invitations/' . $i['id'] . '/revoke') ?>" class="d-inline" onsubmit="return confirm('Révoquer cette invitation ?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-link text-danger p-0">Révoquer</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
