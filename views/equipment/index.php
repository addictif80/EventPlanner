<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Matériel / stock</h2>
  <a href="<?= url('/equipment/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvel article</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Nom</th><th>Catégorie</th><th>Quantité totale</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($equipment)): ?><tr><td colspan="4" class="text-center text-muted py-4">Aucun matériel.</td></tr><?php endif; ?>
        <?php foreach ($equipment as $e): ?>
        <tr>
          <td><?= View::e($e['name']) ?></td>
          <td><span class="badge bg-secondary-subtle text-dark"><?= View::e($e['category']) ?></span></td>
          <td><?= $e['total_quantity'] ?></td>
          <td class="text-end"><a href="<?= url('/equipment/' . $e['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
