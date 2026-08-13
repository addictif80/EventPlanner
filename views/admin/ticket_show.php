<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-0"><?= View::e($ticket['subject']) ?></h2>
    <span class="text-muted small">Organisation : <?= View::e($ticket['organization_name']) ?> · Priorité : <?= View::e($ticket['priority']) ?></span>
  </div>
  <form method="post" action="<?= url('/admin/tickets/' . $ticket['id'] . '/status') ?>" class="d-flex gap-2">
    <?= csrf_field() ?>
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Ouvert</option>
      <option value="pending" <?= $ticket['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
      <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Fermé</option>
    </select>
  </form>
</div>

<?php if (!$smtpConfigured): ?>
  <div class="alert alert-warning small">Le SMTP système n'est pas configuré : le client ne recevra pas de notification email de votre réponse. <a href="<?= url('/admin/settings') ?>">Configurer</a>.</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="list-group list-group-flush">
    <?php foreach ($messages as $m): ?>
      <div class="list-group-item <?= $m['is_staff_reply'] ? 'bg-light' : '' ?>">
        <div class="d-flex justify-content-between">
          <strong><?= $m['is_staff_reply'] ? 'Support' : View::e($m['user_name'] ?? 'Client') ?></strong>
          <span class="text-muted small"><?= View::date($m['created_at'], 'd/m/Y H:i') ?></span>
        </div>
        <p class="mb-0 mt-1"><?= nl2br(View::e($m['body'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= url('/admin/tickets/' . $ticket['id'] . '/reply') ?>">
      <?= csrf_field() ?>
      <textarea name="body" class="form-control mb-2" rows="4" placeholder="Votre réponse..." required></textarea>
      <button class="btn btn-primary">Répondre</button>
    </form>
  </div>
</div>
