<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Historique des caisses</h2>
  <a href="<?= url('/pos') ?>" class="btn btn-primary btn-sm"><i class="bi bi-cash-coin"></i> Aller à la caisse</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>#</th><th>Événement</th><th>Ouverte par</th><th>Ouverture</th><th>Ventes</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($sessions)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune session de caisse.</td></tr><?php endif; ?>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><?= (int) $s['id'] ?></td>
          <td><?= View::e($s['event_title'] ?? '—') ?></td>
          <td><?= View::e($s['opened_by_name'] ?? '—') ?></td>
          <td><?= View::date($s['opened_at'], 'd/m/Y H:i') ?></td>
          <td><?= View::money((float) $s['sales_total']) ?></td>
          <td><span class="badge bg-<?= $s['status'] === 'open' ? 'success' : 'secondary' ?>"><?= $s['status'] === 'open' ? 'Ouverte' : 'Clôturée' ?></span></td>
          <td class="text-end">
            <a href="<?= url($s['status'] === 'open' ? '/pos/' . $s['id'] : '/pos/sessions/' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary"><?= $s['status'] === 'open' ? 'Reprendre' : 'Détail' ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
