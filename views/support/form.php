<?php use App\Core\View; ?>
<div class="card" style="max-width:600px;">
  <div class="card-body">
    <form method="post" action="<?= url('/support') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Sujet</label><input type="text" name="subject" class="form-control" required></div>
      <div class="mb-3">
        <label class="form-label">Priorité</label>
        <select name="priority" class="form-select">
          <option value="low">Basse</option>
          <option value="normal" selected>Normale</option>
          <option value="high">Haute</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Message</label><textarea name="body" class="form-control" rows="5" required></textarea></div>
      <button class="btn btn-primary">Envoyer</button>
    </form>
  </div>
</div>
