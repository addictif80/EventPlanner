<?php use App\Core\View; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card"><div class="card-body text-center">
    <div class="text-muted small">Taux de conversion des devis</div><div class="fs-3 fw-bold"><?= $conversionRate ?>%</div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center">
    <div class="text-muted small">Devis acceptés</div><div class="fs-3 fw-bold text-success"><?= $quoteStats['accepted'] ?? 0 ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center">
    <div class="text-muted small">Devis refusés</div><div class="fs-3 fw-bold text-danger"><?= $quoteStats['refused'] ?? 0 ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center">
    <div class="text-muted small">Satisfaction moyenne</div><div class="fs-3 fw-bold"><?= $satisfactionAvg !== null ? $satisfactionAvg . '/5' : '—' ?></div>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card"><div class="card-body">
      <h3 class="h6">Chiffre d'affaires encaissé — 12 derniers mois</h3>
      <canvas id="chart-revenue" height="90"></canvas>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">CA par type d'événement</h3>
      <canvas id="chart-type"></canvas>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Top clients (CA encaissé)</h3>
      <canvas id="chart-clients"></canvas>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Top prestataires (coût)</h3>
      <canvas id="chart-providers"></canvas>
    </div></div>
  </div>
  <div class="col-md-12">
    <div class="card"><div class="card-body">
      <h3 class="h6">Prévisionnel de trésorerie (factures à encaisser)</h3>
      <canvas id="chart-forecast" height="80"></canvas>
    </div></div>
  </div>
</div>

<div class="mt-3">
  <a href="<?= url('/export/invoices.csv') ?>" class="btn btn-outline-dark btn-sm"><i class="bi bi-download"></i> Exporter les factures (CSV comptable)</a>
</div>

<script>
const palette = ['#4c6ef5', '#12b886', '#f59f00', '#e64980', '#7048e8', '#15aabf', '#fa5252', '#82c91e'];

new Chart(document.getElementById('chart-revenue'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($revenueByMonth, 'ym')) ?>,
    datasets: [{ label: 'CA encaissé (€)', data: <?= json_encode(array_map('floatval', array_column($revenueByMonth, 'total'))) ?>, backgroundColor: '#4c6ef5' }]
  },
  options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chart-type'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($revenueByEventType, 'label')) ?>,
    datasets: [{ data: <?= json_encode(array_map('floatval', array_column($revenueByEventType, 'total'))) ?>, backgroundColor: palette }]
  }
});

new Chart(document.getElementById('chart-clients'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($topClients, 'label')) ?>,
    datasets: [{ label: 'CA (€)', data: <?= json_encode(array_map('floatval', array_column($topClients, 'total'))) ?>, backgroundColor: '#12b886' }]
  },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chart-providers'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($topProviders, 'label')) ?>,
    datasets: [{ label: 'Coût (€)', data: <?= json_encode(array_map('floatval', array_column($topProviders, 'total'))) ?>, backgroundColor: '#f59f00' }]
  },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chart-forecast'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($forecast, 'ym')) ?>,
    datasets: [{ label: 'Reste à encaisser (€)', data: <?= json_encode(array_map('floatval', array_column($forecast, 'total'))) ?>, backgroundColor: '#e64980' }]
  },
  options: { plugins: { legend: { display: false } } }
});
</script>
