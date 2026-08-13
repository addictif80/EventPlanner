<?php use App\Core\View; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Nom du fichier</th><th>Organisation</th><th>Catégorie</th><th>Envoyé par</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($documents as $d): ?>
        <tr>
          <td><?= View::e($d['original_name']) ?></td>
          <td><?= View::e($d['organization_name']) ?></td>
          <td><?= View::e($d['category']) ?></td>
          <td><?= View::e($d['uploaded_by_name'] ?? '—') ?></td>
          <td><?= View::date($d['created_at'], 'd/m/Y H:i') ?></td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <a href="<?= url('/admin/documents/' . $d['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary">Télécharger</a>
            <form method="post" action="<?= url('/admin/documents/' . $d['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce document ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($documents)): ?><tr><td colspan="6" class="text-muted text-center py-4">Aucun document.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
