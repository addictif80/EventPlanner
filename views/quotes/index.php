<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'accepted' => 'Accepté', 'refused' => 'Refusé', 'expired' => 'Expiré'];
$statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'accepted' => 'success', 'refused' => 'danger', 'expired' => 'warning'];
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Devis</h2>
  <a href="<?= url('/quotes/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau devis</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>N°</th><th>Client</th><th>Date</th><th>Validité</th><th>Total</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if (empty($quotes)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun devis.</td></tr><?php endif; ?>
        <?php foreach ($quotes as $q): ?>
        <tr>
          <td><a href="<?= url('/quotes/' . $q['id']) ?>"><?= View::e($q['quote_number']) ?></a></td>
          <td><?= View::e($q['company_name'] ?: trim($q['first_name'] . ' ' . $q['last_name'])) ?></td>
          <td><?= View::date($q['issue_date']) ?></td>
          <td><?= View::date($q['valid_until']) ?></td>
          <td><?= View::money((float)$q['total']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$q['status']] ?>"><?= $statusLabels[$q['status']] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
