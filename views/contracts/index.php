<?php use App\Core\View; ?>
<?php $statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'signed' => 'Signé', 'cancelled' => 'Annulé']; $statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'signed' => 'success', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Contrats</h2>
  <a href="<?= url('/contracts/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau contrat</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Titre</th><th>Client</th><th>Créé le</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if (empty($contracts)): ?><tr><td colspan="4" class="text-center text-muted py-4">Aucun contrat.</td></tr><?php endif; ?>
        <?php foreach ($contracts as $c): ?>
        <tr>
          <td><a href="<?= url('/contracts/' . $c['id']) ?>"><?= View::e($c['title']) ?></a></td>
          <td><?= View::e($c['company_name'] ?: trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
          <td><?= View::date($c['created_at']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$c['status']] ?>"><?= $statusLabels[$c['status']] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
