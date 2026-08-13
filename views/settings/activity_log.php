<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Journal d'activité</h2>
  <a href="<?= url('/settings') ?>" class="btn btn-outline-secondary btn-sm">← Paramètres</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Élément</th><th>Détails</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune activité enregistrée.</td></tr><?php endif; ?>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="text-nowrap"><?= View::date($l['created_at'], 'd/m/Y H:i') ?></td>
          <td><?= View::e($l['user_name'] ?? 'Système') ?></td>
          <td><?= View::e($l['action']) ?></td>
          <td><?= View::e($l['entity_type']) ?> <?= $l['entity_id'] ? '#' . $l['entity_id'] : '' ?></td>
          <td class="text-muted small"><?= View::e($l['details']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
