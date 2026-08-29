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
<title>Réinitialiser mon mot de passe — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:380px;">
    <div class="card-body p-4">
      <h1 class="h4 text-center mb-4"><i class="bi bi-key"></i> Nouveau mot de passe</h1>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger small"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('/reset-password/' . $token) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Nouveau mot de passe</label>
          <input type="password" name="password" class="form-control" minlength="8" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirmer le mot de passe</label>
          <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
