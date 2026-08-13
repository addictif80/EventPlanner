<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'confirmed' => 'Confirmé', 'in_progress' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'];
$statusColors = ['draft' => 'secondary', 'confirmed' => 'primary', 'in_progress' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Événements</h2>
  <a href="<?= url('/events/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvel événement</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Événement</th><th>Client</th><th>Type</th><th>Date</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($events)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun événement.</td></tr><?php endif; ?>
        <?php foreach ($events as $e): ?>
        <tr>
          <td><a href="<?= url('/events/' . $e['id']) ?>"><?= View::e($e['title']) ?></a></td>
          <td><?= View::e($e['company_name'] ?: trim($e['first_name'] . ' ' . $e['last_name'])) ?></td>
          <td><?= View::e($e['type_name']) ?></td>
          <td><?= View::date($e['event_date']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$e['status']] ?>"><?= $statusLabels[$e['status']] ?></span></td>
          <td class="text-end"><a href="<?= url('/events/' . $e['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
