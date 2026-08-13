<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Votre avis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="width:460px;">
    <div class="card-body p-4">
      <h1 class="h5 mb-3">Votre avis sur « <?= View::e($survey['event_title']) ?> »</h1>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Votre note</label>
          <select name="rating" class="form-select" required>
            <option value="5">★★★★★ Excellent</option>
            <option value="4">★★★★ Très bien</option>
            <option value="3">★★★ Correct</option>
            <option value="2">★★ Décevant</option>
            <option value="1">★ Très décevant</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Commentaires</label>
          <textarea name="comments" class="form-control" rows="4"></textarea>
        </div>
        <button class="btn btn-primary w-100">Envoyer mon avis</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
