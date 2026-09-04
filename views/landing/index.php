<?php
use App\Core\View;
/** @var array $plans */
/** @var array $headerItems */
/** @var array $footerItems */
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>EventPlanner — Le logiciel de gestion pour organisateurs d'événements</title>
<meta name="description" content="EventPlanner centralise clients, devis, contrats, factures, invités et prestataires — avec portail client à vos couleurs, alertes proactives et notifications temps réel. Pensé pour les organisateurs d'événements français.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<header class="hero py-5">
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="eyebrow mb-3"><i class="bi bi-stars"></i> Conçu pour les organisateurs d'événements</span>
        <h1 class="display-5 mb-3">Le logiciel qui gère vos événements — <span style="color:var(--ep-primary);">et qui parle à vos clients à votre place.</span></h1>
        <p class="lead text-secondary mb-4">Clients, devis, contrats, factures, invités et prestataires dans un seul espace. Vos clients suivent tout depuis un portail à votre logo et vos couleurs, sans jamais voir la marque EventPlanner.</p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg px-4">Essayer gratuitement</a>
          <a href="<?= url('/demo') ?>" class="btn btn-outline-secondary btn-lg px-4"><i class="bi bi-eye me-1"></i>Essayer la démo</a>
        </div>
        <p class="small text-secondary mb-0"><i class="bi bi-check-circle-fill text-success me-1"></i>Sans engagement &nbsp;·&nbsp; <i class="bi bi-check-circle-fill text-success me-1"></i>Offre gratuite disponible &nbsp;·&nbsp; <i class="bi bi-check-circle-fill text-success me-1"></i>Hébergé en France</p>
      </div>
      <div class="col-lg-6">
        <div class="hero-mock p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-semibold" id="mock-title"><i class="bi bi-speedometer2 me-2" style="color:var(--ep-primary);"></i>Tableau de bord</span>
            <span class="badge text-bg-light border">Aperçu</span>
          </div>

          <div class="mock-tabs d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="mock-tab active" data-mock="portail" data-title="Portail client à votre marque"><i class="bi bi-palette2 me-1"></i>Portail client</button>
            <button type="button" class="mock-tab" data-mock="caisse" data-title="Caisse sur place & ticket autonome"><i class="bi bi-cash-coin me-1"></i>Caisse</button>
            <button type="button" class="mock-tab" data-mock="ia" data-title="Devis généré par l'IA"><i class="bi bi-stars me-1"></i>Assistant IA</button>
            <button type="button" class="mock-tab" data-mock="alertes" data-title="Alertes proactives"><i class="bi bi-bell me-1"></i>Alertes</button>
            <button type="button" class="mock-tab" data-mock="rdv" data-title="Prise de rendez-vous en ligne"><i class="bi bi-calendar2-check me-1"></i>Rendez-vous</button>
          </div>

          <div class="mock-panel" data-mock-panel="portail">
            <div class="portal-preview-header d-flex align-items-center gap-2 mb-3 p-2 rounded">
              <div class="portal-logo">VA</div>
              <div>
                <div class="fw-semibold small">Votre Agence Événements</div>
                <div class="text-secondary" style="font-size:.72rem;">Portail de Camille &amp; Antoine</div>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
              <span><i class="bi bi-check-circle-fill text-success me-2"></i>Devis accepté</span><span class="text-secondary">2 400 €</span>
            </div>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
              <span><i class="bi bi-check-circle-fill text-success me-2"></i>Contrat signé électroniquement</span><span class="text-secondary">✓</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 small">
              <span><i class="bi bi-check-circle-fill text-success me-2"></i>Acompte payé en ligne</span><span class="text-secondary">720 €</span>
            </div>
            <p class="text-secondary small mt-2 mb-0">Un lien unique, à vos couleurs — vos clients ne voient jamais la marque EventPlanner.</p>
          </div>

          <div class="mock-panel d-none" data-mock-panel="caisse">
            <div class="row g-3 mb-3">
              <div class="col-6">
                <div class="border rounded p-3 shadow-soft">
                  <div class="text-secondary small">Ticket <?= '#' ?>V-0142</div>
                  <div class="fs-5 fw-bold">32,00 €</div>
                  <div class="text-secondary" style="font-size:.72rem;">2 coupes + 1 part de gâteau</div>
                </div>
              </div>
              <div class="col-6 text-center">
                <div class="border rounded p-2 d-inline-flex align-items-center justify-content-center shadow-soft" style="width:72px; height:72px;">
                  <i class="bi bi-qr-code" style="font-size:2.2rem; color:var(--ep-ink);"></i>
                </div>
                <div class="text-secondary" style="font-size:.7rem;">Scanné par le client</div>
              </div>
            </div>
            <p class="text-secondary small mb-0">Le client scanne son ticket et le télécharge sur son téléphone — sans imprimante, sans email à taper au comptoir.</p>
          </div>

          <div class="mock-panel d-none" data-mock-panel="ia">
            <div class="border rounded p-2 mb-2 small" style="background:var(--ep-surface);">
              <i class="bi bi-chat-left-text me-1 text-secondary"></i>« Mariage 120 invités, traiteur, DJ, déco florale »
            </div>
            <div class="d-flex justify-content-between border-bottom py-2 small">
              <span>Traiteur — menu 3 services</span><span class="text-secondary">120 × 95 €</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2 small">
              <span>Animation DJ soirée</span><span class="text-secondary">950 €</span>
            </div>
            <div class="d-flex justify-content-between py-2 small">
              <span>Décoration florale</span><span class="text-secondary">1 400 €</span>
            </div>
            <p class="text-secondary small mt-2 mb-0"><i class="bi bi-stars me-1" style="color:var(--ep-primary);"></i>Lignes de devis générées en quelques secondes, à ajuster librement.</p>
          </div>

          <div class="mock-panel d-none" data-mock-panel="alertes">
            <div class="alert py-2 px-3 mb-2 d-flex align-items-center gap-2" style="background:#fff7e6; border:1px solid #ffe9b8; color:#7a5600;">
              <i class="bi bi-exclamation-circle"></i>
              <span class="small fw-semibold mb-0">Devis Dupont sans réponse depuis 5 jours</span>
            </div>
            <div class="alert py-2 px-3 mb-2 d-flex align-items-center gap-2" style="background:#fdeaea; border:1px solid #f6c6c6; color:#8a2323;">
              <i class="bi bi-exclamation-triangle"></i>
              <span class="small fw-semibold mb-0">Facture FAC-2026-014 échue dans 3 jours</span>
            </div>
            <div class="alert py-2 px-3 mb-0 d-flex align-items-center gap-2" style="background:#eaf6ee; border:1px solid #bfe6cc; color:#1e6b3a;">
              <i class="bi bi-info-circle"></i>
              <span class="small fw-semibold mb-0">Acompte manquant à 15 jours de l'événement</span>
            </div>
          </div>

          <div class="mock-panel d-none" data-mock-panel="rdv">
            <div class="text-secondary small mb-2">Créneaux disponibles — jeudi 18</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach (['09:00', '09:30', '11:00', '14:30', '16:00'] as $slot): ?>
                <span class="slot-chip"><?= $slot ?></span>
              <?php endforeach; ?>
            </div>
            <p class="text-secondary small mb-0">Vos prospects réservent un premier contact seuls, sans échange d'emails.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="trust-strip py-3">
  <div class="container d-flex flex-wrap justify-content-center gap-4 gap-md-5">
    <span class="trust-item"><i class="bi bi-shield-check me-1"></i>Conforme RGPD</span>
    <span class="trust-item"><i class="bi bi-geo-alt me-1"></i>Données hébergées en France</span>
    <span class="trust-item"><i class="bi bi-palette me-1"></i>Portail client à votre marque</span>
    <span class="trust-item"><i class="bi bi-phone me-1"></i>Application installable (PWA)</span>
    <span class="trust-item"><i class="bi bi-lock me-1"></i>Paiement en ligne sécurisé (Stripe)</span>
  </div>
