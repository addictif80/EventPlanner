<?php use App\Core\View; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h2 class="h5 mb-0">Session de caisse #<?= (int) $session['id'] ?><?php if (!empty($session['event_title'])): ?> — <?= View::e($session['event_title']) ?><?php endif; ?></h2>
  <div class="d-flex gap-2">
    <?php if ($session['status'] === 'open'): ?><a href="<?= url('/pos/' . $session['id']) ?>" class="btn btn-primary btn-sm">Reprendre la caisse</a><?php endif; ?>
    <a href="<?= url('/pos/sessions') ?>" class="btn btn-outline-secondary btn-sm">← Historique</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Fond de départ</div><div class="fs-5 fw-bold"><?= View::money((float) $session['opening_float']) ?></div></div></div></div>
  <?php foreach ($totalsByMethod as $method => $total): ?>
  <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted small"><?= ['cash' => 'Espèces', 'card' => 'Carte', 'other' => 'Autre'][$method] ?? $method ?></div><div class="fs-5 fw-bold"><?= View::money((float) $total) ?></div></div></div></div>
  <?php endforeach; ?>
  <?php if ($session['status'] === 'closed'): ?>
  <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Écart de caisse</div><div class="fs-5 fw-bold <?= (float) $session['cash_difference'] == 0 ? '' : ((float) $session['cash_difference'] > 0 ? 'text-success' : 'text-danger') ?>"><?= View::money((float) $session['cash_difference']) ?></div></div></div></div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header py-2 fw-semibold">Ventes</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>N°</th><th>Client</th><th>Paiement</th><th class="text-end">Montant</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($sales)): ?><tr><td colspan="5" class="text-center text-muted py-3">Aucune vente.</td></tr><?php endif; ?>
            <?php foreach ($sales as $s): ?>
            <tr>
              <td><?= View::e($s['sale_number']) ?></td>
              <td><?= View::e($s['buyer_name'] ?: trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: '—') ?></td>
              <td><?= ['cash' => 'Espèces', 'card' => 'Carte', 'other' => 'Autre'][$s['payment_method']] ?? $s['payment_method'] ?></td>
              <td class="text-end"><?= View::money((float) $s['total']) ?></td>
              <td class="text-end"><a href="<?= url('/pos/sales/' . $s['id']) ?>" class="btn btn-sm btn-link">Détail</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card">
      <div class="card-header py-2 fw-semibold">Mouvements de caisse</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($movements as $m): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center small">
          <span><?= $m['type'] === 'in' ? '+' : '-' ?> <?= View::money((float) $m['amount']) ?> <span class="text-muted">— <?= View::e($m['reason'] ?: 'Sans motif') ?></span></span>
          <span class="text-muted"><?= View::date($m['created_at'], 'd/m H:i') ?></span>
        </li>
        <?php endforeach; ?>
        <?php if (empty($movements)): ?><li class="list-group-item text-muted small py-3 text-center">Aucun mouvement.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
