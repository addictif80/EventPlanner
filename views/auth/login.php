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
<title>Connexion — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:380px;">
    <div class="card-body p-4">
      <h1 class="h4 text-center mb-4"><i class="bi bi-calendar-event"></i> EventPlanner</h1>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('/login') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= View::e(old('email')) ?>" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label d-flex justify-content-between">Mot de passe <a href="<?= url('/forgot-password') ?>" class="small">Mot de passe oublié ?</a></label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
      </form>
      <p class="text-center small text-muted mt-3 mb-0">Pas encore de compte ? <a href="<?= url('/register') ?>">Créer mon espace organisateur</a></p>
    </div>
  </div>
</div>
</body>
</html>
