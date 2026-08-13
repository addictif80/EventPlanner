<?php use App\Core\View; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Nom</th><th>Statut</th><th>Utilisateurs</th><th>Événements</th><th>Créée le</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($organizations as $org): ?>
        <tr>
          <td><a href="<?= url('/admin/organizations/' . $org['id']) ?>"><?= View::e($org['name']) ?></a></td>
          <td><span class="badge bg-<?= $org['status'] === 'suspended' ? 'danger' : 'success' ?>"><?= View::e($org['status']) ?></span></td>
          <td><?= (int) $org['user_count'] ?></td>
          <td><?= (int) $org['event_count'] ?></td>
          <td><?= View::date($org['created_at'], 'd/m/Y') ?></td>
          <td class="text-end"><a href="<?= url('/admin/organizations/' . $org['id']) ?>" class="btn btn-sm btn-outline-secondary">Détails</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($organizations)): ?><tr><td colspan="6" class="text-muted text-center py-4">Aucune organisation.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
