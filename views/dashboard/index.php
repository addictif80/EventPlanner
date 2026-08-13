<?php use App\Core\View; ?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card"><div class="card-body">
    <div class="text-muted small">Clients</div><div class="fs-3 fw-bold"><?= $stats['clients_count'] ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body">
    <div class="text-muted small">Événements à venir</div><div class="fs-3 fw-bold"><?= $stats['upcoming_events_count'] ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body">
    <div class="text-muted small">Devis en attente</div><div class="fs-3 fw-bold"><?= $stats['quotes_pending'] ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body">
    <div class="text-muted small">Impayés</div><div class="fs-3 fw-bold text-danger"><?= View::money($stats['invoices_unpaid_amount']) ?></div>
  </div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6"><div class="card"><div class="card-body">
    <div class="text-muted small">Chiffre d'affaires encaissé — ce mois-ci</div><div class="fs-4 fw-bold"><?= View::money($stats['revenue_month']) ?></div>
  </div></div></div>
  <div class="col-md-6"><div class="card"><div class="card-body">
    <div class="text-muted small">Chiffre d'affaires encaissé — cette année</div><div class="fs-4 fw-bold"><?= View::money($stats['revenue_year']) ?></div>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Prochains événements</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($upcomingEvents as $e): ?>
          <li class="py-1 border-bottom">
            <a href="<?= url('/events/' . $e['id']) ?>"><?= View::e($e['title']) ?></a>
            <div class="text-muted small"><?= View::date($e['event_date']) ?> — <?= View::e($e['company_name'] ?: trim($e['first_name'] . ' ' . $e['last_name'])) ?></div>
          </li>
        <?php endforeach; ?>
        <?php if (empty($upcomingEvents)): ?><li class="text-muted small">Aucun événement à venir.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Devis récents</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($recentQuotes as $q): ?>
          <li class="py-1 border-bottom">
            <a href="<?= url('/quotes/' . $q['id']) ?>"><?= View::e($q['quote_number']) ?></a>
            <div class="text-muted small"><?= View::e($q['company_name'] ?: trim($q['first_name'] . ' ' . $q['last_name'])) ?> — <?= View::money((float)$q['total']) ?></div>
          </li>
        <?php endforeach; ?>
        <?php if (empty($recentQuotes)): ?><li class="text-muted small">Aucun devis.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Factures en retard (<?= $stats['invoices_overdue_count'] ?>)</h3>
      <ul class="list-unstyled mb-0">
        <?php foreach ($overdueInvoices as $inv): ?>
          <li class="py-1 border-bottom">
            <a href="<?= url('/invoices/' . $inv['id']) ?>"><?= View::e($inv['invoice_number']) ?></a>
            <div class="text-muted small"><?= View::e($inv['company_name'] ?: trim($inv['first_name'] . ' ' . $inv['last_name'])) ?> — échéance <?= View::date($inv['due_date']) ?></div>
          </li>
        <?php endforeach; ?>
        <?php if (empty($overdueInvoices)): ?><li class="text-muted small">Aucune facture en retard.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
</div>
