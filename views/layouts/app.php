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
<title><?= View::e($title ?? 'EventPlanner') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<div class="d-flex">
  <nav class="sidebar bg-dark text-white p-3 vh-100" style="width:230px; position:sticky; top:0;">
    <a href="<?= url('/') ?>" class="d-flex align-items-center mb-4 text-white text-decoration-none">
      <i class="bi bi-calendar-event fs-4 me-2"></i>
      <span class="fs-5 fw-semibold">EventPlanner</span>
    </a>
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/') ?>"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/clients') ?>"><i class="bi bi-people me-2"></i>Clients</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/events') ?>"><i class="bi bi-calendar3 me-2"></i>Événements</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/quotes') ?>"><i class="bi bi-file-earmark-text me-2"></i>Devis</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/invoices') ?>"><i class="bi bi-receipt me-2"></i>Factures</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/providers') ?>"><i class="bi bi-truck me-2"></i>Prestataires</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/venues') ?>"><i class="bi bi-geo-alt me-2"></i>Lieux</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/products') ?>"><i class="bi bi-box-seam me-2"></i>Catalogue</a></li>
      <li class="nav-item mt-3"><span class="text-uppercase small text-white-50 px-2">Administration</span></li>
      <?php if ($user && $user['role'] === 'admin'): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/users') ?>"><i class="bi bi-person-badge me-2"></i>Utilisateurs</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/settings') ?>"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
    </ul>
  </nav>
  <main class="flex-grow-1">
    <header class="d-flex justify-content-between align-items-center border-bottom px-4 py-2 bg-white">
      <h1 class="h5 mb-0"><?= View::e($title ?? '') ?></h1>
      <div class="d-flex align-items-center gap-3">
        <?php if ($user): ?>
        <span class="text-muted small"><?= View::e($user['name']) ?> · <?= View::e($user['role']) ?></span>
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
