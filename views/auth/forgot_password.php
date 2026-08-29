<?php
use App\Core\View;
$flashes = flashes();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Mot de passe oublié — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:380px;">
    <div class="card-body p-4">
      <h1 class="h4 text-center mb-4"><i class="bi bi-key"></i> Mot de passe oublié</h1>
      <?php foreach (($flashes['success'] ?? []) as $m): ?>
        <div class="alert alert-success small"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger small"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <p class="text-muted small">Indiquez votre adresse email : si un compte existe, vous recevrez un lien pour réinitialiser votre mot de passe.</p>
      <form method="post" action="<?= url('/forgot-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
      </form>
      <p class="text-center small text-muted mt-3 mb-0"><a href="<?= url('/login') ?>">Retour à la connexion</a></p>
    </div>
  </div>
</div>
</body>
</html>
