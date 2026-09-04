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
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title><?= View::e($title ?? 'EventPlanner') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/../partials/pwa_install_banner.php'; ?>

<div class="topbar d-lg-none">
  <button type="button" class="btn btn-icon text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Ouvrir le menu">
    <i class="bi bi-list fs-4"></i>
  </button>
  <a href="<?= url('/') ?>" class="topbar-brand text-white text-decoration-none">
    <i class="bi bi-calendar-event me-1"></i>EventPlanner
  </a>
  <?php if ($user): ?>
  <a href="<?= url('/account') ?>" class="btn btn-icon text-white" aria-label="Mon compte"><i class="bi bi-person-circle fs-4"></i></a>
  <?php else: ?>
  <span style="width:44px;"></span>
  <?php endif; ?>
</div>

<div class="app-shell d-flex">
  <nav class="offcanvas-lg offcanvas-start sidebar bg-dark text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header d-lg-none">
      <span class="offcanvas-title text-white fs-5 fw-semibold" id="sidebarOffcanvasLabel"><i class="bi bi-calendar-event me-2"></i>EventPlanner</span>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
      <a href="<?= url('/') ?>" class="d-none d-lg-flex align-items-center mb-4 text-white text-decoration-none">
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
        <?php if (ModuleAccess::has('pos')): ?>
        <li class="nav-item"><a class="nav-link text-white" href="<?= url('/pos') ?>"><i class="bi bi-cash-coin me-2"></i>Caisse</a></li>
        <?php endif; ?>
        <?php if (ModuleAccess::has('appointments')): ?>
        <li class="nav-item"><a class="nav-link text-white" href="<?= url('/appointments') ?>"><i class="bi bi-calendar2-check me-2"></i>Rendez-vous</a></li>
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
      <?php if ($user): ?>
      <div class="d-lg-none mt-auto pt-3 border-top border-secondary-subtle">
        <div class="text-white-50 small mb-2"><?= View::e($user['name']) ?> · <?= View::e($user['role']) ?></div>
        <a href="<?= url('/account') ?>" class="btn btn-outline-light btn-sm w-100 mb-2">Mon compte</a>
        <a href="<?= url('/logout') ?>" class="btn btn-outline-light btn-sm w-100">Déconnexion</a>
      </div>
      <?php endif; ?>
    </div>
  </nav>
  <main class="flex-grow-1 min-w-0">
    <?php if (Auth::isImpersonating()): ?>
    <div class="alert alert-warning rounded-0 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3 px-lg-4">
      <span class="small"><i class="bi bi-incognito me-2"></i>Connecté en tant que <strong><?= View::e($user['name'] ?? '') ?></strong> (organisation <?= View::e($user['organization_id'] ?? '') ?>).</span>
      <form method="post" action="<?= url('/admin/stop-impersonating') ?>" class="mb-0">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-dark">Revenir à mon compte admin</button>
      </form>
    </div>
    <?php endif; ?>
    <?php if (\App\Core\Demo::isActive()): ?>
    <div class="alert alert-warning rounded-0 mb-0 text-center py-2 small">
      <i class="bi bi-eye me-1"></i><strong>Mode démo</strong> — ces données sont partagées entre visiteurs et réinitialisées chaque nuit. Aucun email n'est réellement envoyé. <a href="<?= url('/register') ?>" class="alert-link">Créer mon propre compte</a>
    </div>
    <?php endif; ?>
    <header class="app-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom px-3 px-lg-4 py-2 bg-white">
      <h1 class="h5 mb-0 text-truncate"><?= View::e($title ?? '') ?></h1>
      <div class="d-flex align-items-center gap-2 gap-lg-3">
        <?php if ($user): ?>
        <button id="search-trigger" type="button" class="btn btn-sm btn-outline-secondary" title="Recherche (Ctrl/Cmd+K)"><i class="bi bi-search"></i><span class="d-none d-lg-inline ms-1">Rechercher</span></button>
        <?php
        $notifFeedUrl = url('/notifications.json');
        $notifMarkReadUrl = url('/notifications/__ID__/read');
        $notifMarkAllUrl = url('/notifications/read-all');
        $notifPushSubscribeUrl = url('/push/subscribe');
        $notifVapidKeyUrl = url('/push/vapid-public-key.json');
        include __DIR__ . '/../partials/notification_bell.php';
        ?>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle d-lg-none"></i>
            <span class="d-none d-lg-inline"><?= View::e($user['name']) ?> <span class="text-muted">· <?= View::e($user['role']) ?></span></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="dropdown-header d-lg-none"><?= View::e($user['name']) ?> · <?= View::e($user['role']) ?></li>
            <li><a class="dropdown-item" href="<?= url('/account') ?>">Mon compte</a></li>
            <li><a class="dropdown-item" href="<?= url('/logout') ?>">Déconnexion</a></li>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </header>
    <div class="p-3 p-lg-4">
      <?php foreach (($flashes['success'] ?? []) as $m): ?>
        <div class="alert alert-success"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flashes['error'] ?? []) as $m): ?>
        <div class="alert alert-danger"><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flashes['info'] ?? []) as $m): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i><?= View::e($m) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </main>
</div>

<div id="search-modal" class="d-none" data-search-url="<?= url('/search.json') ?>" style="position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1080;">
  <div class="mx-auto mt-5 px-3" style="max-width:560px;">
    <div class="card shadow">
      <div class="card-body p-0">
        <input id="search-input" type="text" class="form-control form-control-lg border-0" placeholder="Rechercher un client, un événement, un devis, une facture...">
        <div id="search-results" class="list-group list-group-flush" style="max-height:400px; overflow-y:auto;"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('/assets/js/search.js') ?>"></script>
</body>
</html>
