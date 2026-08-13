<?php use App\Core\View; ?>
<?php $statusColors = ['valid' => 'success', 'checked_in' => 'primary', 'cancelled' => 'secondary']; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Billetterie — <?= View::e($event['title']) ?></h2>
  <div class="d-flex gap-2">
    <a href="<?= url('/events/' . $event['id'] . '/checkin') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-qr-code-scan"></i> Check-in</a>
    <a href="<?= url('/events/' . $event['id']) ?>" class="btn btn-outline-secondary btn-sm">← Retour</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <h3 class="h6">Catégories de billets</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/ticket-categories') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="Nom" required></div>
        <div class="col-3"><input type="text" name="price" class="form-control form-control-sm" placeholder="Prix"></div>
        <div class="col-3"><input type="number" name="quantity_available" class="form-control form-control-sm" placeholder="Qté"></div>
        <div class="col-1"><button class="btn btn-sm btn-primary">+</button></div>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($categories as $c): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><?= View::e($c['name']) ?> — <?= View::money((float)$c['price']) ?> <span class="text-muted small">(<?= (int)$c['sold_count'] ?>/<?= $c['quantity_available'] ?>)</span></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/ticket-categories/' . $c['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?><li class="list-group-item px-0 text-muted small">Aucune catégorie.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-7">
    <div class="card"><div class="card-body">
      <h3 class="h6">Générer un billet</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/tickets/generate') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-4">
          <select name="ticket_category_id" class="form-select form-select-sm" required>
            <option value="">Catégorie...</option>
            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-4"><input type="text" name="holder_name" class="form-control form-control-sm" placeholder="Nom du porteur"></div>
        <div class="col-3"><input type="email" name="holder_email" class="form-control form-control-sm" placeholder="Email"></div>
        <div class="col-1"><button class="btn btn-sm btn-primary">+</button></div>
      </form>

      <table class="table table-sm">
        <thead><tr><th>Code</th><th>Catégorie</th><th>Porteur</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($tickets as $t): ?>
          <tr>
            <td><code><?= View::e($t['code']) ?></code></td>
            <td><?= View::e($t['category_name']) ?></td>
            <td><?= View::e($t['holder_name']) ?></td>
            <td><span class="badge bg-<?= $statusColors[$t['status']] ?>"><?= View::e($t['status']) ?></span></td>
            <td>
              <?php if ($t['status'] === 'valid'): ?>
              <form method="post" action="<?= url('/events/' . $event['id'] . '/tickets/' . $t['id'] . '/cancel') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-link text-danger p-0">Annuler</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($tickets)): ?><tr><td colspan="5" class="text-center text-muted py-3">Aucun billet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>
