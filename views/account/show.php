<?php use App\Core\View; ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Mes informations</div>
      <div class="card-body">
        <form method="post" action="<?= url('/account/profile') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="name" class="form-control" value="<?= View::e($user['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= View::e($user['email']) ?>" required>
          </div>
          <p class="mb-3"><strong>Rôle :</strong> <?= View::e($user['role']) ?></p>
          <button class="btn btn-primary btn-sm">Enregistrer</button>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">Changer mon mot de passe</div>
      <div class="card-body">
        <form method="post" action="<?= url('/account/password') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Mot de passe actuel</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nouveau mot de passe</label>
            <input type="password" name="new_password" class="form-control" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmer le nouveau mot de passe</label>
            <input type="password" name="new_password_confirmation" class="form-control" minlength="8" required>
          </div>
          <button class="btn btn-primary btn-sm">Changer le mot de passe</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Mes données</div>
      <div class="card-body">
        <p class="text-muted small">Téléchargez une copie de vos données de compte (droit à la portabilité).</p>
        <a href="<?= url('/account/export.json') ?>" class="btn btn-outline-secondary btn-sm">Exporter mes données</a>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card border-danger">
      <div class="card-header text-danger">Zone dangereuse</div>
      <div class="card-body">
        <p class="mb-2">Supprimer définitivement votre compte.</p>
        <?php if ($isSoleMember): ?>
          <div class="alert alert-warning small">Vous êtes le seul membre de votre organisation : supprimer votre compte supprimera aussi <strong>toute l'organisation</strong> (clients, événements, devis, factures, etc.), de manière irréversible.</div>
        <?php endif; ?>
        <form method="post" action="<?= url('/account/delete') ?>" class="row g-2" onsubmit="return confirm('Cette action est irréversible. Confirmer la suppression de votre compte<?= $isSoleMember ? ' et de toute votre organisation' : '' ?> ?');">
          <?= csrf_field() ?>
          <div class="col-md-4">
            <input type="password" name="password" class="form-control" placeholder="Votre mot de passe" required>
          </div>
          <div class="col-md-3">
            <button class="btn btn-danger">Supprimer mon compte</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
