<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée', 'partially_paid' => 'Partiellement payée', 'overdue' => 'En retard', 'cancelled' => 'Annulée'];
$statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'paid' => 'success', 'partially_paid' => 'warning', 'overdue' => 'danger', 'cancelled' => 'dark'];
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Factures</h2>
  <a href="<?= url('/invoices/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvelle facture</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>N°</th><th>Client</th><th>Date</th><th>Échéance</th><th>Total</th><th>Payé</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if (empty($invoices)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune facture.</td></tr><?php endif; ?>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><a href="<?= url('/invoices/' . $inv['id']) ?>"><?= View::e($inv['invoice_number']) ?></a></td>
          <td><?= View::e($inv['company_name'] ?: trim($inv['first_name'] . ' ' . $inv['last_name'])) ?></td>
          <td><?= View::date($inv['issue_date']) ?></td>
          <td><?= View::date($inv['due_date']) ?></td>
          <td><?= View::money((float)$inv['total']) ?></td>
          <td><?= View::money((float)$inv['amount_paid']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$inv['status']] ?>"><?= $statusLabels[$inv['status']] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
