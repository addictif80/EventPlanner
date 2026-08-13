<?php use App\Core\View; ?>
<div class="row g-3 mb-4">
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">Organisations</div><div class="fs-3 fw-bold"><?= $stats['organizations'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">Suspendues</div><div class="fs-3 fw-bold text-danger"><?= $stats['suspended_organizations'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">Utilisateurs</div><div class="fs-3 fw-bold"><?= $stats['users'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">Tickets ouverts</div><div class="fs-3 fw-bold"><?= $stats['open_tickets'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">IP bloquées</div><div class="fs-3 fw-bold"><?= $stats['blocked_ips'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card text-center"><div class="card-body"><div class="text-muted small">Emails bloqués</div><div class="fs-3 fw-bold"><?= $stats['blocked_emails'] ?></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Dernières organisations</span>
        <a href="<?= url('/admin/organizations') ?>" class="small">Tout voir</a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($recentOrganizations as $org): ?>
        <a href="<?= url('/admin/organizations/' . $org['id']) ?>" class="list-group-item d-flex justify-content-between align-items-center">
          <?= View::e($org['name']) ?>
          <span class="badge bg-<?= $org['status'] === 'suspended' ? 'danger' : 'success' ?>"><?= View::e($org['status']) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (empty($recentOrganizations)): ?><div class="list-group-item text-muted">Aucune organisation.</div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Derniers tickets support</span>
        <a href="<?= url('/admin/tickets') ?>" class="small">Tout voir</a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($recentTickets as $t): ?>
        <a href="<?= url('/admin/tickets/' . $t['id']) ?>" class="list-group-item d-flex justify-content-between align-items-center">
          <span><?= View::e($t['subject']) ?> <span class="text-muted small">(<?= View::e($t['organization_name']) ?>)</span></span>
          <span class="badge bg-secondary"><?= View::e($t['status']) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (empty($recentTickets)): ?><div class="list-group-item text-muted">Aucun ticket.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
