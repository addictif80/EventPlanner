<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'accepted' => 'Accepté', 'refused' => 'Refusé', 'expired' => 'Expiré'];
$statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'accepted' => 'success', 'refused' => 'danger', 'expired' => 'warning'];
$clientName = $quote['company_name'] ?: trim($quote['first_name'] . ' ' . $quote['last_name']);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1">Devis <?= View::e($quote['quote_number']) ?></h2>
    <span class="badge bg-<?= $statusColors[$quote['status']] ?>"><?= $statusLabels[$quote['status']] ?></span>
    <span class="text-muted ms-2">Client : <a href="<?= url('/clients/' . $quote['client_id']) ?>"><?= View::e($clientName) ?></a></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/quotes/' . $quote['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Imprimer / PDF</a>
    <a href="<?= url('/quotes/' . $quote['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Modifier</a>

    <form method="post" action="<?= url('/quotes/' . $quote['id'] . '/send') ?>" onsubmit="return confirm('Envoyer ce devis par email au client ?');">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-sm" <?= $smtpConfigured ? '' : 'disabled title="Configurez le serveur SMTP dans Paramètres"' ?>>
        <i class="bi bi-envelope"></i> Envoyer par email
      </button>
    </form>

    <?php if ($quote['status'] === 'accepted'): ?>
    <form method="post" action="<?= url('/quotes/' . $quote['id'] . '/convert') ?>" onsubmit="return confirm('Générer une facture à partir de ce devis ?');">
      <?= csrf_field() ?>
      <button class="btn btn-success btn-sm"><i class="bi bi-receipt"></i> Convertir en facture</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!$smtpConfigured): ?>
  <div class="alert alert-warning">Aucun serveur SMTP n'est configuré : l'envoi par email est désactivé. <a href="<?= url('/settings') ?>">Configurer maintenant</a>.</div>
<?php endif; ?>

<div class="card mb-3"><div class="card-body">
  <form method="post" action="<?= url('/quotes/' . $quote['id'] . '/status') ?>" class="d-flex align-items-center gap-2">
    <?= csrf_field() ?>
    <label class="mb-0">Statut :</label>
    <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $quote['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
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
  <div class="row justify-content-end">
    <div class="col-md-4">
      <div class="d-flex justify-content-between"><span>Sous-total</span><span><?= View::money((float)$quote['subtotal']) ?></span></div>
      <div class="d-flex justify-content-between"><span>TVA (<?= View::e((string)$quote['tax_rate']) ?>%)</span><span><?= View::money((float)$quote['tax_amount']) ?></span></div>
      <div class="d-flex justify-content-between fs-5"><strong>Total TTC</strong><strong><?= View::money((float)$quote['total']) ?></strong></div>
    </div>
  </div>
  <?php if (!empty($quote['notes'])): ?>
    <hr><p class="text-muted"><?= nl2br(View::e($quote['notes'])) ?></p>
  <?php endif; ?>
</div></div>
