<?php use App\Core\View; ?>
<div class="card"><div class="card-body">
  <h3 class="h6">Documents</h3>
  <form method="post" action="<?= url('/documents') ?>" enctype="multipart/form-data" class="d-flex gap-2 mb-3">
    <?= csrf_field() ?>
    <?php if (!empty($clientId)): ?><input type="hidden" name="client_id" value="<?= $clientId ?>"><?php endif; ?>
    <?php if (!empty($eventId)): ?><input type="hidden" name="event_id" value="<?= $eventId ?>"><?php endif; ?>
    <input type="text" name="category" class="form-control form-control-sm" placeholder="Catégorie (optionnel)" style="max-width:160px;">
    <input type="file" name="file" class="form-control form-control-sm" required>
    <button class="btn btn-sm btn-primary">Envoyer</button>
  </form>
  <ul class="list-group list-group-flush">
    <?php foreach ($documents as $d): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
      <a href="<?= url('/documents/' . $d['id'] . '/download') ?>"><i class="bi bi-file-earmark-arrow-down me-1"></i><?= View::e($d['original_name']) ?></a>
      <span>
        <?php if (!empty($d['category'])): ?><span class="badge bg-light text-dark me-2"><?= View::e($d['category']) ?></span><?php endif; ?>
        <form method="post" action="<?= url('/documents/' . $d['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Supprimer ce document ?');">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
        </form>
      </span>
    </li>
    <?php endforeach; ?>
    <?php if (empty($documents)): ?><li class="list-group-item px-0 text-muted small">Aucun document.</li><?php endif; ?>
  </ul>
</div></div>
