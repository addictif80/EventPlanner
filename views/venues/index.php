<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Lieux</h2>
  <a href="<?= url('/venues/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Nom</th><th>Ville</th><th>Capacité</th><th>Contact</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($venues)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun lieu.</td></tr><?php endif; ?>
        <?php foreach ($venues as $v): ?>
        <tr>
          <td><?= View::e($v['name']) ?></td>
          <td><?= View::e($v['city']) ?></td>
          <td><?= $v['capacity'] ? $v['capacity'] . ' pers.' : '—' ?></td>
          <td><?= View::e($v['contact_name']) ?></td>
          <td class="text-end"><a href="<?= url('/venues/' . $v['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
