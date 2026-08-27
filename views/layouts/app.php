<?php
use App\Core\Auth;
use App\Core\ModuleAccess;
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
      <?php if (ModuleAccess::has('contracts')): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/contracts') ?>"><i class="bi bi-file-earmark-text me-2"></i>Contrats</a></li>
      <?php endif; ?>
      <?php if (ModuleAccess::has('reports')): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/reports') ?>"><i class="bi bi-graph-up me-2"></i>Rapports</a></li>
      <?php endif; ?>
      <li class="nav-item mt-2"><span class="text-uppercase small text-white-50 px-2">Ressources</span></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/providers') ?>"><i class="bi bi-truck me-2"></i>Prestataires</a></li>
      <?php if (ModuleAccess::has('purchase_orders')): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/purchase-orders') ?>"><i class="bi bi-cart-check me-2"></i>Bons de commande</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/venues') ?>"><i class="bi bi-geo-alt me-2"></i>Lieux</a></li>
      <?php if (ModuleAccess::has('equipment')): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/equipment') ?>"><i class="bi bi-boxes me-2"></i>Matériel</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/products') ?>"><i class="bi bi-box-seam me-2"></i>Catalogue</a></li>
      <li class="nav-item mt-3"><span class="text-uppercase small text-white-50 px-2">Administration</span></li>
      <?php if ($user && $user['role'] === 'admin'): ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/users') ?>"><i class="bi bi-person-badge me-2"></i>Utilisateurs</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/settings') ?>"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/subscription') ?>"><i class="bi bi-credit-card me-2"></i>Abonnement</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/support') ?>"><i class="bi bi-life-preserver me-2"></i>Support</a></li>
      <?php if (Auth::isSuperAdmin()): ?>
      <li class="nav-item mt-3"><span class="text-uppercase small text-white-50 px-2">Plateforme</span></li>
      <li class="nav-item"><a class="nav-link text-white" href="<?= url('/admin') ?>"><i class="bi bi-shield-lock me-2"></i>Super admin</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <main class="flex-grow-1">
    <?php if (Auth::isImpersonating()): ?>
    <div class="alert alert-warning rounded-0 mb-0 d-flex justify-content-between align-items-center py-2 px-4">
      <span><i class="bi bi-incognito me-2"></i>Vous êtes connecté en tant que <strong><?= View::e($user['name'] ?? '') ?></strong> (organisation <?= View::e($user['organization_id'] ?? '') ?>).</span>
      <form method="post" action="<?= url('/admin/stop-impersonating') ?>" class="mb-0">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-dark">Revenir à mon compte admin</button>
      </form>
    </div>
    <?php endif; ?>
    <header class="d-flex justify-content-between align-items-center border-bottom px-4 py-2 bg-white">
      <h1 class="h5 mb-0"><?= View::e($title ?? '') ?></h1>
      <div class="d-flex align-items-center gap-3">
        <?php if ($user): ?>
        <span class="text-muted small"><?= View::e($user['name']) ?> · <?= View::e($user['role']) ?></span>
        <a href="<?= url('/account') ?>" class="btn btn-sm btn-outline-secondary">Mon compte</a>
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
