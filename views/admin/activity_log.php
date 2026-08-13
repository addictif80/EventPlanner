<?php use App\Core\View; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead><tr><th>Date</th><th>Admin</th><th>Action</th><th>Cible</th><th>Détails</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td class="text-nowrap"><?= View::date($log['created_at'], 'd/m/Y H:i') ?></td>
          <td><?= View::e($log['admin_name'] ?? '—') ?></td>
          <td><?= View::e($log['action']) ?></td>
          <td><?= View::e($log['target_type']) ?><?= $log['target_id'] ? ' #' . $log['target_id'] : '' ?></td>
          <td class="text-muted small"><?= View::e($log['details']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?><tr><td colspan="5" class="text-muted text-center py-4">Aucune activité enregistrée.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
