<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-0"><?= View::e($organization['name']) ?></h2>
    <span class="badge bg-<?= $organization['status'] === 'suspended' ? 'danger' : 'success' ?>"><?= View::e($organization['status']) ?></span>
    <?php if ($organization['status'] === 'suspended' && !empty($organization['suspension_reason'])): ?>
      <span class="badge bg-secondary"><?= $organization['suspension_reason'] === 'non_payment' ? 'Impayé (automatique)' : 'Suspension manuelle' ?></span>
    <?php endif; ?>
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

<div class="card mb-3">
  <div class="card-header">Abonnement</div>
  <div class="card-body">
    <?php if ($subscription): ?>
      <p class="mb-2">
        Offre actuelle : <strong><?= View::e($subscription['plan_name'] ?? 'Aucune') ?></strong>
        &middot; Statut : <span class="badge bg-secondary"><?= View::e($subscription['status']) ?></span>
        <?php if ($subscription['stripe_subscription_id']): ?> &middot; <span class="text-muted small">Facturé via Stripe</span><?php endif; ?>
      </p>
      <?php if ($subscription['status'] === 'past_due' && !empty($subscription['past_due_since'])): ?>
        <p class="text-danger small mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Impayé depuis le <?= View::date($subscription['past_due_since'], 'd/m/Y') ?> (<?= (int) floor((time() - strtotime($subscription['past_due_since'])) / 86400) ?> jour(s)).</p>
      <?php endif; ?>
    <?php else: ?>
      <p class="text-muted mb-2">Aucun abonnement enregistré pour cette organisation.</p>
    <?php endif; ?>
    <form method="post" action="<?= url('/admin/organizations/' . $organization['id'] . '/assign-plan') ?>" class="d-flex gap-2">
      <?= csrf_field() ?>
      <select name="plan_id" class="form-select" style="max-width:280px;">
        <option value="">— Aucune offre —</option>
        <?php foreach ($plans as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($subscription['plan_id'] ?? null) == $p['id'] ? 'selected' : '' ?>><?= View::e($p['name']) ?> (<?= View::money((float) $p['monthly_price']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-dark">Assigner manuellement</button>
    </form>
    <p class="text-muted small mt-2 mb-0">L'assignation manuelle n'utilise pas Stripe (pas de prélèvement) : utile pour les comptes historiques, gratuits ou promotionnels.</p>
  </div>
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
