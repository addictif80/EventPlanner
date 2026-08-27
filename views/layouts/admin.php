<?php
use App\Core\Auth;
use App\Core\View;
$user = Auth::user();
$flashes = flashes();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title ?? 'Administration') ?> — EventPlanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<div class="d-flex">
  <nav class="sidebar bg-black text-white p-3 vh-100" style="width:230px; position:sticky; top:0;">
    <a href="<?= url('/admin') ?>" class="d-flex align-items-center mb-4 text-white text-decoration-none">
      <i class="bi bi-shield-lock fs-4 me-2"></i>
      <span class="fs-5 fw-semibold">Super admin</span>
    </a>
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin') ?>"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/organizations') ?>"><i class="bi bi-buildings me-2"></i>Organisations</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/users') ?>"><i class="bi bi-people me-2"></i>Utilisateurs</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/documents') ?>"><i class="bi bi-file-earmark-text me-2"></i>Documents</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/tickets') ?>"><i class="bi bi-life-preserver me-2"></i>Tickets support</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/offers') ?>"><i class="bi bi-tags me-2"></i>Offres</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/pages') ?>"><i class="bi bi-file-earmark-richtext me-2"></i>Pages & menus</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/blocklist') ?>"><i class="bi bi-slash-circle me-2"></i>Blocage IP / email</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/settings') ?>"><i class="bi bi-gear me-2"></i>Paramètres système</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin/activity-log') ?>"><i class="bi bi-journal-text me-2"></i>Journal d'activité</a></li>
      <li class="nav-item mt-3"><a class="nav-link text-white-50" href="<?= url('/') ?>"><i class="bi bi-arrow-left me-2"></i>Retour au panel</a></li>
    </ul>
  </nav>
  <main class="flex-grow-1">
    <header class="d-flex justify-content-between align-items-center border-bottom px-4 py-2 bg-white">
      <h1 class="h5 mb-0"><?= View::e($title ?? '') ?></h1>
      <div class="d-flex align-items-center gap-3">
        <?php if ($user): ?>
        <span class="text-muted small"><?= View::e($user['name']) ?> · super admin</span>
        <a href="<?= url('/logout') ?>" class="btn btn-sm btn-outline-secondary">Déconnexion</a>
        <?php endif; ?>
      </div>
    </header>
    <div class="p-4">
      <?php foreach (($flashes['success'] ?? []) as $m): ?>
        <div class="alert alert-success"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
