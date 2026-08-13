<?php use App\Core\View; ?>
<h2 class="h5 mb-3"><?= View::e($ticket['subject']) ?> <span class="badge bg-secondary"><?= View::e($ticket['status']) ?></span></h2>

<div class="card mb-3">
  <div class="list-group list-group-flush">
    <?php foreach ($messages as $m): ?>
      <div class="list-group-item <?= $m['is_staff_reply'] ? 'bg-light' : '' ?>">
        <div class="d-flex justify-content-between">
          <strong><?= $m['is_staff_reply'] ? 'Support EventPlanner' : View::e($m['user_name'] ?? 'Vous') ?></strong>
          <span class="text-muted small"><?= View::date($m['created_at'], 'd/m/Y H:i') ?></span>
        </div>
        <p class="mb-0 mt-1"><?= nl2br(View::e($m['body'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($ticket['status'] !== 'closed'): ?>
<div class="card">
  <div class="card-body">
    <form method="post" action="<?= url('/support/' . $ticket['id'] . '/reply') ?>">
      <?= csrf_field() ?>
      <textarea name="body" class="form-control mb-2" rows="4" placeholder="Votre message..." required></textarea>
      <button class="btn btn-primary">Envoyer</button>
    </form>
  </div>
</div>
<?php else: ?>
<p class="text-muted">Ce ticket est fermé.</p>
<?php endif; ?>
