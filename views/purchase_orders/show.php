<?php use App\Core\View; ?>
<?php $statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'confirmed' => 'Confirmé', 'cancelled' => 'Annulé']; $statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'confirmed' => 'success', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1">Bon de commande <?= View::e($po['po_number']) ?></h2>
    <span class="badge bg-<?= $statusColors[$po['status']] ?>"><?= $statusLabels[$po['status']] ?></span>
    <span class="text-muted ms-2">Prestataire : <?= View::e($po['provider_name']) ?></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/purchase-orders/' . $po['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Imprimer</a>
    <form method="post" action="<?= url('/purchase-orders/' . $po['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce bon de commande ?');">
      <?= csrf_field() ?>
      <button class="btn btn-outline-danger btn-sm">Supprimer</button>
    </form>
  </div>
</div>

<div class="card mb-3"><div class="card-body">
  <form method="post" action="<?= url('/purchase-orders/' . $po['id'] . '/status') ?>" class="d-flex align-items-center gap-2">
    <?= csrf_field() ?>
    <label class="mb-0">Statut :</label>
    <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $po['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <table class="table">
    <thead><tr><th>Description</th><th>Qté</th><th>Prix unitaire</th><th class="text-end">Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= View::e($item['description']) ?></td>
        <td><?= View::e((string)$item['quantity']) ?></td>
        <td><?= View::money((float)$item['unit_price']) ?></td>
        <td class="text-end"><?= View::money((float)$item['total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-end fs-5"><strong>Total : <?= View::money((float)$po['total']) ?></strong></div>
  <?php if (!empty($po['notes'])): ?><hr><p class="text-muted"><?= nl2br(View::e($po['notes'])) ?></p><?php endif; ?>
</div></div>
