<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Rentabilité par événement</h2>
  <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">← Rapports</a>
</div>

<p class="text-muted small">
  Marge = montant facturé au client (hors événements annulés) − coûts prestataires
  (event_providers.cost, alimentés manuellement ou automatiquement depuis un bon de
  commande confirmé). Le matériel réservé n'est pas chiffré dans l'app et n'est donc
  pas inclus dans le coût ci-dessous.
</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Événement</th><th>Client</th><th>Date</th><th class="text-end">Facturé</th><th class="text-end">Coût prestataires</th><th class="text-end">Marge</th><th class="text-end">%</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun événement.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $marginPct = $r['marginPercent'];
          $color = $marginPct === null ? 'secondary' : ($marginPct < 0 ? 'danger' : ($marginPct < 15 ? 'warning' : 'success'));
          $clientName = $r['company_name'] ?: trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        ?>
        <tr>
          <td><a href="<?= url('/events/' . $r['id']) ?>"><?= View::e($r['title']) ?></a></td>
          <td><?= View::e($clientName) ?></td>
          <td><?= View::date($r['event_date']) ?></td>
          <td class="text-end"><?= View::money((float) $r['invoiced']) ?></td>
          <td class="text-end text-danger"><?= View::money((float) $r['cost']) ?></td>
          <td class="text-end fw-semibold text-<?= $color ?>"><?= View::money((float) $r['margin']) ?></td>
          <td class="text-end"><?php if ($marginPct !== null): ?><span class="badge bg-<?= $color ?>"><?= $marginPct ?>%</span><?php else: ?>—<?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
