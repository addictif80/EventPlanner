<?php use App\Core\View; use App\Models\Client; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Espace client — <?= View::e(Client::displayName($client)) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h1 class="h4 mb-4">Bonjour <?= View::e(Client::displayName($client)) ?>,</h1>

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
            <li class="py-1 border-bottom"><?= View::e($q['quote_number']) ?> — <?= View::money((float)$q['total']) ?><br><span class="badge bg-light text-dark"><?= View::e($q['status']) ?></span></li>
          <?php endforeach; ?>
          <?php if (empty($quotes)): ?><li class="text-muted small">Aucun devis.</li><?php endif; ?>
        </ul>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Vos factures</h2>
        <ul class="list-unstyled mb-0">
          <?php foreach ($invoices as $inv): ?>
            <li class="py-1 border-bottom">
              <?= View::e($inv['invoice_number']) ?> — <?= View::money((float)$inv['total']) ?><br>
              <span class="badge bg-light text-dark"><?= View::e($inv['status']) ?></span>
              Payé : <?= View::money((float)$inv['amount_paid']) ?>
            </li>
          <?php endforeach; ?>
          <?php if (empty($invoices)): ?><li class="text-muted small">Aucune facture.</li><?php endif; ?>
        </ul>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
