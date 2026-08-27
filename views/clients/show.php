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

<?php if (!empty($client['deletion_requested_at'])): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-exclamation-triangle me-2"></i>Ce client a demandé la suppression de ses données le <?= View::date($client['deletion_requested_at'], 'd/m/Y à H:i') ?>. Vérifiez vos obligations légales de conservation (notamment comptables sur les factures) avant de supprimer sa fiche.</span>
    <form method="post" action="<?= url('/clients/' . $client['id'] . '/erasure-request/dismiss') ?>" class="ms-3">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-dark">Marquer comme traitée</button>
    </form>
  </div>
<?php endif; ?>

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

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Notes internes</h3>
      <form method="post" action="<?= url('/clients/' . $client['id'] . '/notes') ?>" class="d-flex gap-2 mb-3">
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
    <?php $clientId = $client['id']; include __DIR__ . '/../partials/documents.php'; ?>
  </div>
</div>

<div class="mt-3">
  <?php if (\App\Core\ModuleAccess::has('client_portal')): ?>
  <form method="post" action="<?= url('/clients/' . $client['id'] . '/portal-link') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-link-45deg"></i> Générer un lien portail client</button>
  </form>
  <?php if (!empty($portalLink)): ?>
    <div class="alert alert-info mt-2 mb-0">Lien à transmettre au client (valable 30 jours) : <a href="<?= View::e($portalLink) ?>"><?= View::e($portalLink) ?></a></div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-8">
    <div class="card" id="messages">
      <div class="card-header">Messages avec le client</div>
      <div class="card-body">
        <div id="chat-thread" class="border rounded p-2 mb-2 d-flex flex-column gap-2" style="height:280px; overflow-y:auto; background:#fff;"></div>
        <form id="chat-form" method="post" action="<?= url('/clients/' . $client['id'] . '/messages') ?>" class="d-flex gap-2">
          <?= csrf_field() ?>
          <input type="text" name="body" class="form-control" placeholder="Votre message..." required autocomplete="off">
          <button class="btn btn-primary">Envoyer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var thread = document.getElementById('chat-thread');
  var form = document.getElementById('chat-form');
  var pollUrl = <?= json_encode(url('/clients/' . $client['id'] . '/messages.json')) ?>;

  function render(messages) {
    thread.innerHTML = '';
    messages.forEach(function (m) {
      var div = document.createElement('div');
      div.className = 'p-2 rounded ' + (m.sender_type === 'staff' ? 'bg-primary text-white' : 'bg-light border');
      div.style.maxWidth = '80%';
      if (m.sender_type === 'staff') { div.style.marginLeft = 'auto'; }
      var author = document.createElement('div');
      author.className = 'small ' + (m.sender_type === 'staff' ? 'text-white-50' : 'text-muted');
      author.textContent = m.author;
      var body = document.createElement('div');
      body.textContent = m.body;
      div.appendChild(author);
      div.appendChild(body);
      thread.appendChild(div);
    });
    thread.scrollTop = thread.scrollHeight;
  }

  function poll() {
    fetch(pollUrl).then(function (r) { return r.json(); }).then(render).catch(function () {});
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var data = new FormData(form);
    fetch(form.action, { method: 'POST', body: data }).then(function () {
      form.reset();
      poll();
    });
  });

  poll();
  setInterval(poll, 4000);
})();
</script>
