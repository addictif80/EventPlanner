<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirmer ma présence</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:420px;">
    <div class="card-body p-4">
      <h1 class="h5 mb-3">Vous êtes invité(e) !</h1>
      <p><strong><?= View::e($guest['event_title']) ?></strong><br><?= View::date($guest['event_date']) ?></p>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Serez-vous présent(e) ?</label>
          <select name="rsvp_status" class="form-select" required>
            <option value="confirmed" <?= $guest['rsvp_status'] === 'confirmed' ? 'selected' : '' ?>>Je serai présent(e)</option>
            <option value="declined" <?= $guest['rsvp_status'] === 'declined' ? 'selected' : '' ?>>Je ne pourrai pas venir</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Accompagnants (+1)</label>
          <input type="number" name="plus_ones" class="form-control" value="<?= (int)$guest['plus_ones'] ?>" min="0">
        </div>
        <div class="mb-3">
          <label class="form-label">Régime alimentaire particulier / allergies</label>
          <textarea name="dietary_notes" class="form-control" rows="2"><?= View::e($guest['dietary_notes']) ?></textarea>
        </div>
        <button class="btn btn-primary w-100">Confirmer ma réponse</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
