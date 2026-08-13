<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Catalogue produits / prestations</h2>
  <a href="<?= url('/products/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvel article</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Nom</th><th>Catégorie</th><th>Prix unitaire</th><th>Unité</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($products)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun article.</td></tr><?php endif; ?>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><?= View::e($p['name']) ?></td>
          <td><span class="badge bg-secondary-subtle text-dark"><?= View::e($p['category']) ?></span></td>
          <td><?= View::money((float)$p['unit_price']) ?></td>
          <td><?= View::e($p['unit']) ?></td>
          <td class="text-end"><a href="<?= url('/products/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
