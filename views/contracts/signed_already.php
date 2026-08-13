<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contrat déjà signé</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center text-center" style="min-height:100vh;">
  <div>
    <h1 class="h4">Ce contrat a déjà été signé</h1>
    <p class="text-muted">Signé par <?= View::e($contract['signer_name']) ?> le <?= View::date($contract['signed_at'], 'd/m/Y') ?>.</p>
  </div>
</div>
</body>
</html>
