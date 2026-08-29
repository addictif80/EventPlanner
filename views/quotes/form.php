<?php use App\Core\View; use App\Models\Client; ?>
<?php
$q = $quote ?? [];
$action = $quote ? url('/quotes/' . $quote['id']) : url('/quotes');
$taxRate = $q['tax_rate'] ?? $company['default_tax_rate'] ?? 20;
$rows = !empty($items) ? $items : [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
?>
<?php if (\App\Core\ModuleAccess::has('ai_assistant')): ?>
<div class="card mb-3 border-primary-subtle"><div class="card-body">
  <h3 class="h6"><i class="bi bi-stars me-1"></i>Générer les lignes avec l'IA</h3>
  <p class="text-muted small mb-2">Décrivez le besoin du client en langage libre, l'IA propose des lignes de devis à ajuster.</p>
  <div class="d-flex gap-2">
    <textarea id="ai-brief" class="form-control" rows="2" placeholder="Ex : mariage 120 invités, traiteur, DJ, décoration florale, location de la salle jusqu'à 2h du matin..."></textarea>
    <button type="button" id="ai-draft-btn" class="btn btn-outline-primary text-nowrap">Générer</button>
  </div>
  <div id="ai-draft-status" class="small text-muted mt-2"></div>
</div></div>
<?php endif; ?>

<div class="card"><div class="card-body">
  <form method="post" action="<?= $action ?>" id="quote-form">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (($q['client_id'] ?? $selectedClientId) == $c['id']) ? 'selected' : '' ?>><?= View::e(Client::displayName($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Événement lié (optionnel)</label>
        <select name="event_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>" <?= (($q['event_id'] ?? $selectedEventId) == $e['id']) ? 'selected' : '' ?>><?= View::e($e['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Date d'émission</label>
        <input type="date" name="issue_date" class="form-control" value="<?= View::e($q['issue_date'] ?? date('Y-m-d')) ?>" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Valide jusqu'au</label>
        <input type="date" name="valid_until" class="form-control" value="<?= View::e($q['valid_until'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
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
      <label class="form-label">Notes / conditions</label>
      <textarea name="notes" class="form-control" rows="3"><?= View::e($q['notes'] ?? '') ?></textarea>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/quotes') ?>" class="btn btn-outline-secondary">Annuler</a>
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

const aiBtn = document.getElementById('ai-draft-btn');
if (aiBtn) {
  aiBtn.addEventListener('click', () => {
    const brief = document.getElementById('ai-brief').value.trim();
    const status = document.getElementById('ai-draft-status');
    if (!brief) { status.textContent = 'Décrivez le besoin du client.'; return; }

    aiBtn.disabled = true;
    status.textContent = 'Génération en cours...';

    const eventId = document.querySelector('select[name="event_id"]').value;
    const csrfToken = document.querySelector('#quote-form input[name="csrf_token"]').value;

    fetch(<?= json_encode(url('/quotes/ai-draft')) ?>, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&brief=' + encodeURIComponent(brief) + '&event_id=' + encodeURIComponent(eventId),
    }).then(r => r.json()).then(data => {
      aiBtn.disabled = false;
      if (data.error) { status.textContent = data.error; return; }

      const tbody = document.querySelector('#items-table tbody');
      tbody.innerHTML = '';
      (data.items || []).forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
          <td><input type="text" name="description[]" class="form-control" value="${item.description.replace(/"/g, '&quot;')}"></td>
          <td><input type="text" name="quantity[]" class="form-control qty" value="${item.quantity}"></td>
          <td><input type="text" name="unit_price[]" class="form-control price" value="${item.unit_price}"></td>
          <td class="line-total pt-2">0,00 €</td>
          <td><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(tr);
      });

      const notesField = document.querySelector('textarea[name="notes"]');
      if (notesField && !notesField.value.trim() && data.notes) notesField.value = data.notes;

      status.textContent = 'Lignes générées — relisez et ajustez avant d\'enregistrer.';
      recalc();
    }).catch(() => {
      aiBtn.disabled = false;
      status.textContent = "Échec de la génération.";
    });
  });
}
</script>