</div>

<section id="differenciants" class="py-5">
  <div class="container py-5">
    <div class="text-center mb-5">
      <span class="section-eyebrow">Pourquoi EventPlanner</span>
      <h2 class="fw-bold mt-2">Pas juste un CRM générique repeint pour l'événementiel</h2>
      <p class="text-secondary mx-auto" style="max-width:640px;">La plupart des outils du marché sont des tableurs ou des CRM généralistes adaptés tant bien que mal. EventPlanner est pensé dès le départ pour le métier d'organisateur.</p>
    </div>

    <div class="row g-4 mb-5">
      <?php
      $diffs = [
          ['bi-palette2', 'Portail client à votre marque', "Vos clients suivent devis, factures et messages sur un portail affichant votre logo et vos couleurs — pas celles d'EventPlanner. Un vrai espace à votre nom, pas un widget tiers visible.", 'Marque blanche'],
          ['bi-bell', 'Alertes qui anticipent, pas qui constatent', "Devis resté sans réponse, facture bientôt échue, acompte manquant à 15 jours de l'événement, double réservation d'un lieu : le système vous prévient avant que ça devienne un problème.", 'Proactif'],
          ['bi-link-45deg', 'Un seul lien pour tout faire signer et payer', "Accepter le devis, signer le contrat et régler l'acompte se font depuis le même lien envoyé au client — pas trois emails et trois liens différents à jongler.", 'Moins de friction'],
          ['bi-broadcast', 'Notifications en temps réel, pour tout le monde', "Vos équipes, vos clients et vous-même recevez des notifications instantanées (dans l'app et sur mobile via notifications push) à chaque étape clé — devis accepté, message reçu, paiement effectué.", 'Push + in-app'],
      ];
      ?>
      <?php foreach ($diffs as [$icon, $title, $desc, $badge]): ?>
        <div class="col-md-6">
          <div class="diff-card p-4 d-flex gap-3">
            <div class="diff-icon d-flex align-items-center justify-content-center"><i class="bi <?= $icon ?> fs-5"></i></div>
            <div>
              <span class="diff-badge mb-2 d-inline-block"><?= View::e($badge) ?></span>
              <h3 class="h6 fw-bold mb-1"><?= View::e($title) ?></h3>
              <p class="text-secondary small mb-0"><?= View::e($desc) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="compare-table">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th style="width:40%;">&nbsp;</th>
              <th class="text-center">Tableur / CRM généraliste</th>
              <th class="text-center" style="color:var(--ep-primary);">EventPlanner</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rows = [
                ['Portail client personnalisé à votre logo/couleurs', false, true],
                ['Alertes automatiques sur devis, factures et acomptes', false, true],
                ['Signature électronique + paiement d\'acompte en un lien', false, true],
                ['Notifications temps réel (in-app + push mobile)', false, true],
                ['Application installable sur mobile (PWA)', false, true],
                ['Conçu pour le métier événementiel (invités, prestataires, lieux)', false, true],
            ];
            ?>
            <?php foreach ($rows as [$label, $generic, $ep]): ?>
              <tr>
                <td><?= View::e($label) ?></td>
                <td class="text-center"><?= $generic ? '<i class="bi bi-check-lg compare-yes"></i>' : '<i class="bi bi-dash-lg compare-no"></i>' ?></td>
                <td class="text-center"><?= $ep ? '<i class="bi bi-check-lg compare-yes"></i>' : '<i class="bi bi-dash-lg compare-no"></i>' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-eyebrow">En pratique</span>
      <h2 class="fw-bold mt-2">De la demande client à l'encaissement, sans recopier</h2>
    </div>
    <div class="row g-4">
      <?php
      $steps = [
          ['1', 'Créez le devis', "Depuis la fiche client ou l'événement, générez un devis détaillé en quelques minutes."],
          ['2', 'Le client accepte, signe et paie', "Un seul lien envoyé au client : il accepte le devis, signe le contrat et règle son acompte en ligne."],
          ['3', 'Vous êtes prévenu, jamais surpris', "Facture générée, paiement confirmé, relances automatiques et alertes si quelque chose cloche."],
      ];
      ?>
      <?php foreach ($steps as [$num, $title, $desc]): ?>
        <div class="col-md-4">
          <div class="d-flex gap-3">
            <div class="step-num d-flex align-items-center justify-content-center"><?= $num ?></div>
            <div>
              <h3 class="h6 fw-bold mb-1"><?= View::e($title) ?></h3>
              <p class="text-secondary small mb-0"><?= View::e($desc) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="fonctionnalites" class="py-5">
  <div class="container py-5">
    <div class="text-center mb-5">
      <span class="section-eyebrow">Fonctionnalités</span>
      <h2 class="fw-bold mt-2">Tout votre métier, dans un seul panel</h2>
      <p class="text-secondary">Les outils essentiels pour organiser, facturer et suivre vos événements.</p>
    </div>
    <div class="row g-4">
      <?php
      $features = [
          ['bi-people', 'Clients', 'Centralisez vos contacts et l\'historique de chaque client.'],
          ['bi-calendar3', 'Événements & planning', 'Suivez chaque événement de la demande à la facturation.'],
          ['bi-file-earmark-text', 'Devis & contrats', 'Générez des devis et contrats signables en quelques clics.'],
          ['bi-receipt', 'Factures & paiements', 'Facturez, encaissez et relancez les impayés automatiquement.'],
          ['bi-ticket-perforated', 'Invités & billetterie', 'Gérez les invitations, RSVP et le contrôle d\'accès.'],
          ['bi-truck', 'Prestataires & lieux', 'Référencez vos prestataires, lieux et matériel disponibles.'],
          ['bi-folder2-open', 'Documents', 'Stockez et partagez les documents liés à chaque dossier.'],
          ['bi-graph-up', 'Rapports', 'Suivez votre activité et votre chiffre d\'affaires en temps réel.'],
      ];
      ?>
      <?php foreach ($features as [$icon, $title, $desc]): ?>
        <div class="col-sm-6 col-lg-3">
          <div class="feature-card p-4">
            <div class="feature-icon d-flex align-items-center justify-content-center mb-3">
              <i class="bi <?= $icon ?> fs-5"></i>
            </div>
            <h3 class="h6 fw-semibold"><?= View::e($title) ?></h3>
            <p class="text-secondary small mb-0"><?= View::e($desc) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="tarifs" class="py-5 bg-light">
  <div class="container py-5">
    <div class="text-center mb-5">
      <span class="section-eyebrow">Tarifs</span>
      <h2 class="fw-bold mt-2">Des tarifs simples, adaptés à votre activité</h2>
      <p class="text-secondary">Choisissez la formule qui correspond à la taille de votre équipe.</p>
    </div>

    <?php if (empty($plans)): ?>
      <p class="text-center text-secondary">Nos formules seront bientôt disponibles. <a href="<?= url('/register') ?>">Créez votre compte</a> pour être informé.</p>
    <?php else: ?>
      <div class="row g-4 justify-content-center">
        <?php foreach ($plans as $plan): ?>
          <?php $highlight = !empty($plan['is_default_signup']); ?>
          <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="plan-card h-100 p-4 d-flex flex-column <?= $highlight ? 'plan-highlight' : '' ?>">
              <?php if ($highlight): ?>
                <span class="badge bg-primary align-self-start mb-2">Le plus choisi</span>
              <?php endif; ?>
              <h3 class="h5 fw-semibold mb-1"><?= View::e($plan['name']) ?></h3>
              <?php if (!empty($plan['description'])): ?>
                <p class="text-secondary small"><?= View::e($plan['description']) ?></p>
              <?php endif; ?>
              <div class="mb-3">
                <span class="display-6 fw-bold"><?= View::money((float) $plan['monthly_price']) ?></span>
                <span class="text-secondary">/ mois</span>
              </div>
              <?php if (!empty($plan['max_members'])): ?>
                <p class="small text-secondary mb-4"><i class="bi bi-people me-1"></i>Jusqu'à <?= (int) $plan['max_members'] ?> utilisateurs</p>
              <?php endif; ?>
              <a href="<?= url('/register') ?>" class="btn <?= $highlight ? 'btn-primary' : 'btn-outline-primary' ?> mt-auto">Choisir ce plan</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-band py-5">
  <div class="container py-4 text-center">
    <h2 class="fw-bold mb-3">Prêt à simplifier la gestion de vos événements ?</h2>
    <p class="mb-4" style="color:rgba(255,255,255,.85);">Créez votre compte en quelques minutes, sans engagement — ou testez l'outil tout de suite, sans inscription.</p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="<?= url('/register') ?>" class="btn btn-light btn-lg px-4">Créer mon compte</a>
      <a href="<?= url('/demo') ?>" class="btn btn-outline-light btn-lg px-4"><i class="bi bi-eye me-1"></i>Essayer la démo</a>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

<script>
(function () {
  var tabs = document.querySelectorAll('.mock-tab');
  var panels = document.querySelectorAll('[data-mock-panel]');
  var title = document.getElementById('mock-title');
  var icons = {
    portail: 'bi-palette2', caisse: 'bi-cash-coin', ia: 'bi-stars',
    alertes: 'bi-bell', rdv: 'bi-calendar2-check'
  };

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var key = tab.dataset.mock;
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      panels.forEach(function (p) { p.classList.toggle('d-none', p.dataset.mockPanel !== key); });
      if (title) title.innerHTML = '<i class="bi ' + icons[key] + ' me-2" style="color:var(--ep-primary);"></i>' + tab.dataset.title;
    });
  });
})();
</script>
</body>
</html>
