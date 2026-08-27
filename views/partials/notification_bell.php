<?php
/**
 * Shared notification bell markup. Include with these variables set:
 * $notifFeedUrl, $notifMarkReadUrl (containing the literal "__ID__" placeholder),
 * $notifMarkAllUrl, $notifPushSubscribeUrl, $notifVapidKeyUrl.
 */
?>
<div id="notif-root" class="position-relative">
  <?= csrf_field() ?>
  <a href="#" id="notif-toggle" class="btn btn-sm btn-outline-secondary position-relative">
    <i class="bi bi-bell"></i>
    <span id="notif-badge" class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill d-none" style="font-size:.6rem;"></span>
  </a>
  <div id="notif-panel" class="dropdown-menu dropdown-menu-end p-0 shadow" style="width:340px; max-height:420px; overflow-y:auto; right:0; left:auto;">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
      <span class="fw-semibold small">Notifications</span>
      <div class="d-flex align-items-center gap-2">
        <button id="notif-enable-push" type="button" class="btn btn-sm btn-link p-0 d-none" title="Activer les notifications navigateur"><i class="bi bi-bell-fill"></i></button>
        <form id="notif-mark-all" class="mb-0"><button class="btn btn-sm btn-link p-0">Tout marquer lu</button></form>
      </div>
    </div>
    <div id="notif-list"></div>
  </div>
</div>
<script src="<?= url('/assets/js/notifications.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  window.EventPlannerNotifications.init({
    feedUrl: <?= json_encode($notifFeedUrl) ?>,
    markReadUrl: <?= json_encode($notifMarkReadUrl) ?>,
    markAllReadUrl: <?= json_encode($notifMarkAllUrl) ?>,
    pushSubscribeUrl: <?= json_encode($notifPushSubscribeUrl) ?>,
    vapidKeyUrl: <?= json_encode($notifVapidKeyUrl) ?>,
  });
});
</script>
