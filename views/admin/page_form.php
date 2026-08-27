<?php use App\Core\View; ?>
<div class="card" style="max-width:760px;">
  <div class="card-body">
    <form method="post" action="<?= $page ? url('/admin/pages/' . $page['id']) : url('/admin/pages') ?>">
      <?= csrf_field() ?>
      <div class="row g-3 mb-3">
        <div class="col-md-7">
          <label class="form-label">Titre</label>
          <input type="text" name="title" class="form-control" value="<?= View::e($page['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-5">
          <label class="form-label">Slug (URL : /page/...)</label>
          <input type="text" name="slug" class="form-control" placeholder="auto-généré si vide" value="<?= View::e($page['slug'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Description (SEO, optionnel)</label>
        <input type="text" name="meta_description" class="form-control" value="<?= View::e($page['meta_description'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Contenu (HTML autorisé)</label>
        <textarea name="content" class="form-control" rows="14"><?= View::e($page['content'] ?? '') ?></textarea>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" <?= ($page['is_published'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_published">Page publiée (accessible publiquement)</label>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a href="<?= url('/admin/pages') ?>" class="btn btn-link">Annuler</a>
    </form>
  </div>
</div>
