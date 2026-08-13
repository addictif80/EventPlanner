<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Check-in — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id'] . '/tickets') ?>" class="btn btn-outline-secondary btn-sm">← Billetterie</a>
</div>

<?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageStatus === 'success' ? 'success' : ($messageStatus === 'warning' ? 'warning' : 'danger') ?> fs-5"><?= View::e($message) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px;"><div class="card-body">
  <form method="post" action="<?= url('/events/' . $event['id'] . '/checkin') ?>">
    <?= csrf_field() ?>
    <label class="form-label">Code du billet</label>
    <input type="text" name="code" class="form-control form-control-lg text-uppercase mb-3" autofocus autocomplete="off" placeholder="Ex : A1B2C3D4E5">
    <button class="btn btn-primary w-100 btn-lg">Valider l'entrée</button>
  </form>
</div></div>
