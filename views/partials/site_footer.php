<?php
use App\Core\View;
/** @var array $footerItems */
?>
<footer class="footer-landing py-4">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <span class="text-secondary small"><i class="bi bi-calendar-event me-1"></i>EventPlanner &copy; <?= date('Y') ?></span>
    <div class="d-flex gap-3 small">
      <?php foreach (($footerItems ?? []) as $item): ?>
        <?php $href = str_starts_with($item['url'], '/') ? url($item['url']) : $item['url']; ?>
        <a href="<?= View::e($href) ?>" class="text-secondary text-decoration-none"><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</footer>
