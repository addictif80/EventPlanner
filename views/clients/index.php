<?php use App\Core\View; use App\Models\Client; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="search" name="q" class="form-control" placeholder="Rechercher un client..." value="<?= View::e($term) ?>">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= url('/clients/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouveau client</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Type</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Ville</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Aucun client pour le moment.</td></tr>
        <?php endif; ?>
        <?php foreach ($clients as $client): ?>
        <tr>
          <td>
            <a href="<?= url('/clients/' . $client['id']) ?>"><?= View::e(Client::displayName($client)) ?></a>
            <?php if (!empty($client['deletion_requested_at'])): ?>
              <span class="badge bg-warning text-dark ms-1" title="Demande de suppression de données">RGPD</span>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-secondary-subtle text-dark"><?= View::e($client['type']) ?></span></td>
          <td><?= View::e($client['email']) ?></td>
          <td><?= View::e($client['phone']) ?></td>
          <td><?= View::e($client['city']) ?></td>
          <td class="text-end"><a href="<?= url('/clients/' . $client['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
