<?php use App\Core\View; ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-company">Entreprise</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-smtp">Email (SMTP)</a></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-company">
    <div class="card"><div class="card-body">
      <form method="post" action="<?= url('/settings/company') ?>">
        <?= csrf_field() ?>
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
</div>
