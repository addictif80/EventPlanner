<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Jour-J — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id']) ?>" class="btn btn-outline-secondary btn-sm">← Retour</a>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Feuille de route</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/runsheet') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-3"><input type="time" name="time_slot" class="form-control form-control-sm"></div>
        <div class="col-5"><input type="text" name="title" class="form-control form-control-sm" placeholder="Étape" required></div>
        <div class="col-3"><input type="text" name="responsible" class="form-control form-control-sm" placeholder="Responsable"></div>
        <div class="col-1"><button class="btn btn-sm btn-primary w-100">+</button></div>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($runSheet as $item): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><strong><?= $item['time_slot'] ? substr($item['time_slot'], 0, 5) : '--:--' ?></strong> — <?= View::e($item['title']) ?> <?php if ($item['responsible']): ?><span class="text-muted small">(<?= View::e($item['responsible']) ?>)</span><?php endif; ?></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/runsheet/' . $item['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($runSheet)): ?><li class="list-group-item px-0 text-muted small">Aucune étape planifiée.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6">Contacts d'urgence</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/emergency-contacts') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-4"><input type="text" name="name" class="form-control form-control-sm" placeholder="Nom" required></div>
        <div class="col-3"><input type="text" name="role" class="form-control form-control-sm" placeholder="Rôle"></div>
        <div class="col-4"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Téléphone"></div>
        <div class="col-1"><button class="btn btn-sm btn-primary w-100">+</button></div>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($emergencyContacts as $c): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><?= View::e($c['name']) ?> <span class="text-muted small">(<?= View::e($c['role']) ?>)</span> — <a href="tel:<?= View::e($c['phone']) ?>"><?= View::e($c['phone']) ?></a></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/emergency-contacts/' . $c['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($emergencyContacts)): ?><li class="list-group-item px-0 text-muted small">Aucun contact.</li><?php endif; ?>
      </ul>
    </div></div>

    <div class="card"><div class="card-body">
      <h3 class="h6">Incidents</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/incidents') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?>
        <div class="col-5"><input type="text" name="title" class="form-control form-control-sm" placeholder="Titre" required></div>
        <div class="col-4">
          <select name="severity" class="form-select form-select-sm">
            <option value="low">Faible</option>
            <option value="medium" selected>Moyen</option>
            <option value="high">Élevé</option>
          </select>
        </div>
        <div class="col-3"><button class="btn btn-sm btn-danger w-100">Signaler</button></div>
        <div class="col-12"><textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Description"></textarea></div>
      </form>
      <ul class="list-group list-group-flush">
        <?php $sevColors = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger']; ?>
        <?php foreach ($incidents as $inc): ?>
        <li class="list-group-item px-0">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <span class="badge bg-<?= $sevColors[$inc['severity']] ?>"><?= View::e($inc['severity']) ?></span>
              <strong><?= View::e($inc['title']) ?></strong>
              <?php if ($inc['status'] === 'resolved'): ?><span class="badge bg-success">Résolu</span><?php endif; ?>
              <p class="mb-0 small text-muted"><?= View::e($inc['description']) ?></p>
            </div>
            <?php if ($inc['status'] === 'open'): ?>
            <form method="post" action="<?= url('/events/' . $event['id'] . '/incidents/' . $inc['id'] . '/status') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="resolved">
              <button class="btn btn-sm btn-outline-success">Résoudre</button>
            </form>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($incidents)): ?><li class="list-group-item px-0 text-muted small">Aucun incident signalé.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
</div>
