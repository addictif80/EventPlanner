<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée', 'partially_paid' => 'Partiellement payée', 'overdue' => 'En retard', 'cancelled' => 'Annulée'];
$statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'paid' => 'success', 'partially_paid' => 'warning', 'overdue' => 'danger', 'cancelled' => 'dark'];
$clientName = $invoice['company_name'] ?: trim($invoice['first_name'] . ' ' . $invoice['last_name']);
$remaining = (float)$invoice['total'] - (float)$invoice['amount_paid'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1">Facture <?= View::e($invoice['invoice_number']) ?></h2>
    <span class="badge bg-<?= $statusColors[$invoice['status']] ?>"><?= $statusLabels[$invoice['status']] ?></span>
    <span class="text-muted ms-2">Client : <a href="<?= url('/clients/' . $invoice['client_id']) ?>"><?= View::e($clientName) ?></a></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/invoices/' . $invoice['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Imprimer / PDF</a>
    <a href="<?= url('/invoices/' . $invoice['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Modifier</a>
    <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/send') ?>" onsubmit="return confirm('Envoyer cette facture par email au client ?');">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-sm" <?= $smtpConfigured ? '' : 'disabled title="Configurez le serveur SMTP dans Paramètres"' ?>>
        <i class="bi bi-envelope"></i> Envoyer par email
      </button>
    </form>
    <?php if (in_array($invoice['status'], ['sent', 'partially_paid', 'overdue'], true)): ?>
    <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/remind') ?>" onsubmit="return confirm('Envoyer une relance de paiement au client ?');">
      <?= csrf_field() ?>
      <button class="btn btn-outline-warning btn-sm" <?= $smtpConfigured ? '' : 'disabled' ?>><i class="bi bi-bell"></i> Relancer</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!$smtpConfigured): ?>
  <div class="alert alert-warning">Aucun serveur SMTP n'est configuré : l'envoi par email est désactivé. <a href="<?= url('/settings') ?>">Configurer maintenant</a>.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Total TTC</div><div class="fs-4 fw-bold"><?= View::money((float)$invoice['total']) ?></div></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Payé</div><div class="fs-4 fw-bold text-success"><?= View::money((float)$invoice['amount_paid']) ?></div></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Restant dû</div><div class="fs-4 fw-bold <?= $remaining > 0 ? 'text-danger' : '' ?>"><?= View::money($remaining) ?></div></div></div></div>
</div>

<div class="card mb-3"><div class="card-body">
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
      <div class="d-flex justify-content-between"><span>Sous-total</span><span><?= View::money((float)$invoice['subtotal']) ?></span></div>
      <div class="d-flex justify-content-between"><span>TVA (<?= View::e((string)$invoice['tax_rate']) ?>%)</span><span><?= View::money((float)$invoice['tax_amount']) ?></span></div>
      <div class="d-flex justify-content-between fs-5"><strong>Total TTC</strong><strong><?= View::money((float)$invoice['total']) ?></strong></div>
    </div>
  </div>
</div></div>

<div class="card"><div class="card-body">
  <h3 class="h6">Paiements</h3>
  <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/payments') ?>" class="row g-2 mb-3 align-items-end">
    <?= csrf_field() ?>
    <div class="col-md-2"><label class="form-label small">Montant</label><input type="text" name="amount" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
    <div class="col-md-2">
      <label class="form-label small">Méthode</label>
      <select name="method" class="form-select form-select-sm">
        <?php foreach (['virement' => 'Virement', 'cb' => 'Carte bancaire', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'autre' => 'Autre'] as $val => $label): ?>
          <option value="<?= $val ?>"><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label small">Référence</label><input type="text" name="reference" class="form-control form-control-sm"></div>
    <div class="col-md-3"><button class="btn btn-sm btn-primary">Ajouter le paiement</button></div>
  </form>
  <table class="table table-sm">
    <thead><tr><th>Date</th><th>Montant</th><th>Méthode</th><th>Référence</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= View::date($p['payment_date']) ?></td>
        <td><?= View::money((float)$p['amount']) ?></td>
        <td><?= View::e($p['method']) ?></td>
        <td><?= View::e($p['reference']) ?></td>
        <td>
          <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/payments/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce paiement ?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($payments)): ?><tr><td colspan="5" class="text-muted small text-center py-3">Aucun paiement enregistré.</td></tr><?php endif; ?>
    </tbody>
  </table>

  <?php if (!empty($stripeEnabled) && \App\Core\ModuleAccess::has('stripe_payments')): ?>
  <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/stripe-link') ?>" class="mt-2">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-credit-card"></i> Générer un lien de paiement en ligne (Stripe)</button>
  </form>
  <?php if (!empty($stripePaymentUrl)): ?>
    <div class="alert alert-info mt-2 mb-0">Lien de paiement : <a href="<?= View::e($stripePaymentUrl) ?>" target="_blank"><?= View::e($stripePaymentUrl) ?></a></div>
  <?php endif; ?>
  <?php endif; ?>
</div></div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Avoirs</h3>
      <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/credit-notes') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-4"><input type="text" name="amount" class="form-control form-control-sm" placeholder="Montant" required></div>
        <div class="col-6"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Motif"></div>
        <div class="col-2"><button class="btn btn-sm btn-outline-dark w-100">+</button></div>
      </form>
      <ul class="list-unstyled mb-0">
        <?php foreach (($creditNotes ?? []) as $cn): ?>
          <li class="py-1 border-bottom small"><a href="<?= url('/credit-notes/' . $cn['id'] . '/print') ?>" target="_blank"><?= View::e($cn['credit_note_number']) ?></a> — <?= View::money((float)$cn['amount']) ?> <?= View::e($cn['reason']) ?></li>
        <?php endforeach; ?>
        <?php if (empty($creditNotes)): ?><li class="text-muted small">Aucun avoir.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Facture récurrente</h3>
      <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/recurring') ?>" class="d-flex align-items-center gap-2 mb-2">
        <?= csrf_field() ?>
        <div class="form-check">
          <input type="checkbox" name="is_recurring" class="form-check-input" id="is_recurring" <?= !empty($invoice['is_recurring']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_recurring">Récurrente</label>
        </div>
        <select name="recurrence_interval" class="form-select form-select-sm" style="width:auto;">
          <?php foreach (['monthly' => 'Mensuelle', 'quarterly' => 'Trimestrielle', 'yearly' => 'Annuelle'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($invoice['recurrence_interval'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-secondary">Enregistrer</button>
      </form>
      <?php if (!empty($invoice['is_recurring'])): ?>
        <p class="small text-muted">Prochaine échéance prévue : <?= View::date($invoice['recurrence_next_date']) ?></p>
        <form method="post" action="<?= url('/invoices/' . $invoice['id'] . '/generate-next') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-primary">Générer la prochaine facture maintenant</button>
        </form>
      <?php endif; ?>
    </div></div>
  </div>
</div>
