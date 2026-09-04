<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Rendez-vous</h2>
  <a href="<?= url('/booking-settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Réglages</a>
</div>

<div class="card mb-4">
  <div class="card-header py-2 fw-semibold">À venir</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Quand</th><th>Prospect</th><th>Contact</th><th>Sujet</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($upcoming)): ?><tr><td colspan="5" class="text-center text-muted py-3">Aucun rendez-vous à venir.</td></tr><?php endif; ?>
        <?php foreach ($upcoming as $a): ?>
        <tr>
          <td><?= View::date($a['starts_at'], 'd/m/Y H:i') ?></td>
          <td><?= View::e($a['prospect_name']) ?></td>
          <td><a href="mailto:<?= View::e($a['prospect_email']) ?>"><?= View::e($a['prospect_email']) ?></a><?php if ($a['prospect_phone']): ?><br><span class="text-muted small"><?= View::e($a['prospect_phone']) ?></span><?php endif; ?></td>
          <td><?= View::e($a['subject']) ?></td>
          <td class="text-end">
            <form method="post" action="<?= url('/appointments/' . $a['id'] . '/cancel') ?>" onsubmit="return confirm('Annuler ce rendez-vous ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Annuler</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header py-2 fw-semibold">Historique</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Quand</th><th>Prospect</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if (empty($history)): ?><tr><td colspan="3" class="text-center text-muted py-3">Aucun rendez-vous.</td></tr><?php endif; ?>
        <?php foreach ($history as $a): ?>
        <tr>
          <td><?= View::date($a['starts_at'], 'd/m/Y H:i') ?></td>
          <td><?= View::e($a['prospect_name']) ?></td>
          <td><span class="badge bg-<?= $a['status'] === 'confirmed' ? 'success' : 'secondary' ?>"><?= $a['status'] === 'confirmed' ? 'Confirmé' : 'Annulé' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
