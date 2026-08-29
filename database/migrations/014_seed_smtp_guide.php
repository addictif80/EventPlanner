<?php
// Data seed (see Migrator docblock): creates/updates the "Configurer votre
// serveur d'envoi d'email" guide as a site_pages row, linked from the
// in-app notification / email sent to an organization's admins the first
// time one of their client-facing emails (devis, facture, relance, portail)
// had to go out through EventPlanner's own SMTP server because the
// organization hasn't configured its own — see Mailer::warnAdminsOfSmtpFallback().
// Runs with a connected $pdo in scope. Idempotent: upserts by slug.

$content = <<<'HTML'
<div class="alert alert-warning d-flex gap-3 align-items-start mb-4">
  <i class="bi bi-info-circle-fill fs-4"></i>
  <div>
    <strong>Pourquoi ce message ?</strong> Votre organisation n'a pas encore renseigné de serveur SMTP dans <em>Paramètres&nbsp;&gt;&nbsp;Email</em>. En attendant, EventPlanner envoie vos devis, factures, relances et emails du portail client depuis son propre serveur pour que vos clients continuent bien à les recevoir — vos emails gardent votre nom d'organisation comme expéditeur affiché, mais l'adresse technique d'envoi n'est pas la vôtre. Configurer votre propre serveur en 5 minutes règle ça définitivement.
  </div>
</div>

<h2>Pourquoi configurer votre propre serveur d'envoi ?</h2>
<ul>
  <li><strong>Crédibilité</strong> : vos emails partent depuis une adresse @votredomaine.fr, pas depuis un serveur mutualisé partagé avec d'autres organisations.</li>
  <li><strong>Délivrabilité</strong> : certains clients (ou leur messagerie) filtrent plus sévèrement les emails provenant d'un expéditeur technique qu'ils ne connaissent pas.</li>
  <li><strong>Réponses directes</strong> : si un client répond à un devis ou une facture, la réponse arrive directement dans votre propre boîte mail.</li>
</ul>

<h2>Où configurer ?</h2>
<p>Rendez-vous dans <a href="/settings">Paramètres</a> puis l'onglet <strong>« Email (SMTP) »</strong>. Vous y renseignez 6 informations : l'hôte, le port, le type de chiffrement, un nom d'utilisateur, un mot de passe, et l'adresse d'expédition. Choisissez ci-dessous le mode qui correspond à votre situation.</p>

<hr class="my-4">

<h2>Option 1 — Votre propre nom de domaine (recommandé)</h2>
<p>Si vous avez un site ou un nom de domaine (ex. <code>votreagence.fr</code>), votre hébergeur (OVH, Ionos, Gandi, o2switch...) fournit presque toujours un serveur SMTP pour les adresses <code>@votreagence.fr</code>. C'est l'option la plus professionnelle : vos clients voient une adresse à votre nom.</p>
<p>Dans l'espace client de votre hébergeur, cherchez une rubrique « Messagerie » ou « Emails » et créez (ou retrouvez) une adresse dédiée, par exemple <code>contact@votreagence.fr</code>. Vous y trouverez les identifiants SMTP à recopier :</p>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle">
<thead><tr><th>Champ EventPlanner</th><th>Où le trouver</th></tr></thead>
<tbody>
<tr><td>Hôte SMTP</td><td>Souvent <code>ssl0.ovh.net</code>, <code>smtp.ionos.fr</code>, etc. — indiqué dans la doc « Configurer un client mail » de votre hébergeur.</td></tr>
<tr><td>Port</td><td><code>587</code> (STARTTLS) le plus souvent, parfois <code>465</code> (SSL/TLS).</td></tr>
<tr><td>Chiffrement</td><td>STARTTLS pour le port 587, SSL/TLS implicite pour le port 465.</td></tr>
<tr><td>Nom d'utilisateur</td><td>L'adresse email complète, ex. <code>contact@votreagence.fr</code>.</td></tr>
<tr><td>Mot de passe</td><td>Le mot de passe de cette boîte mail.</td></tr>
<tr><td>Email expéditeur</td><td>La même adresse, ex. <code>contact@votreagence.fr</code>.</td></tr>
</tbody>
</table>
</div>

