<?php use App\Core\View; $statusColors = ['open' => 'primary', 'pending' => 'warning', 'closed' => 'secondary']; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Sujet</th><th>Organisation</th><th>Priorité</th><th>Statut</th><th>Mis à jour</th></tr></thead>
      <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><a href="<?= url('/admin/tickets/' . $t['id']) ?>"><?= View::e($t['subject']) ?></a></td>
          <td><?= View::e($t['organization_name']) ?></td>
          <td><?= View::e($t['priority']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$t['status']] ?? 'secondary' ?>"><?= View::e($t['status']) ?></span></td>
          <td><?= View::date($t['updated_at'], 'd/m/Y H:i') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($tickets)): ?><tr><td colspan="5" class="text-muted text-center py-4">Aucun ticket.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
