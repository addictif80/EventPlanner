<?php
use App\Core\View;
/** @var array $page */
/** @var array $headerItems */
/** @var array $footerItems */
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($page['title']) ?> — EventPlanner</title>
<?php if (!empty($page['meta_description'])): ?>
<meta name="description" content="<?= View::e($page['meta_description']) ?>">
<?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<article class="py-5">
  <div class="container py-4" style="max-width: 800px;">
    <h1 class="fw-bold mb-4"><?= View::e($page['title']) ?></h1>
    <div class="page-content">
      <?= $page['content'] ?>
    </div>
  </div>
</article>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

</body>
</html>
