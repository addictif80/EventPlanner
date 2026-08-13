<?php use App\Core\View; use App\Models\Client; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1"><?= View::e(Client::displayName($client)) ?></h2>
    <span class="badge bg-secondary-subtle text-dark"><?= View::e($client['type']) ?></span>
    <?php foreach (array_filter(array_map('trim', explode(',', $client['tags'] ?? ''))) as $tag): ?>
      <span class="badge bg-info-subtle text-dark"><?= View::e($tag) ?></span>
    <?php endforeach; ?>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/events/create') ?>?client_id=<?= $client['id'] ?>" class="btn btn-outline-primary btn-sm">+ Événement</a>
    <a href="<?= url('/clients/' . $client['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Modifier</a>
    <form method="post" action="<?= url('/clients/' . $client['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce client et toutes ses données liées ?');">
      <?= csrf_field() ?>
      <button class="btn btn-outline-danger btn-sm">Supprimer</button>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card h-100"><div class="card-body">
      <h3 class="h6">Coordonnées</h3>
      <p class="mb-1"><i class="bi bi-envelope me-2"></i><?= View::e($client['email']) ?: '—' ?></p>
      <p class="mb-1"><i class="bi bi-telephone me-2"></i><?= View::e($client['phone']) ?: '—' ?></p>
      <p class="mb-0"><i class="bi bi-geo-alt me-2"></i><?= View::e(trim($client['address'] . ' ' . $client['postal_code'] . ' ' . $client['city'])) ?: '—' ?></p>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card h-100"><div class="card-body">
      <h3 class="h6">Notes</h3>
      <p class="mb-0 text-muted"><?= nl2br(View::e($client['notes'])) ?: '—' ?></p>
    </div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Événements (<?= count($events) ?>)</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($events as $e): ?>
          <li class="py-1 border-bottom"><a href="<?= url('/events/' . $e['id']) ?>"><?= View::e($e['title']) ?></a> <span class="text-muted small"><?= View::date($e['event_date']) ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($events)): ?><li class="text-muted small">Aucun</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Devis (<?= count($quotes) ?>)</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($quotes as $q): ?>
          <li class="py-1 border-bottom"><a href="<?= url('/quotes/' . $q['id']) ?>"><?= View::e($q['quote_number']) ?></a> <span class="text-muted small"><?= View::money((float)$q['total']) ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($quotes)): ?><li class="text-muted small">Aucun</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Factures (<?= count($invoices) ?>)</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($invoices as $inv): ?>
          <li class="py-1 border-bottom"><a href="<?= url('/invoices/' . $inv['id']) ?>"><?= View::e($inv['invoice_number']) ?></a> <span class="text-muted small"><?= View::money((float)$inv['total']) ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?><li class="text-muted small">Aucune</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
</div>
