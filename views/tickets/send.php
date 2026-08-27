<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Envoyer les billets — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id'] . '/tickets') ?>" class="btn btn-outline-secondary btn-sm">← Billetterie</a>
</div>

<?php if (empty($categories)): ?>
  <div class="alert alert-warning">Créez d'abord au moins une catégorie de billet pour pouvoir envoyer des billets.</div>
<?php else: ?>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <h3 class="h6">Catégorie à envoyer</h3>
      <form method="get" action="<?= url('/events/' . $event['id'] . '/tickets/send') ?>" class="mb-3" id="category-form">
        <select name="ticket_category_id" class="form-select form-select-sm" onchange="document.getElementById('category-form').submit()">
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $category && (int)$category['id'] === (int)$c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <ul class="list-group list-group-flush mb-3">
        <li class="list-group-item px-0 d-flex justify-content-between">
          <span>Invités avec email</span>
          <strong><?= count($guests) ?></strong>
        </li>
        <?php if ($missingEmailCount > 0): ?>
        <li class="list-group-item px-0 d-flex justify-content-between text-warning">
          <span>Invités sans email (ignorés)</span>
          <strong><?= $missingEmailCount ?></strong>
        </li>
        <?php endif; ?>
      </ul>

      <?php if (!empty($guests)): ?>
      <div class="small text-muted mb-3" style="max-height:180px; overflow-y:auto;">
        <?php foreach ($guests as $g): ?>
          <div><?= View::e(\App\Models\Guest::displayName($g)) ?> — <?= View::e($g['email']) ?></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="post" action="<?= url('/events/' . $event['id'] . '/tickets/send') ?>"
            onsubmit="return confirm('Envoyer un billet PDF par email à <?= count($guests) ?> invité(s) ? Cette action est immédiate.');">
        <?= csrf_field() ?>
        <input type="hidden" name="ticket_category_id" value="<?= $category['id'] ?? '' ?>">
        <button class="btn btn-primary w-100" <?= (empty($guests) || !$category) ? 'disabled' : '' ?>>
          <i class="bi bi-envelope-paper"></i> Envoyer <?= count($guests) ?> billet(s)
        </button>
      </form>
    </div></div>
  </div>

  <div class="col-md-7">
    <div class="card"><div class="card-body">
      <h3 class="h6">Aperçu de l'email</h3>
      <?php if ($previewHtml): ?>
        <p class="text-muted small">Aperçu généré à partir du premier invité éligible (<?= View::e(\App\Models\Guest::displayName($guests[0])) ?>). Chaque invité recevra son propre billet nominatif.</p>
        <iframe srcdoc="<?= htmlspecialchars($previewHtml, ENT_QUOTES) ?>" style="width:100%; height:560px; border:1px solid #e2e4e9;" title="Aperçu de l'email"></iframe>
      <?php else: ?>
        <p class="text-muted">Aucun aperçu disponible : sélectionnez une catégorie et assurez-vous qu'au moins un invité a une adresse email.</p>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<?php endif; ?>
