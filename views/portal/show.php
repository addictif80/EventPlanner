<?php use App\Core\View; use App\Models\Client; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Espace client — <?= View::e(Client::displayName($client)) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>.chat-thread{height:280px;overflow-y:auto;background:#fff;} .chat-msg{max-width:80%;} .chat-msg.mine{margin-left:auto;}</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../partials/pwa_install_banner.php'; ?>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <h1 class="h4 mb-0">Bonjour <?= View::e(Client::displayName($client)) ?>,</h1>
    <div class="d-flex align-items-center gap-2">
      <?php
      $notifFeedUrl = url('/portal/' . $token . '/notifications.json');
      $notifMarkReadUrl = url('/portal/' . $token . '/notifications/__ID__/read');
      $notifMarkAllUrl = url('/portal/' . $token . '/notifications/read-all');
      $notifPushSubscribeUrl = url('/portal/' . $token . '/push/subscribe');
      $notifVapidKeyUrl = url('/push/vapid-public-key.json');
      include __DIR__ . '/../partials/notification_bell.php';
      ?>
      <a href="<?= url('/portal/' . $token . '/export.json') ?>" class="btn btn-outline-secondary btn-sm">Exporter mes données</a>
    </div>
  </div>

  <?php if (!empty($client['deletion_requested_at'])): ?>
    <div class="alert alert-info small">Votre demande de suppression de données a bien été transmise à l'organisateur.</div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Vos événements</h2>
        <ul class="list-unstyled mb-0">
          <?php foreach ($events as $e): ?>
            <li class="py-1 border-bottom"><?= View::e($e['title']) ?><br><span class="text-muted small"><?= View::date($e['event_date']) ?></span></li>
          <?php endforeach; ?>
          <?php if (empty($events)): ?><li class="text-muted small">Aucun événement.</li><?php endif; ?>
        </ul>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Vos devis</h2>
        <ul class="list-unstyled mb-0">
          <?php foreach ($quotes as $q): ?>
            <li class="py-2 border-bottom">
              <?= View::e($q['quote_number']) ?> — <?= View::money((float)$q['total']) ?><br>
              <span class="badge bg-light text-dark mb-1"><?= View::e($q['status']) ?></span>
              <?php if ($q['status'] === 'sent'): ?>
                <div class="d-flex gap-2 mt-1">
                  <form method="post" action="<?= url('/portal/' . $token . '/quotes/' . $q['id'] . '/accept') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-success">Accepter</button>
                  </form>
                  <form method="post" action="<?= url('/portal/' . $token . '/quotes/' . $q['id'] . '/refuse') ?>" onsubmit="return confirm('Refuser ce devis ?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger">Refuser</button>
                  </form>
                </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if (empty($quotes)): ?><li class="text-muted small">Aucun devis.</li><?php endif; ?>
        </ul>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Vos factures</h2>
        <ul class="list-unstyled mb-0">
          <?php foreach ($invoices as $inv): $remaining = (float) $inv['total'] - (float) $inv['amount_paid']; ?>
            <li class="py-2 border-bottom">
              <?= View::e($inv['invoice_number']) ?> — <?= View::money((float)$inv['total']) ?><br>
              <span class="badge bg-light text-dark"><?= View::e($inv['status']) ?></span>
              Payé : <?= View::money((float)$inv['amount_paid']) ?>
              <?php if ($remaining > 0 && $stripeAvailable): ?>
                <form method="post" action="<?= url('/portal/' . $token . '/invoices/' . $inv['id'] . '/pay') ?>" class="mt-1">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-primary">Payer <?= View::money($remaining) ?></button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if (empty($invoices)): ?><li class="text-muted small">Aucune facture.</li><?php endif; ?>
        </ul>
      </div></div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-md-8">
      <div class="card" id="messages">
        <div class="card-header">Discuter avec l'organisateur</div>
        <div class="card-body">
          <div id="chat-thread" class="chat-thread border rounded p-2 mb-2 d-flex flex-column gap-2"></div>
          <form id="chat-form" method="post" action="<?= url('/portal/' . $token . '/messages') ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="text" name="body" class="form-control" placeholder="Votre message..." required autocomplete="off">
            <button class="btn btn-primary">Envoyer</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-danger">
        <div class="card-header text-danger">Mes données</div>
        <div class="card-body">
          <p class="text-muted small">Vous pouvez demander la suppression de vos données. L'organisateur pourra y donner suite, sous réserve d'éventuelles obligations légales de conservation (ex. factures).</p>
          <form method="post" action="<?= url('/portal/' . $token . '/erasure-request') ?>" onsubmit="return confirm('Demander la suppression de vos données ?');">
            <?= csrf_field() ?>
            <button class="btn btn-outline-danger btn-sm" <?= !empty($client['deletion_requested_at']) ? 'disabled' : '' ?>>Demander la suppression de mes données</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var thread = document.getElementById('chat-thread');
  var form = document.getElementById('chat-form');
  var pollUrl = <?= json_encode(url('/portal/' . $token . '/messages.json')) ?>;

  function render(messages) {
    thread.innerHTML = '';
    messages.forEach(function (m) {
      var div = document.createElement('div');
      div.className = 'chat-msg p-2 rounded ' + (m.sender_type === 'client' ? 'mine bg-primary text-white' : 'bg-light border');
      var author = document.createElement('div');
      author.className = 'small ' + (m.sender_type === 'client' ? 'text-white-50' : 'text-muted');
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
</body>
</html>
