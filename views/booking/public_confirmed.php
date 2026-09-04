<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Rendez-vous confirmé</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center" style="max-width:520px;">
  <div class="card shadow-sm"><div class="card-body p-4">
    <i class="bi bi-calendar-check fs-1 text-success"></i>
    <h1 class="h5 mt-3">Rendez-vous confirmé</h1>
    <p class="text-muted">Le <strong><?= View::e(date('d/m/Y à H:i', strtotime($startsAt))) ?></strong> (<?= View::e($settings['location_type']) ?>).</p>
    <p class="text-muted small">Un email de confirmation vous a été envoyé, avec un lien pour annuler si besoin.</p>
  </div></div>
</div>
</body>
</html>
