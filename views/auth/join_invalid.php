<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Invitation invalide — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center text-center" style="min-height:100vh;">
  <div>
    <h1 class="h4 mb-2">Lien d'invitation invalide ou expiré</h1>
    <p class="text-muted">Ce lien a peut-être déjà été utilisé, révoqué, ou a expiré. Contactez la personne qui vous l'a envoyé.</p>
    <a href="<?= url('/login') ?>" class="btn btn-outline-secondary">Retour à la connexion</a>
  </div>
</div>
</body>
</html>
