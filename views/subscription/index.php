<?php
use App\Core\View;

$hasStripeSub = $subscription && !empty($subscription['stripe_subscription_id']) && in_array($subscription['status'], ['active', 'trialing'], true);
$activeModuleIds = [];
$activePackageIds = [];
foreach ($items as $it) {
    if ($it['item_type'] === 'module') { $activeModuleIds[] = (int) $it['module_id']; }
    if ($it['item_type'] === 'package') { $activePackageIds[] = (int) $it['package_id']; }
}
$statusLabels = ['trialing' => 'Essai', 'active' => 'Actif', 'past_due' => 'Paiement en retard', 'canceled' => 'Résilié', 'incomplete' => 'Incomplet'];
?>

<?php if (isset($_GET['checkout']) && $_GET['checkout'] === 'success'): ?>
  <div class="alert alert-success">Paiement confirmé, votre abonnement est en cours d'activation (peut prendre quelques secondes).</div>
<?php elseif (isset($_GET['checkout']) && $_GET['checkout'] === 'cancel'): ?>
  <div class="alert alert-warning">Paiement annulé.</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <h2 class="h6">Votre abonnement</h2>
    <?php if ($subscription): ?>
      <p class="mb-1">Offre actuelle : <strong><?= View::e($subscription['plan_name'] ?? 'Aucune') ?></strong>
        &middot; Statut : <span class="badge bg-secondary"><?= View::e($statusLabels[$subscription['status']] ?? $subscription['status']) ?></span>
        <?php if (!empty($subscription['cancel_at_period_end'])): ?><span class="badge bg-warning text-dark">Résiliation programmée</span><?php endif; ?>
      </p>
      <p class="mb-1 text-muted small">Membres : <?= $memberCount ?><?= $subscription['max_members'] !== null ? ' / ' . $subscription['max_members'] : ' (illimité)' ?></p>
      <?php if ($subscription['current_period_end']): ?>
        <p class="mb-0 text-muted small">Prochaine échéance : <?= View::date($subscription['current_period_end'], 'd/m/Y') ?></p>
      <?php endif; ?>
      <?php if ($hasStripeSub && empty($subscription['cancel_at_period_end'])): ?>
        <form method="post" action="<?= url('/subscription/cancel') ?>" class="mt-3" onsubmit="return confirm('Résilier votre abonnement à la fin de la période en cours ?');">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Résilier l'abonnement</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="text-muted mb-0">Aucun abonnement enregistré.</p>
    <?php endif; ?>
  </div>
</div>

<?php if (!$stripeConfigured): ?>
  <div class="alert alert-warning small">Le paiement en ligne des abonnements n'est pas encore configuré par la plateforme ; seules les offres/modules gratuits sont disponibles pour le moment.</div>
<?php endif; ?>

<?php if (!$hasStripeSub): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h2 class="h6">Souscrire à une offre</h2>
      <form method="post" action="<?= url('/subscription/checkout') ?>">
        <?= csrf_field() ?>
        <div class="row g-2 mb-3">
          <?php foreach ($plans as $p): ?>
            <div class="col-md-4">
              <label class="border rounded p-3 d-block h-100" style="cursor:pointer;">
                <input type="radio" name="plan_id" value="<?= $p['id'] ?>" <?= ($subscription['plan_id'] ?? null) == $p['id'] ? 'checked' : '' ?> class="form-check-input me-2">
                <strong><?= View::e($p['name']) ?></strong>
                <div class="text-muted small"><?= View::money((float) $p['monthly_price']) ?> / mois &middot; <?= $p['max_members'] !== null ? $p['max_members'] . ' membres max' : 'membres illimités' ?></div>
                <div class="small mt-1"><?= View::e($p['description']) ?></div>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($modules)): ?>
        <h3 class="h6 mt-3">Modules additionnels (optionnel)</h3>
        <div class="row g-2 mb-3">
          <?php foreach ($modules as $m): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input type="checkbox" name="module_ids[]" value="<?= $m['id'] ?>" class="form-check-input" id="chk_m<?= $m['id'] ?>">
                <label class="form-check-label" for="chk_m<?= $m['id'] ?>"><?= View::e($m['name']) ?> — <?= View::money((float) $m['monthly_price']) ?>/mois</label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($packages)): ?>
        <h3 class="h6">Packages (optionnel)</h3>
        <div class="row g-2 mb-3">
          <?php foreach ($packages as $pkg): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input type="checkbox" name="package_ids[]" value="<?= $pkg['id'] ?>" class="form-check-input" id="chk_p<?= $pkg['id'] ?>">
                <label class="form-check-label" for="chk_p<?= $pkg['id'] ?>"><?= View::e($pkg['name']) ?> — <?= View::money((float) $pkg['monthly_price']) ?>/mois</label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <button class="btn btn-primary">Souscrire</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="card mb-4">
    <div class="card-body">
      <h2 class="h6">Changer d'offre</h2>
      <form method="post" action="<?= url('/subscription/change-plan') ?>" class="d-flex gap-2">
        <?= csrf_field() ?>
        <select name="plan_id" class="form-select" style="max-width:320px;">
          <?php foreach ($plans as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($subscription['plan_id'] ?? null) == $p['id'] ? 'selected' : '' ?>><?= View::e($p['name']) ?> (<?= View::money((float) $p['monthly_price']) ?>/mois)</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary">Changer</button>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Modules actifs</div>
        <div class="list-group list-group-flush">
          <?php foreach ($items as $it): ?>
            <?php if ($it['item_type'] === 'plan') continue; ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <?= View::e($it['module_name'] ?? $it['package_name'] ?? '—') ?>
              <form method="post" action="<?= url('/subscription/remove-addon/' . $it['id']) ?>" onsubmit="return confirm('Retirer ce module ?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger">Retirer</button>
              </form>
            </div>
          <?php endforeach; ?>
          <?php if (count($items) <= 1): ?><div class="list-group-item text-muted">Aucun module additionnel actif.</div><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Ajouter un module</div>
        <div class="list-group list-group-flush">
          <?php foreach ($modules as $m): ?>
            <?php if (in_array($m['id'], $activeModuleIds, true)) continue; ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span><?= View::e($m['name']) ?> <span class="text-muted small">(<?= View::money((float) $m['monthly_price']) ?>/mois)</span></span>
              <form method="post" action="<?= url('/subscription/add-addon') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="module">
                <input type="hidden" name="item_id" value="<?= $m['id'] ?>">
                <button class="btn btn-sm btn-outline-primary">Activer</button>
              </form>
            </div>
          <?php endforeach; ?>
          <?php foreach ($packages as $pkg): ?>
            <?php if (in_array($pkg['id'], $activePackageIds, true)) continue; ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span><?= View::e($pkg['name']) ?> <span class="text-muted small">(package, <?= View::money((float) $pkg['monthly_price']) ?>/mois)</span></span>
              <form method="post" action="<?= url('/subscription/add-addon') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="package">
                <input type="hidden" name="item_id" value="<?= $pkg['id'] ?>">
                <button class="btn btn-sm btn-outline-primary">Activer</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
