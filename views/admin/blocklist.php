<?php use App\Core\View; ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Adresses IP bloquées</div>
      <div class="card-body">
        <form method="post" action="<?= url('/admin/blocklist/ips') ?>" class="row g-2 mb-3">
          <?= csrf_field() ?>
          <div class="col-5"><input type="text" name="ip_address" class="form-control" placeholder="1.2.3.4" required></div>
          <div class="col-5"><input type="text" name="reason" class="form-control" placeholder="Motif (optionnel)"></div>
          <div class="col-2"><button class="btn btn-danger w-100">Bloquer</button></div>
        </form>
        <table class="table table-sm align-middle">
          <thead><tr><th>IP</th><th>Motif</th><th>Par</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($ips as $ip): ?>
            <tr>
              <td><?= View::e($ip['ip_address']) ?></td>
              <td class="small text-muted"><?= View::e($ip['reason']) ?></td>
              <td class="small text-muted"><?= View::e($ip['blocked_by_name'] ?? '—') ?></td>
              <td class="text-end">
                <form method="post" action="<?= url('/admin/blocklist/ips/' . $ip['id'] . '/delete') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-secondary">Débloquer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($ips)): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucune IP bloquée.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Adresses email bloquées</div>
      <div class="card-body">
        <form method="post" action="<?= url('/admin/blocklist/emails') ?>" class="row g-2 mb-3">
          <?= csrf_field() ?>
          <div class="col-5"><input type="email" name="email" class="form-control" placeholder="email@example.com" required></div>
          <div class="col-5"><input type="text" name="reason" class="form-control" placeholder="Motif (optionnel)"></div>
          <div class="col-2"><button class="btn btn-danger w-100">Bloquer</button></div>
        </form>
        <table class="table table-sm align-middle">
          <thead><tr><th>Email</th><th>Motif</th><th>Par</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($emails as $e): ?>
            <tr>
              <td><?= View::e($e['email']) ?></td>
              <td class="small text-muted"><?= View::e($e['reason']) ?></td>
              <td class="small text-muted"><?= View::e($e['blocked_by_name'] ?? '—') ?></td>
              <td class="text-end">
                <form method="post" action="<?= url('/admin/blocklist/emails/' . $e['id'] . '/delete') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-secondary">Débloquer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($emails)): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucun email bloqué.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
