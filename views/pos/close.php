<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Clôturer la caisse</h2>
  <a href="<?= url('/pos/' . $session['id']) ?>" class="btn btn-outline-secondary btn-sm">← Retour à la caisse</a>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Résumé de la session</h3>
      <table class="table table-sm mb-0">
        <tbody>
          <tr><td>Fond de départ</td><td class="text-end"><?= View::money((float) $session['opening_float']) ?></td></tr>
          <?php foreach ($totalsByMethod as $method => $total): ?>
          <tr><td><?= ['cash' => 'Ventes espèces', 'card' => 'Ventes carte', 'other' => 'Ventes autre'][$method] ?? $method ?></td><td class="text-end"><?= View::money((float) $total) ?></td></tr>
          <?php endforeach; ?>
          <tr class="table-light"><th>Espèces attendues en caisse</th><th class="text-end"><?= View::money($expectedCash) ?></th></tr>
        </tbody>
      </table>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Comptage de caisse</h3>
      <form method="post" action="<?= url('/pos/' . $session['id'] . '/close') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Montant compté en espèces</label>
          <input type="text" name="counted_cash" id="counted-cash" class="form-control form-control-lg" value="<?= View::e((string) $expectedCash) ?>" required>
        </div>
        <p class="text-muted small">Écart : <span id="cash-diff">0,00 €</span></p>
        <div class="mb-3">
          <label class="form-label">Notes (optionnel)</label>
          <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn btn-danger w-100 btn-lg">Clôturer définitivement</button>
      </form>
    </div></div>
  </div>
</div>

<script>
(function () {
  var expected = <?= json_encode($expectedCash) ?>;
  var input = document.getElementById('counted-cash');
  var diff = document.getElementById('cash-diff');
  function update() {
    var counted = parseFloat((input.value || '0').replace(',', '.')) || 0;
    var d = counted - expected;
    diff.textContent = (d >= 0 ? '+' : '') + d.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €';
    diff.className = d === 0 ? '' : (d > 0 ? 'text-success' : 'text-danger');
  }
  input.addEventListener('input', update);
  update();
})();
</script>
