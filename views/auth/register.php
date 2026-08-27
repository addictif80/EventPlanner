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
<title>Créer un compte — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:440px;">
    <div class="card-body p-4">
      <h1 class="h4 text-center mb-1"><i class="bi bi-calendar-event"></i> EventPlanner</h1>
      <p class="text-center text-muted small mb-4">Créez votre espace organisateur d'événements</p>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('/register') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Nom de votre entreprise / agence</label>
          <input type="text" name="organization_name" class="form-control" value="<?= View::e(old('organization_name')) ?>" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Votre nom</label>
          <input type="text" name="name" class="form-control" value="<?= View::e(old('name')) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= View::e(old('email')) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Mot de passe</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
      </form>
      <p class="text-center small text-muted mt-3 mb-0">Déjà un compte ? <a href="<?= url('/login') ?>">Se connecter</a></p>
    </div>
  </div>
</div>
</body>
</html>
