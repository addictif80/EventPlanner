<?php use App\Core\View; ?>
<?php $statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'confirmed' => 'Confirmé', 'cancelled' => 'Annulé']; $statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'confirmed' => 'success', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Bons de commande</h2>
  <a href="<?= url('/purchase-orders/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>N°</th><th>Prestataire</th><th>Date</th><th>Total</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if (empty($orders)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun bon de commande.</td></tr><?php endif; ?>
        <?php foreach ($orders as $po): ?>
        <tr>
          <td><a href="<?= url('/purchase-orders/' . $po['id']) ?>"><?= View::e($po['po_number']) ?></a></td>
          <td><?= View::e($po['provider_name']) ?></td>
          <td><?= View::date($po['issue_date']) ?></td>
          <td><?= View::money((float)$po['total']) ?></td>
          <td><span class="badge bg-<?= $statusColors[$po['status']] ?>"><?= $statusLabels[$po['status']] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
