<?php use App\Core\View; ?>
<?php if (!$stripeConfigured): ?>
  <div class="alert alert-warning small">La clé Stripe plateforme n'est pas configurée : les offres payantes ne pourront pas être facturées tant que vous n'aurez pas renseigné vos clés dans <a href="<?= url('/admin/settings') ?>">Paramètres système</a>.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h6 mb-0">Offres (plans)</h2>
  <a href="<?= url('/admin/offers/plans/create') ?>" class="btn btn-sm btn-primary">Nouvelle offre</a>
</div>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Prix / mois</th><th>Membres max</th><th>Défaut inscription</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($plans as $p): ?>
        <tr>
          <td><?= View::e($p['name']) ?></td>
          <td><?= View::money((float) $p['monthly_price']) ?></td>
          <td><?= $p['max_members'] !== null ? $p['max_members'] : 'Illimité' ?></td>
          <td><?= $p['is_default_signup'] ? '<span class="badge bg-info">Oui</span>' : '' ?></td>
          <td><span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <a href="<?= url('/admin/offers/plans/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <?php if ($p['is_active']): ?>
            <form method="post" action="<?= url('/admin/offers/plans/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Désactiver cette offre ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Désactiver</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h6 mb-0">Modules (suppléments à l'unité)</h2>
  <a href="<?= url('/admin/offers/modules/create') ?>" class="btn btn-sm btn-primary">Nouveau module</a>
</div>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Clé</th><th>Prix / mois</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($modules as $m): ?>
        <tr>
          <td><?= View::e($m['name']) ?></td>
          <td><code><?= View::e($m['module_key']) ?></code></td>
          <td><?= View::money((float) $m['monthly_price']) ?></td>
          <td><span class="badge bg-<?= $m['is_active'] ? 'success' : 'secondary' ?>"><?= $m['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
          <td class="text-end"><a href="<?= url('/admin/offers/modules/' . $m['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h6 mb-0">Packages de modules</h2>
  <a href="<?= url('/admin/offers/packages/create') ?>" class="btn btn-sm btn-primary">Nouveau package</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Prix / mois</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($packages as $pkg): ?>
        <tr>
          <td><?= View::e($pkg['name']) ?></td>
          <td><?= View::money((float) $pkg['monthly_price']) ?></td>
          <td><span class="badge bg-<?= $pkg['is_active'] ? 'success' : 'secondary' ?>"><?= $pkg['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <a href="<?= url('/admin/offers/packages/' . $pkg['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <?php if ($pkg['is_active']): ?>
            <form method="post" action="<?= url('/admin/offers/packages/' . $pkg['id'] . '/delete') ?>" onsubmit="return confirm('Désactiver ce package ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Désactiver</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
