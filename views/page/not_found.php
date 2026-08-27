<?php
/** @var array $headerItems */
/** @var array $footerItems */
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Page introuvable — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<div class="text-center py-5">
  <h1 class="display-4">404</h1>
  <p class="text-secondary">Page introuvable.</p>
  <a href="<?= url('/') ?>" class="btn btn-primary">Retour à l'accueil</a>
</div>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

</body>
</html>
