<?php use App\Core\View; use App\Models\Guest; ?>
<?php
$rsvpLabels = ['pending' => 'En attente', 'confirmed' => 'Confirmé', 'declined' => 'Décliné'];
$rsvpColors = ['pending' => 'secondary', 'confirmed' => 'success', 'declined' => 'danger'];
$totalGuests = count($guests);
$confirmedCount = count(array_filter($guests, fn($g) => $g['rsvp_status'] === 'confirmed'));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Invités — <?= View::e($event['title']) ?></h2>
  <a href="<?= url('/events/' . $event['id']) ?>" class="btn btn-outline-secondary btn-sm">← Retour à l'événement</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Invités</div><div class="fs-4 fw-bold"><?= $totalGuests ?></div></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Confirmés</div><div class="fs-4 fw-bold text-success"><?= $confirmedCount ?></div></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body text-center"><div class="text-muted small">Tables</div><div class="fs-4 fw-bold"><?= count($tables) ?></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h3 class="h6">Tables</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/tables') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nom de la table" required>
        <input type="number" name="capacity" class="form-control form-control-sm" placeholder="Places" style="max-width:90px;" value="8">
        <button class="btn btn-sm btn-primary">+</button>
      </form>
      <ul class="list-group list-group-flush">
        <?php foreach ($tables as $t): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><?= View::e($t['name']) ?> <span class="text-muted small">(<?= (int)$t['seated'] ?>/<?= $t['capacity'] ?>)</span></span>
          <form method="post" action="<?= url('/events/' . $event['id'] . '/tables/' . $t['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cette table ?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
          </form>
        </li>
        <?php endforeach; ?>
        <?php if (empty($tables)): ?><li class="list-group-item px-0 text-muted small">Aucune table.</li><?php endif; ?>
      </ul>
    </div></div>
  </div>

  <div class="col-md-8">
    <div class="card"><div class="card-body">
      <h3 class="h6">Ajouter un invité</h3>
      <form method="post" action="<?= url('/events/' . $event['id'] . '/guests') ?>" class="row g-2 mb-4">
        <?= csrf_field() ?>
        <div class="col-md-3"><input type="text" name="first_name" class="form-control form-control-sm" placeholder="Prénom"></div>
        <div class="col-md-3"><input type="text" name="last_name" class="form-control form-control-sm" placeholder="Nom"></div>
        <div class="col-md-3"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email"></div>
        <div class="col-md-2"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Téléphone"></div>
        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">+</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Nom</th><th>Contact</th><th>Table</th><th>+1</th><th>RSVP</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($guests as $g): $fid = 'guest-form-' . $g['id']; ?>
            <tr>
              <td>
                <input type="text" form="<?= $fid ?>" name="first_name" value="<?= View::e($g['first_name']) ?>" class="form-control form-control-sm mb-1" placeholder="Prénom">
                <input type="text" form="<?= $fid ?>" name="last_name" value="<?= View::e($g['last_name']) ?>" class="form-control form-control-sm" placeholder="Nom">
              </td>
              <td>
                <input type="email" form="<?= $fid ?>" name="email" value="<?= View::e($g['email']) ?>" class="form-control form-control-sm mb-1" placeholder="Email">
                <input type="text" form="<?= $fid ?>" name="phone" value="<?= View::e($g['phone']) ?>" class="form-control form-control-sm" placeholder="Téléphone">
              </td>
              <td>
                <select name="table_id" form="<?= $fid ?>" class="form-select form-select-sm">
                  <option value="">—</option>
                  <?php foreach ($tables as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $g['table_id'] == $t['id'] ? 'selected' : '' ?>><?= View::e($t['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" form="<?= $fid ?>" name="plus_ones" value="<?= (int)$g['plus_ones'] ?>" class="form-control form-control-sm" style="width:60px;"></td>
              <td>
                <select name="rsvp_status" form="<?= $fid ?>" class="form-select form-select-sm">
                  <?php foreach ($rsvpLabels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $g['rsvp_status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="text-nowrap">
                <button type="submit" form="<?= $fid ?>" class="btn btn-sm btn-outline-secondary" title="Enregistrer"><i class="bi bi-check"></i></button>
                <button type="submit" form="invite-<?= $fid ?>" class="btn btn-sm btn-outline-primary" title="Envoyer l'invitation par email" <?= empty($g['email']) ? 'disabled' : '' ?>><i class="bi bi-envelope"></i></button>
                <button type="submit" form="delete-<?= $fid ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cet invité ?');"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($guests)): ?><tr><td colspan="6" class="text-center text-muted py-3">Aucun invité pour le moment.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php foreach ($guests as $g): $fid = 'guest-form-' . $g['id']; ?>
        <form id="<?= $fid ?>" method="post" action="<?= url('/guests/' . $g['id']) ?>"><?= csrf_field() ?></form>
        <form id="invite-<?= $fid ?>" method="post" action="<?= url('/guests/' . $g['id'] . '/invite') ?>"><?= csrf_field() ?></form>
        <form id="delete-<?= $fid ?>" method="post" action="<?= url('/guests/' . $g['id'] . '/delete') ?>"><?= csrf_field() ?></form>
      <?php endforeach; ?>
    </div></div>
  </div>
</div>
