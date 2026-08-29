<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Aide à la déclaration URSSAF</h2>
  <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">← Rapports</a>
</div>

<div class="alert alert-info small">
  <i class="bi bi-info-circle me-1"></i>
  En micro-entreprise, le chiffre d'affaires à déclarer à l'URSSAF est celui <strong>réellement encaissé</strong> sur la période (et non celui facturé). Les montants ci-dessous reprennent vos paiements enregistrés, par mois et par trimestre.
  Cette page ne calcule pas de cotisations : les taux évoluent chaque année — vérifiez le taux en vigueur sur <a href="https://www.autoentrepreneur.urssaf.fr" target="_blank" rel="noopener">autoentrepreneur.urssaf.fr</a> avant de déclarer.
  <?php if (!empty($company['legal_form'])): ?>
    <br>Forme juridique renseignée : <strong><?= View::e($company['legal_form']) ?></strong>.
  <?php endif; ?>
</div>

<form method="get" action="<?= url('/reports/urssaf') ?>" class="d-flex align-items-center gap-2 mb-3">
  <label class="form-label mb-0">Année</label>
  <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
    <?php foreach ($availableYears as $y): ?>
      <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
    <?php endforeach; ?>
  </select>
  <a href="<?= url('/reports/urssaf/export.csv?year=' . $year) ?>" class="btn btn-outline-dark btn-sm ms-auto"><i class="bi bi-download me-1"></i>Exporter en CSV</a>
</form>

<div class="row g-3 mb-4">
  <?php foreach ([1 => 'T1 (jan-mars)', 2 => 'T2 (avr-juin)', 3 => 'T3 (juil-sept)', 4 => 'T4 (oct-déc)'] as $q => $label): ?>
    <div class="col-md-3">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small"><?= $label ?></div>
        <div class="fs-4 fw-bold"><?= View::money($byQuarter[$q]) ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between">
    <span>Détail mensuel — <?= $year ?></span>
    <strong>Total <?= $year ?> : <?= View::money($yearTotal) ?></strong>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Mois</th><th>Trimestre</th><th class="text-end">Chiffre d'affaires encaissé</th></tr></thead>
      <tbody>
        <?php
        $monthNames = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
        ?>
        <?php foreach ($byMonth as $month => $total): ?>
          <tr>
            <td><?= $monthNames[$month] ?></td>
            <td>T<?= (int) ceil($month / 3) ?></td>
            <td class="text-end"><?= View::money($total) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
