<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title><?= View::e($company['company_name']) ?> — Annuaire EventPlanner</title>
<?php if (!empty($company['directory_description'])): ?><meta name="description" content="<?= View::e(mb_substr($company['directory_description'], 0, 155)) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<article class="py-5">
  <div class="container py-4" style="max-width:760px;">
    <a href="<?= url('/annuaire') ?>" class="small text-secondary d-inline-block mb-3">← Annuaire</a>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <h1 class="fw-bold mb-1"><?= View::e($company['company_name']) ?></h1>
        <?php if ($company['city']): ?><p class="text-secondary mb-0"><i class="bi bi-geo-alt me-1"></i><?= View::e($company['city']) ?></p><?php endif; ?>
      </div>
      <?php if ($avgRating): ?>
        <div class="text-end">
          <div class="fs-3 fw-bold" style="color:var(--ep-primary);"><?= $avgRating ?> / 5</div>
          <div class="text-secondary small"><?= count($reviews) ?> avis vérifié<?= count($reviews) > 1 ? 's' : '' ?></div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($company['directory_specialties']): ?>
      <p class="mb-3">
        <?php foreach (array_map('trim', explode(',', $company['directory_specialties'])) as $spec): ?>
          <?php if ($spec !== ''): ?><span class="diff-badge me-1 mb-1 d-inline-block"><?= View::e($spec) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php if ($company['directory_description']): ?>
      <p class="text-secondary"><?= nl2br(View::e($company['directory_description'])) ?></p>
    <?php endif; ?>

    <?php if ($bookingUrl): ?>
      <a href="<?= url($bookingUrl) ?>" class="btn btn-primary btn-lg mt-2 mb-4"><i class="bi bi-calendar2-check me-1"></i>Prendre rendez-vous</a>
    <?php elseif (!empty($company['email'])): ?>
      <a href="mailto:<?= View::e($company['email']) ?>" class="btn btn-outline-primary btn-lg mt-2 mb-4"><i class="bi bi-envelope me-1"></i>Contacter</a>
    <?php endif; ?>

    <hr class="my-4">

    <h2 class="h5 fw-bold mb-3">Avis clients vérifiés</h2>
    <?php if (empty($reviews)): ?>
      <p class="text-secondary small">Aucun avis publié pour l'instant.</p>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="feature-card p-3 mb-3">
        <div class="mb-1" style="color:#f5a623;"><?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?></div>
        <?php if ($r['comments']): ?><p class="mb-1"><?= View::e($r['comments']) ?></p><?php endif; ?>
        <p class="text-secondary small mb-0">Avis vérifié — <?= View::date($r['submitted_at']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</article>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

</body>
</html>