<hr class="my-4">

<h2>Option 2 — Gmail / Google Workspace</h2>
<ol>
<li>Activez la <strong>validation en deux étapes</strong> sur votre compte Google si ce n'est pas déjà fait (<a href="https://myaccount.google.com/security" target="_blank" rel="noopener">myaccount.google.com/security</a>) — Google l'exige pour l'étape suivante.</li>
<li>Toujours dans <em>Sécurité</em>, ouvrez <strong>« Mots de passe des applications »</strong> et créez-en un nouveau (nommez-le par exemple « EventPlanner »). Google affiche un mot de passe de 16 caractères : copiez-le, il ne sera plus jamais réaffiché.</li>
<li>Dans EventPlanner, renseignez :</li>
</ol>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle">
<tbody>
<tr><td>Hôte SMTP</td><td><code>smtp.gmail.com</code></td></tr>
<tr><td>Port</td><td><code>587</code></td></tr>
<tr><td>Chiffrement</td><td>STARTTLS</td></tr>
<tr><td>Nom d'utilisateur</td><td>Votre adresse Gmail complète</td></tr>
<tr><td>Mot de passe</td><td>Le mot de passe d'application à 16 caractères (pas votre mot de passe Google habituel)</td></tr>
<tr><td>Email expéditeur</td><td>Votre adresse Gmail</td></tr>
</tbody>
</table>
</div>

<hr class="my-4">

<h2>Option 3 — Outlook / Hotmail / Microsoft 365</h2>
<ol>
<li>Si la validation en deux étapes est activée sur votre compte Microsoft, créez un « mot de passe d'application » depuis <a href="https://account.live.com/proofs/AppPassword" target="_blank" rel="noopener">account.live.com/proofs/AppPassword</a>. Sinon, le mot de passe habituel de votre compte peut suffire.</li>
<li>Dans EventPlanner, renseignez :</li>
</ol>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle">
<tbody>
<tr><td>Hôte SMTP</td><td><code>smtp.office365.com</code></td></tr>
<tr><td>Port</td><td><code>587</code></td></tr>
<tr><td>Chiffrement</td><td>STARTTLS</td></tr>
<tr><td>Nom d'utilisateur</td><td>Votre adresse Outlook/Hotmail complète</td></tr>
<tr><td>Mot de passe</td><td>Votre mot de passe (ou mot de passe d'application)</td></tr>
<tr><td>Email expéditeur</td><td>Votre adresse Outlook/Hotmail</td></tr>
</tbody>
</table>
</div>

<hr class="my-4">

<h2>Vérifier que ça fonctionne</h2>
<p>Une fois les informations enregistrées dans <em>Paramètres&nbsp;&gt;&nbsp;Email</em>, utilisez le bouton <strong>« Envoyer un email de test »</strong> en bas de l'onglet SMTP. Si vous recevez l'email de test, tous vos prochains devis, factures et emails clients partiront automatiquement depuis votre propre adresse — et vous ne recevrez plus cette alerte.</p>

<h2>Besoin d'aide ?</h2>
<p>Si un message d'erreur apparaît lors du test, vérifiez en priorité le port et le mot de passe (les mots de passe d'application se collent sans les espaces). Si le problème persiste, contactez le support depuis votre espace.</p>
HTML;

$stmt = $pdo->prepare(
    'INSERT INTO site_pages (slug, title, content, meta_description, is_published)
     VALUES (:slug, :title, :content, :meta_description, 1)
     ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), meta_description = VALUES(meta_description)'
);
$stmt->execute([
    'slug' => 'guide-configuration-smtp',
    'title' => "Configurer votre serveur d'envoi d'email",
    'content' => $content,
    'meta_description' => "Guide pas à pas pour configurer votre propre serveur SMTP (domaine personnalisé, Gmail, Outlook/Hotmail) dans EventPlanner.",
]);
