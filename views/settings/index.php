<?php use App\Core\Auth; use App\Core\View; ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-company">Entreprise</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-smtp">Email (SMTP)</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-templates">Modèles d'emails</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-integrations">Intégrations</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-organization">Organisation</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('/settings/activity-log') ?>">Journal d'activité</a></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-company">
    <div class="card"><div class="card-body">
      <form method="post" action="<?= url('/settings/company') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Logo de l'organisation</label>
          <div class="d-flex align-items-center gap-3">
            <?php if (!empty($company['logo_path'])): ?>
              <img src="<?= url('/org-logo/' . Auth::organizationId()) ?>" alt="Logo" style="height:48px; max-width:160px; object-fit:contain;" class="border rounded p-1 bg-white">
            <?php else: ?>
              <span class="text-muted small">Aucun logo pour l'instant.</span>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="form-control form-control-sm" style="max-width:280px;">
            <?php if (!empty($company['logo_path'])): ?>
              <button type="submit" name="remove_logo" value="1" class="btn btn-sm btn-outline-danger">Supprimer</button>
            <?php endif; ?>
          </div>
          <p class="form-text">Utilisé dans les emails envoyés à vos clients et sur vos devis/factures/contrats. PNG, JPG, WebP ou SVG, 2 Mo max.</p>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nom de l'entreprise</label><input type="text" name="company_name" class="form-control" value="<?= View::e($company['company_name'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Forme juridique</label><input type="text" name="legal_form" class="form-control" value="<?= View::e($company['legal_form'] ?? '') ?>"></div>
          <div class="col-12"><label class="form-label">Adresse</label><input type="text" name="address" class="form-control" value="<?= View::e($company['address'] ?? '') ?>"></div>
          <div class="col-md-3"><label class="form-label">Code postal</label><input type="text" name="postal_code" class="form-control" value="<?= View::e($company['postal_code'] ?? '') ?>"></div>
          <div class="col-md-5"><label class="form-label">Ville</label><input type="text" name="city" class="form-control" value="<?= View::e($company['city'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Pays</label><input type="text" name="country" class="form-control" value="<?= View::e($company['country'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Téléphone</label><input type="text" name="phone" class="form-control" value="<?= View::e($company['phone'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= View::e($company['email'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Site web</label><input type="text" name="website" class="form-control" value="<?= View::e($company['website'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">SIRET</label><input type="text" name="siret" class="form-control" value="<?= View::e($company['siret'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">N° TVA intracommunautaire</label><input type="text" name="vat_number" class="form-control" value="<?= View::e($company['vat_number'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Devise</label><input type="text" name="currency" class="form-control" value="<?= View::e($company['currency'] ?? 'EUR') ?>"></div>
          <div class="col-md-4"><label class="form-label">TVA par défaut (%)</label><input type="text" name="default_tax_rate" class="form-control" value="<?= View::e((string)($company['default_tax_rate'] ?? 20)) ?>"></div>
          <div class="col-md-4"><label class="form-label">Préfixe des devis</label><input type="text" name="quote_prefix" class="form-control" value="<?= View::e($company['quote_prefix'] ?? 'DEV-') ?>"></div>
          <div class="col-md-4"><label class="form-label">Préfixe des factures</label><input type="text" name="invoice_prefix" class="form-control" value="<?= View::e($company['invoice_prefix'] ?? 'FAC-') ?>"></div>
          <div class="col-12"><label class="form-label">Pied de page des factures (mentions légales, IBAN...)</label><textarea name="invoice_footer" class="form-control" rows="3"><?= View::e($company['invoice_footer'] ?? '') ?></textarea></div>
        </div>
        <button class="btn btn-primary mt-4">Enregistrer</button>
      </form>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-smtp">
    <div class="card"><div class="card-body">
      <p class="text-muted">Configurez ici le serveur SMTP utilisé pour l'envoi des devis, factures et emails de test. Compatible avec n'importe quel fournisseur (OVH, Gmail, votre propre serveur mail sur la VM, etc.).</p>
      <form method="post" action="<?= url('/settings/smtp') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Hôte SMTP</label><input type="text" name="host" class="form-control" placeholder="smtp.example.com" value="<?= View::e($smtp['host'] ?? '') ?>" required></div>
          <div class="col-md-2"><label class="form-label">Port</label><input type="number" name="port" class="form-control" value="<?= View::e((string)($smtp['port'] ?? 587)) ?>" required></div>
          <div class="col-md-4">
            <label class="form-label">Chiffrement</label>
            <select name="encryption" class="form-select">
              <option value="tls" <?= ($smtp['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
              <option value="ssl" <?= ($smtp['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL/TLS implicite (465)</option>
              <option value="none" <?= ($smtp['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Aucun (non recommandé)</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Nom d'utilisateur</label><input type="text" name="username" class="form-control" value="<?= View::e($smtp['username'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" placeholder="<?= !empty($smtp['password']) ? '•••••••• (laisser vide pour ne pas changer)' : '' ?>"></div>
          <div class="col-md-6"><label class="form-label">Email expéditeur (From)</label><input type="email" name="from_email" class="form-control" value="<?= View::e($smtp['from_email'] ?? '') ?>" required></div>
          <div class="col-md-6"><label class="form-label">Nom expéditeur</label><input type="text" name="from_name" class="form-control" value="<?= View::e($smtp['from_name'] ?? '') ?>"></div>
        </div>
        <button class="btn btn-primary mt-4">Enregistrer la configuration SMTP</button>
      </form>

      <hr class="my-4">

      <h3 class="h6">Tester l'envoi</h3>
      <form method="post" action="<?= url('/settings/smtp/test') ?>" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="email" name="test_email" class="form-control" placeholder="Adresse de test (par défaut : votre email)" style="max-width:320px;">
        <button class="btn btn-outline-primary">Envoyer un email de test</button>
      </form>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-templates">
    <div class="card"><div class="card-body">
      <p class="text-muted">Personnalisez le sujet et l'introduction des emails automatiques. Utilisez <code>{number}</code> dans le sujet pour insérer le numéro de devis/facture.</p>
      <form method="post" action="<?= url('/settings/email-templates') ?>">
        <?= csrf_field() ?>
        <?php $templateLabels = ['quote' => 'Envoi de devis', 'invoice' => 'Envoi de facture', 'reminder' => 'Relance impayé']; ?>
        <?php foreach ($templates as $t): ?>
        <div class="mb-4 pb-3 border-bottom">
          <h3 class="h6"><?= View::e($templateLabels[$t['template_key']] ?? $t['template_key']) ?></h3>
          <div class="mb-2">
            <label class="form-label small">Sujet</label>
            <input type="text" name="subject[<?= $t['template_key'] ?>]" class="form-control" value="<?= View::e($t['subject']) ?>">
          </div>
          <div>
            <label class="form-label small">Introduction</label>
            <textarea name="intro[<?= $t['template_key'] ?>]" class="form-control" rows="2"><?= View::e($t['intro']) ?></textarea>
          </div>
        </div>
        <?php endforeach; ?>
        <button class="btn btn-primary">Enregistrer les modèles</button>
      </form>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-integrations">
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6">Paiement en ligne (Stripe)</h3>
      <p class="text-muted small">Renseignez vos propres clés API Stripe (Dashboard Stripe → Développeurs → Clés API) pour activer la génération de liens de paiement sur les factures.</p>
      <form method="post" action="<?= url('/settings/company') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="company_name" value="<?= View::e($company['company_name'] ?? '') ?>">
        <input type="hidden" name="default_tax_rate" value="<?= View::e((string)($company['default_tax_rate'] ?? 20)) ?>">
        <input type="hidden" name="currency" value="<?= View::e($company['currency'] ?? 'EUR') ?>">
        <input type="hidden" name="quote_prefix" value="<?= View::e($company['quote_prefix'] ?? 'DEV-') ?>">
        <input type="hidden" name="invoice_prefix" value="<?= View::e($company['invoice_prefix'] ?? 'FAC-') ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Clé secrète Stripe</label><input type="password" name="stripe_secret_key" class="form-control" value="<?= View::e($company['stripe_secret_key'] ?? '') ?>" placeholder="sk_live_..."></div>
          <div class="col-md-6"><label class="form-label">Clé publiable Stripe</label><input type="text" name="stripe_publishable_key" class="form-control" value="<?= View::e($company['stripe_publishable_key'] ?? '') ?>" placeholder="pk_live_..."></div>
        </div>
        <button class="btn btn-primary mt-3">Enregistrer</button>
      </form>
    </div></div>

    <div class="card"><div class="card-body">
      <h3 class="h6">Flux calendrier (Google Calendar / Outlook)</h3>
      <p class="text-muted small">Abonnez votre agenda (Google Calendar, Outlook, Apple Calendar...) à ce flux pour voir automatiquement tous vos événements.</p>
      <?php if (!empty($company['ics_feed_token'])): ?>
        <div class="input-group mb-2">
          <input type="text" readonly class="form-control" value="<?= View::e((($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/calendar/' . $company['ics_feed_token'] . '.ics'))) ?>">
        </div>
      <?php endif; ?>
      <form method="post" action="<?= url('/settings/ics-token') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-dark btn-sm"><?= !empty($company['ics_feed_token']) ? 'Régénérer le lien' : 'Générer le lien du flux calendrier' ?></button>
      </form>
    </div></div>

    <div class="card mt-3"><div class="card-body">
      <h3 class="h6">Export emailing</h3>
      <p class="text-muted small">Exportez la liste de vos clients (email, nom) au format CSV pour l'importer dans Mailchimp ou tout autre outil d'emailing.</p>
      <a href="<?= url('/export/clients.csv') ?>" class="btn btn-outline-dark btn-sm">Exporter les emails clients (CSV)</a>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-organization">
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6">Mon compte</h3>
      <p class="text-muted small mb-2">Consultez et gérez vos propres données personnelles (export, suppression de compte).</p>
      <a href="<?= url('/account') ?>" class="btn btn-outline-secondary btn-sm">Aller à « Mon compte »</a>
    </div></div>

    <div class="card border-danger"><div class="card-body">
      <h3 class="h6 text-danger">Zone dangereuse</h3>
      <p class="mb-2">Supprimer définitivement cette organisation et toutes ses données (clients, événements, devis, factures, utilisateurs...). Cette action est irréversible.</p>
      <form method="post" action="<?= url('/settings/organization/delete') ?>" class="row g-2" onsubmit="return confirm('Cette action supprimera irréversiblement toute l\'organisation et l\'ensemble de ses données. Continuer ?');">
        <?= csrf_field() ?>
        <div class="col-md-4">
          <label class="form-label small">Votre mot de passe</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-5">
          <label class="form-label small">Tapez le nom de l'organisation pour confirmer</label>
          <input type="text" name="confirm_name" class="form-control" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button class="btn btn-danger w-100">Supprimer l'organisation</button>
        </div>
      </form>
    </div></div>
  </div>
</div>
