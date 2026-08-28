<?php
use App\Core\View;
/** @var array $headerItems */
?>
<nav class="navbar navbar-landing navbar-expand sticky-top py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= url('/') ?>" class="d-flex align-items-center text-decoration-none text-dark">
      <i class="bi bi-calendar-event fs-4 me-2 text-primary"></i>
      <span class="fs-5 fw-semibold">EventPlanner</span>
    </a>
    <div class="d-none d-md-flex align-items-center gap-3">
      <?php foreach (($headerItems ?? []) as $item): ?>
        <?php $href = str_starts_with($item['url'], '/') ? url($item['url']) : $item['url']; ?>
        <a href="<?= View::e($href) ?>" class="text-decoration-none nav-link-custom"><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= url('/login') ?>" class="btn btn-outline-secondary">Connexion</a>
      <a href="<?= url('/register') ?>" class="btn btn-primary">Inscription</a>
    </div>
  </div>
</nav>
