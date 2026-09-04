<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Avis clients</h2>
  <a href="<?= url('/settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shop-window me-1"></i>Réglages de l'annuaire public (Paramètres)</a>
</div>

<p class="text-muted small">Seuls les avis pour lesquels le client a coché l'autorisation de publication peuvent être publiés sur votre page de l'annuaire public. Vous choisissez ensuite lesquels afficher.</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Client</th><th>Événement</th><th>Note</th><th>Commentaire</th><th>Consentement</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($surveys)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun avis reçu pour l'instant.</td></tr><?php endif; ?>
        <?php foreach ($surveys as $s): ?>
        <tr>
          <td><?= View::e($s['company_name'] ?: trim($s['first_name'] . ' ' . $s['last_name'])) ?></td>
          <td><?= View::e($s['event_title']) ?></td>
          <td><?= $s['rating'] ? str_repeat('★', (int) $s['rating']) . str_repeat('☆', 5 - (int) $s['rating']) : '—' ?></td>
          <td class="small" style="max-width:280px;"><?= View::e($s['comments']) ?></td>
          <td><?= $s['consent_public'] ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>' ?></td>
          <td class="text-end">
            <?php if ($s['consent_public']): ?>
            <form method="post" action="<?= url('/surveys/' . $s['id'] . '/publish') ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm <?= $s['is_published'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                <?= $s['is_published'] ? 'Publié ✓' : 'Publier' ?>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
