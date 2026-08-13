<?php use App\Core\View; ?>
<?php
$statusLabels = ['draft' => 'Brouillon', 'confirmed' => 'Confirmé', 'in_progress' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'];
$statusColors = ['draft' => 'secondary', 'confirmed' => 'primary', 'in_progress' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
$clientName = $event['company_name'] ?: trim($event['first_name'] . ' ' . $event['last_name']);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1"><?= View::e($event['title']) ?></h2>
    <span class="badge bg-<?= $statusColors[$event['status']] ?>"><?= $statusLabels[$event['status']] ?></span>
    <span class="text-muted ms-2"><?= View::date($event['event_date']) ?><?= $event['end_date'] ? ' → ' . View::date($event['end_date']) : '' ?></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/quotes/create') ?>?event_id=<?= $event['id'] ?>&client_id=<?= $event['client_id'] ?>" class="btn btn-outline-primary btn-sm">+ Devis</a>
    <a href="<?= url('/events/' . $event['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Modifier</a>
    <form method="post" action="<?= url('/events/' . $event['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cet événement ?');">
      <?= csrf_field() ?>
      <button class="btn btn-outline-danger btn-sm">Supprimer</button>
    </form>
  </div>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <a href="<?= url('/events/' . $event['id'] . '/guests') ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-person-lines-fill"></i> Invités</a>
  <a href="<?= url('/events/' . $event['id'] . '/tickets') ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-ticket-perforated"></i> Billetterie</a>
  <a href="<?= url('/events/' . $event['id'] . '/dayof') ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-clock-history"></i> Jour-J</a>
  <a href="<?= url('/contracts/create') ?>?event_id=<?= $event['id'] ?>&client_id=<?= $event['client_id'] ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-text"></i> Contrat</a>
  <?php if ($event['status'] === 'completed'): ?>
  <form method="post" action="<?= url('/events/' . $event['id'] . '/survey/send') ?>" onsubmit="return confirm('Envoyer le sondage de satisfaction au client ?');">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-star"></i> Envoyer le sondage de satisfaction</button>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h3 class="h6">Informations</h3>
      <p class="mb-1"><strong>Client :</strong> <a href="<?= url('/clients/' . $event['client_id']) ?>"><?= View::e($clientName) ?></a></p>
      <p class="mb-1"><strong>Type :</strong> <?= View::e($event['type_name']) ?: '—' ?></p>
      <p class="mb-1"><strong>Lieu :</strong> <?= View::e($event['venue_name'] ?: $event['location']) ?: '—' ?></p>
      <p class="mb-1"><strong>Invités :</strong> <?= $event['guests_count'] ?? '—' ?></p>
      <p class="mb-0"><strong>Budget :</strong> <?= $event['budget'] !== null ? View::money((float)$event['budget']) : '—' ?></p>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card h-100"><div class="card-body">
      <h3 class="h6">Description</h3>
      <p class="mb-0 text-muted"><?= nl2br(View::e($event['description'])) ?: '—' ?></p>
    </div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Checklist / Tâches</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/tasks') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input type="text" name="title" class="form-control form-control-sm" placeholder="Nouvelle tâche..." required>
        <input type="date" name="due_date" class="form-control form-control-sm" style="max-width:150px;">
        <select name="assigned_to" class="form-select form-select-sm" style="max-width:150px;">
          <option value="">Assigner...</option>
          <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= View::e($u['name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary">+</button>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($tasks as $t): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <div>
            <form method="post" action="<?= url('/tasks/' . $t['id'] . '/status') ?>" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="<?= $t['status'] === 'done' ? 'todo' : 'done' ?>">
              <button class="btn btn-sm btn-link p-0 me-2" title="Basculer le statut">
                <i class="bi <?= $t['status'] === 'done' ? 'bi-check-square-fill text-success' : 'bi-square' ?>"></i>
              </button>
            </form>
            <span class="<?= $t['status'] === 'done' ? 'text-decoration-line-through text-muted' : '' ?>"><?= View::e($t['title']) ?></span>
            <?php if ($t['due_date']): ?><span class="text-muted small ms-1">(<?= View::date($t['due_date']) ?>)</span><?php endif; ?>
          </div>
          <form method="post" action="<?= url('/tasks/' . $t['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($tasks)): ?><li class="list-group-item px-0 text-muted small">Aucune tâche.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Prestataires</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/providers') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <select name="provider_id" class="form-select form-select-sm" required>
          <option value="">Ajouter un prestataire...</option>
          <?php foreach ($providers as $p): ?><option value="<?= $p['id'] ?>"><?= View::e($p['name']) ?> (<?= View::e($p['category']) ?>)</option><?php endforeach; ?>
        </select>
        <input type="text" name="cost" class="form-control form-control-sm" placeholder="Coût" style="max-width:100px;">
        <button class="btn btn-sm btn-primary">+</button>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($eventProviders as $ep): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><?= View::e($ep['provider_name']) ?> <span class="text-muted small">(<?= View::e($ep['category']) ?>)</span><?php if ($ep['cost']): ?> — <?= View::money((float)$ep['cost']) ?><?php endif; ?></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/providers/' . $ep['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($eventProviders)): ?><li class="list-group-item px-0 text-muted small">Aucun prestataire lié.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Matériel réservé</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/equipment') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <select name="equipment_id" class="form-select form-select-sm" required>
          <option value="">Ajouter du matériel...</option>
          <?php foreach ($equipmentList as $eq): ?><option value="<?= $eq['id'] ?>"><?= View::e($eq['name']) ?> (<?= $eq['total_quantity'] ?> dispo.)</option><?php endforeach; ?>
        </select>
        <input type="number" name="quantity" class="form-control form-control-sm" value="1" style="max-width:80px;">
        <button class="btn btn-sm btn-primary">+</button>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($equipmentBookings as $eb): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><?= View::e($eb['name']) ?> — Qté : <?= $eb['quantity'] ?></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/equipment/' . $eb['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($equipmentBookings)): ?><li class="list-group-item px-0 text-muted small">Aucun matériel réservé.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Devis</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($quotes as $q): ?>
          <li class="py-1 border-bottom"><a href="<?= url('/quotes/' . $q['id']) ?>"><?= View::e($q['quote_number']) ?></a> — <?= View::money((float)$q['total']) ?> <span class="badge bg-light text-dark"><?= View::e($q['status']) ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($quotes)): ?><li class="text-muted small">Aucun devis.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Factures</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($invoices as $inv): ?>
          <li class="py-1 border-bottom"><a href="<?= url('/invoices/' . $inv['id']) ?>"><?= View::e($inv['invoice_number']) ?></a> — <?= View::money((float)$inv['total']) ?> <span class="badge bg-light text-dark"><?= View::e($inv['status']) ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?><li class="text-muted small">Aucune facture.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Notes internes</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/notes') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input type="text" name="body" class="form-control form-control-sm" placeholder="Ajouter une note..." required>
        <button class="btn btn-sm btn-primary">+</button>
      </form>
      <ul class="list-unstyled mb-0">
        <?php foreach (($notes ?? []) as $n): ?>
          <li class="py-1 border-bottom small"><?= View::e($n['body']) ?> <span class="text-muted">— <?= View::e($n['author_name'] ?? '') ?>, <?= View::date($n['created_at'], 'd/m/Y H:i') ?></span></li>
        <?php endforeach; ?>
        <?php if (empty($notes)): ?><li class="text-muted small">Aucune note.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-6">
    <?php $eventId = $event['id']; include __DIR__ . '/../partials/documents.php'; ?>
  </div>
</div>
