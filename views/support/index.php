<?php use App\Core\View; $statusColors = ['open' => 'primary', 'pending' => 'warning', 'closed' => 'secondary']; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Besoin d'aide ? Ouvrez un ticket, notre équipe vous répond directement ici.</p>
  <a href="<?= url('/support/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouveau ticket</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Sujet</th><th>Priorité</th><th>Statut</th><th>Mis à jour</th></tr></thead>
      <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><a href="<?= url('/support/' . $t['id']) ?>"><?= View::e($t['subject']) ?></a></td>
          <td><?= View::e($t['priority']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$t['status']] ?? 'secondary' ?>"><?= View::e($t['status']) ?></span></td>
          <td><?= View::date($t['updated_at'], 'd/m/Y H:i') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($tickets)): ?><tr><td colspan="4" class="text-muted text-center py-4">Aucun ticket pour le moment.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
