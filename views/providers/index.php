<?php use App\Core\View; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="h5 mb-0">Prestataires</h2>
  <a href="<?= url('/providers/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Nom</th><th>Catégorie</th><th>Contact</th><th>Email / Tél.</th><th>Note</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($providers)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun prestataire.</td></tr><?php endif; ?>
        <?php foreach ($providers as $p): ?>
        <tr>
          <td><?= View::e($p['name']) ?></td>
          <td><span class="badge bg-secondary-subtle text-dark"><?= View::e($p['category']) ?></span></td>
          <td><?= View::e($p['contact_name']) ?></td>
          <td><?= View::e($p['email']) ?> <?= View::e($p['phone']) ?></td>
          <td><?= $p['rating'] ? str_repeat('★', (int)$p['rating']) : '—' ?></td>
          <td class="text-end"><a href="<?= url('/providers/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
