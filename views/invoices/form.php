<?php use App\Core\View; use App\Models\Client; ?>
<?php
$inv = $invoice ?? [];
$action = $invoice ? url('/invoices/' . $invoice['id']) : url('/invoices');
$taxRate = $inv['tax_rate'] ?? $company['default_tax_rate'] ?? 20;
$rows = !empty($items) ? $items : [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>" id="invoice-form">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (($inv['client_id'] ?? $selectedClientId) == $c['id']) ? 'selected' : '' ?>><?= View::e(Client::displayName($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Événement lié (optionnel)</label>
        <select name="event_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>" <?= (($inv['event_id'] ?? $selectedEventId) == $e['id']) ? 'selected' : '' ?>><?= View::e($e['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
          <?php foreach (['standalone' => 'Standard', 'deposit' => 'Acompte', 'final' => 'Solde'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($inv['type'] ?? 'standalone') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Date d'émission</label>
        <input type="date" name="issue_date" class="form-control" value="<?= View::e($inv['issue_date'] ?? date('Y-m-d')) ?>" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Échéance</label>
        <input type="date" name="due_date" class="form-control" value="<?= View::e($inv['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
      </div>
    </div>

    <table class="table" id="items-table">
      <thead><tr><th>Description</th><th style="width:100px;">Qté</th><th style="width:140px;">Prix unitaire</th><th style="width:140px;">Total</th><th style="width:40px;"></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr class="item-row">
          <td><input type="text" name="description[]" class="form-control" value="<?= View::e($row['description']) ?>"></td>
          <td><input type="text" name="quantity[]" class="form-control qty" value="<?= View::e((string)$row['quantity']) ?>"></td>
          <td><input type="text" name="unit_price[]" class="form-control price" value="<?= View::e((string)$row['unit_price']) ?>"></td>
          <td class="line-total pt-2">0,00 €</td>
          <td><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="bi bi-trash"></i></button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-plus-lg"></i> Ajouter une ligne</button>

    <div class="row justify-content-end">
      <div class="col-md-4">
        <div class="d-flex justify-content-between"><span>Sous-total</span><strong id="subtotal-display">0,00 €</strong></div>
        <div class="d-flex justify-content-between align-items-center my-2">
          <label class="mb-0">TVA (%)</label>
          <input type="text" name="tax_rate" id="tax-rate" class="form-control form-control-sm" style="width:80px;" value="<?= View::e((string)$taxRate) ?>">
        </div>
        <div class="d-flex justify-content-between"><span>Total TTC</span><strong id="total-display" class="fs-5">0,00 €</strong></div>
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label">Notes / conditions de paiement</label>
      <textarea name="notes" class="form-control" rows="3"><?= View::e($inv['notes'] ?? '') ?></textarea>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/invoices') ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>
</div></div>

<script>
function recalc() {
  let subtotal = 0;
  document.querySelectorAll('#items-table .item-row').forEach(row => {
    const qty = parseFloat((row.querySelector('.qty').value || '0').replace(',', '.')) || 0;
    const price = parseFloat((row.querySelector('.price').value || '0').replace(',', '.')) || 0;
    const total = qty * price;
    row.querySelector('.line-total').textContent = total.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
    subtotal += total;
  });
  const taxRate = parseFloat((document.getElementById('tax-rate').value || '0').replace(',', '.')) || 0;
  const taxAmount = subtotal * taxRate / 100;
  const total = subtotal + taxAmount;
  document.getElementById('subtotal-display').textContent = subtotal.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
  document.getElementById('total-display').textContent = total.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
}

document.getElementById('items-table').addEventListener('input', recalc);
document.getElementById('tax-rate').addEventListener('input', recalc);

document.getElementById('add-row').addEventListener('click', () => {
  const tbody = document.querySelector('#items-table tbody');
  const tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = `
    <td><input type="text" name="description[]" class="form-control"></td>
    <td><input type="text" name="quantity[]" class="form-control qty" value="1"></td>
    <td><input type="text" name="unit_price[]" class="form-control price" value="0"></td>
    <td class="line-total pt-2">0,00 €</td>
    <td><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="bi bi-trash"></i></button></td>`;
  tbody.appendChild(tr);
});

document.getElementById('items-table').addEventListener('click', (e) => {
  if (e.target.closest('.remove-row')) {
    e.target.closest('tr').remove();
    recalc();
  }
});

recalc();
</script>
