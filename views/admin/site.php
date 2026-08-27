<?php use App\Core\View; ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h6 mb-0">Pages</h2>
  <a href="<?= url('/admin/pages/create') ?>" class="btn btn-sm btn-primary">Nouvelle page</a>
</div>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Titre</th><th>URL</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pages as $p): ?>
        <tr>
          <td><?= View::e($p['title']) ?></td>
          <td class="small text-muted">/page/<?= View::e($p['slug']) ?></td>
          <td><span class="badge bg-<?= $p['is_published'] ? 'success' : 'secondary' ?>"><?= $p['is_published'] ? 'Publiée' : 'Brouillon' ?></span></td>
          <td class="text-end d-flex gap-2 justify-content-end">
            <a href="<?= url('/admin/pages/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <form method="post" action="<?= url('/admin/pages/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer définitivement cette page ?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($pages)): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucune page pour le moment.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$menuSections = [
    'header' => ['title' => 'Menu haut (en-tête de la landing page)', 'items' => $headerItems],
    'footer' => ['title' => 'Menu pied de page', 'items' => $footerItems],
];
?>
<div class="row g-3">
  <?php foreach ($menuSections as $location => $section): ?>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><?= View::e($section['title']) ?></div>
        <div class="card-body">
          <table class="table table-sm align-middle mb-3">
            <thead><tr><th>Libellé</th><th>Lien</th><th>Ordre</th><th>Actif</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($section['items'] as $item): $formId = 'menu-item-' . $item['id']; ?>
              <tr>
                <td>
                  <form id="<?= $formId ?>" method="post" action="<?= url('/admin/menu/' . $item['id']) ?>"><?= csrf_field() ?></form>
                  <input form="<?= $formId ?>" type="text" name="label" class="form-control form-control-sm" value="<?= View::e($item['label']) ?>" required>
                </td>
                <td><input form="<?= $formId ?>" type="text" name="url" class="form-control form-control-sm" value="<?= View::e($item['url']) ?>" required></td>
                <td><input form="<?= $formId ?>" type="number" name="sort_order" class="form-control form-control-sm" style="width:70px;" value="<?= (int) $item['sort_order'] ?>"></td>
                <td class="text-center"><input form="<?= $formId ?>" type="checkbox" name="is_active" value="1" class="form-check-input" <?= $item['is_active'] ? 'checked' : '' ?>></td>
                <td class="text-end d-flex gap-2 justify-content-end">
                  <button form="<?= $formId ?>" class="btn btn-sm btn-outline-secondary">Enregistrer</button>
                  <form method="post" action="<?= url('/admin/menu/' . $item['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cet élément de menu ?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger">Suppr.</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($section['items'])): ?>
              <tr><td colspan="5" class="text-muted text-center py-3">Aucun élément.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
          <form method="post" action="<?= url('/admin/menu') ?>" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="location" value="<?= $location ?>">
            <div class="col-4"><input type="text" name="label" class="form-control form-control-sm" placeholder="Libellé" required></div>
            <div class="col-4"><input type="text" name="url" class="form-control form-control-sm" placeholder="/page/... ou #ancre" required></div>
            <div class="col-2"><input type="number" name="sort_order" class="form-control form-control-sm" placeholder="Ordre" value="0"></div>
            <div class="col-2"><button class="btn btn-sm btn-primary w-100">Ajouter</button></div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
