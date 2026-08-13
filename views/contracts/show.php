<?php use App\Core\View; ?>
<?php $statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'signed' => 'Signé', 'cancelled' => 'Annulé']; $statusColors = ['draft' => 'secondary', 'sent' => 'primary', 'signed' => 'success', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h2 class="h4 mb-1"><?= View::e($contract['title']) ?></h2>
    <span class="badge bg-<?= $statusColors[$contract['status']] ?>"><?= $statusLabels[$contract['status']] ?></span>
    <span class="text-muted ms-2">Client : <a href="<?= url('/clients/' . $contract['client_id']) ?>"><?= View::e($contract['company_name'] ?: trim($contract['first_name'] . ' ' . $contract['last_name'])) ?></a></span>
  </div>
  <div class="d-flex gap-2">
    <?php if ($contract['status'] !== 'signed'): ?>
    <form method="post" action="<?= url('/contracts/' . $contract['id'] . '/send') ?>" onsubmit="return confirm('Envoyer ce contrat au client pour signature électronique ?');">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-sm" <?= $smtpConfigured ? '' : 'disabled title="Configurez le SMTP dans Paramètres"' ?>><i class="bi bi-envelope"></i> Envoyer pour signature</button>
    </form>
    <?php endif; ?>
    <form method="post" action="<?= url('/contracts/' . $contract['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce contrat ?');">
      <?= csrf_field() ?>
      <button class="btn btn-outline-danger btn-sm">Supprimer</button>
    </form>
  </div>
</div>

<?php if ($contract['status'] === 'signed'): ?>
  <div class="alert alert-success">
    Signé électroniquement par <strong><?= View::e($contract['signer_name']) ?></strong> le <?= View::date($contract['signed_at'], 'd/m/Y à H:i') ?> (IP : <?= View::e($contract['signer_ip']) ?>)
  </div>
<?php endif; ?>

<div class="card"><div class="card-body">
  <div style="white-space: pre-wrap; font-family: Georgia, serif;"><?= View::e($contract['content']) ?></div>

  <?php if (!empty($contract['signature_data'])): ?>
    <hr>
    <p class="text-muted small mb-1">Signature de <?= View::e($contract['signer_name']) ?> :</p>
    <img src="<?= View::e($contract['signature_data']) ?>" alt="Signature" style="max-width:300px; border:1px solid #ddd;">
  <?php endif; ?>
</div></div>
