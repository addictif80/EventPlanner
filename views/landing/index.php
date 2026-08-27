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
<title>EventPlanner — Gérez vos événements de A à Z</title>
<meta name="description" content="EventPlanner centralise clients, devis, contrats, factures, invités et prestataires pour les organisateurs d'événements.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require dirname(__DIR__) . '/partials/site_styles.php'; ?>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/site_header.php'; ?>

<header class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h1 class="display-6 fw-bold mb-3">Le logiciel de gestion pour organisateurs d'événements</h1>
        <p class="lead text-secondary mb-4">Clients, devis, contrats, factures, invités et prestataires : pilotez toute votre activité événementielle depuis un seul espace.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg px-4">Essayer gratuitement</a>
          <a href="<?= url('/login') ?>" class="btn btn-link btn-lg px-2 text-decoration-none">Déjà client ? Se connecter</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-mock p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-semibold"><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord</span>
            <span class="badge text-bg-light border">Aperçu</span>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="border rounded p-3">
                <div class="text-secondary small">Événements à venir</div>
                <div class="fs-4 fw-bold">12</div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3">
                <div class="text-secondary small">Devis en attente</div>
                <div class="fs-4 fw-bold">5</div>
              </div>
            </div>
          </div>
          <div class="border rounded p-3">
            <div class="text-secondary small mb-2">Prochains événements</div>
            <div class="d-flex justify-content-between border-bottom py-2 small">
              <span>Mariage — Dupont</span><span class="text-secondary">14/09</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2 small">
              <span>Séminaire — Acme Corp</span><span class="text-secondary">22/09</span>
            </div>
            <div class="d-flex justify-content-between py-2 small">
              <span>Anniversaire — Martin</span><span class="text-secondary">30/09</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<section id="fonctionnalites" class="py-5">
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Tout votre métier, dans un seul panel</h2>
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
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Des tarifs simples, adaptés à votre activité</h2>
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
  <div class="container py-3 text-center">
    <h2 class="fw-bold mb-3">Prêt à simplifier la gestion de vos événements ?</h2>
    <a href="<?= url('/register') ?>" class="btn btn-light btn-lg px-4">Créer mon compte</a>
  </div>
</section>

<?php require dirname(__DIR__) . '/partials/site_footer.php'; ?>

</body>
</html>
