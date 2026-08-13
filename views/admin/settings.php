<?php use App\Core\View; ?>
<div class="card" style="max-width:640px;">
  <div class="card-body">
    <p class="text-muted small">Ce serveur SMTP est utilisé uniquement pour les emails envoyés par la plateforme elle-même (réponses aux tickets de support, notifications de compte...). Chaque organisation configure séparément son propre SMTP dans Paramètres &gt; Email.</p>
    <form method="post" action="<?= url('/admin/settings/smtp') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nom de la plateforme</label><input type="text" name="platform_name" class="form-control" value="<?= View::e($settings['platform_name'] ?? 'EventPlanner') ?>"></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Hôte SMTP</label><input type="text" name="smtp_host" class="form-control" value="<?= View::e($settings['smtp_host'] ?? '') ?>" required></div>
        <div class="col-md-2"><label class="form-label">Port</label><input type="number" name="smtp_port" class="form-control" value="<?= View::e((string)($settings['smtp_port'] ?? 587)) ?>" required></div>
        <div class="col-md-4">
          <label class="form-label">Chiffrement</label>
          <select name="smtp_encryption" class="form-select">
            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
            <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL/TLS implicite (465)</option>
            <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Aucun</option>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Nom d'utilisateur</label><input type="text" name="smtp_username" class="form-control" value="<?= View::e($settings['smtp_username'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Mot de passe</label><input type="password" name="smtp_password" class="form-control" placeholder="<?= !empty($settings['smtp_password']) ? '•••••••• (laisser vide pour ne pas changer)' : '' ?>"></div>
        <div class="col-md-6"><label class="form-label">Email expéditeur</label><input type="email" name="smtp_from_email" class="form-control" value="<?= View::e($settings['smtp_from_email'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nom expéditeur</label><input type="text" name="smtp_from_name" class="form-control" value="<?= View::e($settings['smtp_from_name'] ?? '') ?>"></div>
      </div>
      <button class="btn btn-primary mt-4">Enregistrer</button>
    </form>

    <hr class="my-4">
    <h3 class="h6">Tester l'envoi</h3>
    <form method="post" action="<?= url('/admin/settings/smtp/test') ?>" class="d-flex gap-2">
      <?= csrf_field() ?>
      <input type="email" name="test_email" class="form-control" placeholder="Adresse de test">
      <button class="btn btn-outline-primary">Envoyer un test</button>
    </form>
  </div>
</div>

<div class="card mt-4">
  <div class="card-body">
    <h3 class="h6">Facturation des abonnements (Stripe)</h3>
    <p class="text-muted small">Compte Stripe de la <strong>plateforme</strong>, utilisé pour prélever les organisations sur leurs offres/modules payants. Distinct du compte Stripe que chaque organisation peut renseigner elle-même pour encaisser ses propres factures clients.</p>
    <form method="post" action="<?= url('/admin/settings/billing') ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Clé secrète Stripe</label><input type="password" name="stripe_secret_key" class="form-control" placeholder="<?= !empty($settings['stripe_secret_key']) ? '•••••••• (laisser vide pour ne pas changer)' : 'sk_live_...' ?>"></div>
        <div class="col-md-6"><label class="form-label">Clé publiable Stripe</label><input type="text" name="stripe_publishable_key" class="form-control" value="<?= View::e($settings['stripe_publishable_key'] ?? '') ?>" placeholder="pk_live_..."></div>
        <div class="col-md-12"><label class="form-label">Secret du webhook</label><input type="text" name="stripe_webhook_secret" class="form-control" value="<?= View::e($settings['stripe_webhook_secret'] ?? '') ?>" placeholder="whsec_..."></div>
      </div>
      <p class="text-muted small mt-2 mb-0">
        URL du webhook à renseigner côté Stripe (Développeurs &gt; Webhooks) :
        événements <code>checkout.session.completed</code>, <code>customer.subscription.updated</code>,
        <code>customer.subscription.deleted</code>, <code>invoice.payment_failed</code>.
      </p>
      <button class="btn btn-primary mt-3">Enregistrer</button>
    </form>
  </div>
</div>
