<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Trouver un organisateur d'événements — Annuaire EventPlanner</title>
<meta name="description" content="Annuaire des organisateurs d'événements utilisant EventPlanner : mariages, séminaires, anniversaires... avec avis clients vérifiés.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<header class="hero py-5">
  <div class="container py-4">
    <div class="text-center mb-4">
      <span class="eyebrow mb-3"><i class="bi bi-search-heart"></i> Annuaire</span>
      <h1 class="display-6 fw-bold mt-2">Trouver un organisateur d'événements</h1>
      <p class="text-secondary mx-auto" style="max-width:600px;">Des professionnels qui gèrent leur activité avec EventPlanner, avec des avis clients vérifiés.</p>
    </div>
    <form method="get" action="<?= url('/annuaire') ?>" class="mx-auto d-flex gap-2" style="max-width:520px;">
      <input type="text" name="q" class="form-control form-control-lg" placeholder="Ville, spécialité, nom..." value="<?= View::e($search) ?>">
      <button class="btn btn-primary btn-lg px-4">Chercher</button>
    </form>
  </div>
</header>

<section class="py-5">
  <div class="container py-3">
    <?php if (empty($organizations)): ?>
      <p class="text-center text-secondary py-5">Aucun organisateur ne correspond à votre recherche pour l'instant.</p>
    <?php endif; ?>
    <div class="row g-4">
      <?php foreach ($organizations as $org): ?>
      <div class="col-md-6 col-lg-4">
        <a href="<?= url('/annuaire/' . $org['directory_slug']) ?>" class="text-decoration-none">
          <div class="feature-card p-4 h-100">
            <h3 class="h5 fw-bold mb-1" style="color:var(--ep-ink);"><?= View::e($org['company_name']) ?></h3>
            <?php if ($org['city']): ?><p class="text-secondary small mb-2"><i class="bi bi-geo-alt me-1"></i><?= View::e($org['city']) ?></p><?php endif; ?>
            <?php if ($org['avg_rating']): ?>
              <p class="mb-2"><span class="fw-bold" style="color:var(--ep-primary);"><?= $org['avg_rating'] ?> / 5</span> <span class="text-secondary small">(<?= (int) $org['review_count'] ?> avis)</span></p>
            <?php else: ?>
              <p class="text-secondary small mb-2">Pas encore d'avis publié</p>
            <?php endif; ?>
            <?php if ($org['directory_specialties']): ?>
              <p class="small mb-0">
                <?php foreach (array_slice(array_map('trim', explode(',', $org['directory_specialties'])), 0, 3) as $spec): ?>
                  <span class="diff-badge me-1"><?= View::e($spec) ?></span>
                <?php endforeach; ?>
              </p>
            <?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

</body>
</html>
