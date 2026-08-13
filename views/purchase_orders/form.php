<?php use App\Core\View; ?>
<div class="card"><div class="card-body">
  <form method="post" action="<?= url('/purchase-orders') ?>" id="po-form">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Prestataire</label>
        <select name="provider_id" class="form-select" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($providers as $p): ?><option value="<?= $p['id'] ?>"><?= View::e($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Événement lié</label>
        <select name="event_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($events as $e): ?><option value="<?= $e['id'] ?>"><?= View::e($e['title']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
    </div>

    <table class="table" id="items-table">
      <thead><tr><th>Description</th><th style="width:100px;">Qté</th><th style="width:140px;">Prix unitaire</th><th style="width:140px;">Total</th><th style="width:40px;"></th></tr></thead>
      <tbody>
        <tr class="item-row">
          <td><input type="text" name="description[]" class="form-control"></td>
          <td><input type="text" name="quantity[]" class="form-control qty" value="1"></td>
          <td><input type="text" name="unit_price[]" class="form-control price" value="0"></td>
          <td class="line-total pt-2">0,00 €</td>
          <td><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="bi bi-trash"></i></button></td>
        </tr>
      </tbody>
    </table>
    <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-plus-lg"></i> Ajouter une ligne</button>

    <div class="row justify-content-end mb-3">
      <div class="col-md-4 d-flex justify-content-between fs-5"><strong>Total</strong><strong id="total-display">0,00 €</strong></div>
    </div>

    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control mb-4" rows="3"></textarea>

    <button class="btn btn-primary">Créer le bon de commande</button>
    <a href="<?= url('/purchase-orders') ?>" class="btn btn-outline-secondary">Annuler</a>
  </form>
</div></div>

<script>
function recalc() {
  let total = 0;
  document.querySelectorAll('#items-table .item-row').forEach(row => {
    const qty = parseFloat((row.querySelector('.qty').value || '0').replace(',', '.')) || 0;
    const price = parseFloat((row.querySelector('.price').value || '0').replace(',', '.')) || 0;
    const lineTotal = qty * price;
    row.querySelector('.line-total').textContent = lineTotal.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
    total += lineTotal;
  });
  document.getElementById('total-display').textContent = total.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
}
document.getElementById('items-table').addEventListener('input', recalc);
document.getElementById('add-row').addEventListener('click', () => {
  const tbody = document.querySelector('#items-table tbody');
  const tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = `<td><input type="text" name="description[]" class="form-control"></td>
    <td><input type="text" name="quantity[]" class="form-control qty" value="1"></td>
    <td><input type="text" name="unit_price[]" class="form-control price" value="0"></td>
    <td class="line-total pt-2">0,00 €</td>
    <td><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="bi bi-trash"></i></button></td>`;
  tbody.appendChild(tr);
});
document.getElementById('items-table').addEventListener('click', (e) => {
  if (e.target.closest('.remove-row')) { e.target.closest('tr').remove(); recalc(); }
});
recalc();
</script>
