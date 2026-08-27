<?php

/**
 * One-off CLI script: creates (or updates, if already present) the three
 * legal pages — Mentions légales, CGU, CGV — as site_pages rows, and adds
 * matching links to the footer menu (site_menu_items) if not already there.
 * Safe to re-run: upserts by slug / by (location, url).
 *
 * Usage: php bin/seed_legal_pages.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$pdo = Database::connection();

$mentionsLegales = <<<'HTML'
<h2>Édition du site</h2>
<p>Le site et le service EventPlanner (ci-après « le Service ») sont édités par :</p>
<p>
ABhD, entreprise individuelle (micro-entreprise)<br>
Représentant légal : Adrien Bochard<br>
Adresse du siège : 2 rue Ricarde de Buffet, 46100 Capdenac, France<br>
SIREN : 987 664 182<br>
TVA non applicable, article 293 B du Code général des impôts (franchise en base de TVA)
</p>
<p>Directeur de la publication : Adrien Bochard.</p>
<p>Contact : via le formulaire de support accessible depuis l'espace client (rubrique « Support »), ou par courrier postal à l'adresse ci-dessus.</p>

<h2>Hébergement</h2>
<p>
Le Service est hébergé par :<br>
ABhD (abhd.fr)<br>
2 rue Ricarde de Buffet, 46100 Capdenac, France
</p>

<h2>Propriété intellectuelle</h2>
<p>L'ensemble des éléments composant le Service (logiciel, textes, graphismes, logos, structure de navigation) est la propriété exclusive d'ABhD ou de ses partenaires, sauf mention contraire, et est protégé par le Code de la propriété intellectuelle. Toute reproduction, représentation ou exploitation, totale ou partielle, sans autorisation préalable est interdite.</p>

<h2>Données personnelles</h2>
<p>Le traitement des données personnelles réalisé dans le cadre du Service est décrit dans les Conditions Générales d'Utilisation. Un utilisateur du Service peut exercer ses droits sur ses propres données de compte (export, suppression) directement depuis la rubrique « Mon compte » de son espace ; le client d'une organisation utilisatrice peut faire de même sur ses propres données depuis son portail client (export, demande de suppression). Pour toute autre question, vous pouvez contacter ABhD via le formulaire de support de l'espace client. Vous disposez également du droit d'introduire une réclamation auprès de la Commission Nationale de l'Informatique et des Libertés (CNIL) — <a href="https://www.cnil.fr">www.cnil.fr</a>.</p>

<h2>Cookies</h2>
<p>Le Service n'utilise que des cookies strictement nécessaires à son fonctionnement (authentification, sécurité de session). Aucun cookie de mesure d'audience ou publicitaire n'est déposé à ce jour.</p>

<h2>Limitation de responsabilité</h2>
<p>ABhD s'efforce d'assurer l'exactitude des informations diffusées sur le site, sans garantir qu'elles soient exemptes d'erreurs. ABhD ne saurait être tenue responsable des dommages directs ou indirects résultant de l'accès ou de l'usage du site, y compris l'inaccessibilité, les pertes de données ou la présence de virus.</p>

<h2>Droit applicable</h2>
<p>Les présentes mentions légales sont soumises au droit français.</p>
HTML;

$cgu = <<<'HTML'
<p><em>Dernière mise à jour : à compléter à la publication.</em></p>

<h2>1. Objet</h2>
<p>Les présentes Conditions Générales d'Utilisation (« CGU ») ont pour objet de définir les modalités et conditions dans lesquelles ABhD (« l'Éditeur ») met à disposition son service en ligne EventPlanner (« le Service »), un logiciel SaaS de gestion d'activité pour organisateurs d'événements (clients, événements, devis, contrats, factures, invités, prestataires), ainsi que les droits et obligations des utilisateurs (« l'Utilisateur », « le Client »).</p>
<p>Elles s'appliquent à tout utilisateur du Service, professionnel ou particulier. Les conditions commerciales et tarifaires relatives aux offres payantes sont précisées dans les Conditions Générales de Vente (CGV), qui complètent les présentes CGU.</p>

<h2>2. Acceptation</h2>
<p>La création d'un compte et/ou l'utilisation du Service emporte l'acceptation pleine et entière des présentes CGU. Si l'Utilisateur n'accepte pas ces conditions, il doit renoncer à créer un compte et à utiliser le Service.</p>

<h2>3. Accès au service et compte utilisateur</h2>
<p>L'accès au Service nécessite la création d'un compte associé à une organisation. L'Utilisateur s'engage à fournir des informations exactes lors de son inscription et à les maintenir à jour. Il est responsable de la confidentialité de ses identifiants de connexion et de toute activité réalisée depuis son compte.</p>
<p>Le titulaire du compte principal d'une organisation peut inviter d'autres membres et leur attribuer des rôles et niveaux d'accès au sein de l'espace de son organisation. Il est seul responsable des accès qu'il accorde.</p>

<h2>4. Description du service</h2>
<p>EventPlanner permet notamment de gérer des clients, événements, devis, contrats, factures et paiements, invités et prestataires liés à l'activité de l'Utilisateur. Certaines fonctionnalités peuvent être réservées aux offres payantes décrites sur la page tarifs du site et dans les CGV.</p>
<p>L'Éditeur se réserve le droit de faire évoluer le Service (ajout, modification ou suppression de fonctionnalités) afin d'en améliorer la qualité, sans que cela ne constitue une modification substantielle du Service justifiant une indemnisation.</p>

<h2>5. Obligations de l'utilisateur</h2>
<p>L'Utilisateur s'engage à utiliser le Service conformément à sa destination et à la réglementation en vigueur, et notamment à ne pas :</p>
<ul>
<li>utiliser le Service à des fins illicites, frauduleuses ou portant atteinte aux droits de tiers ;</li>
<li>tenter d'accéder à des données ou comptes ne lui appartenant pas, ou de contourner les mesures de sécurité du Service ;</li>
<li>importer, saisir ou diffuser via le Service des contenus illicites, diffamatoires, ou portant atteinte à la vie privée de tiers ;</li>
<li>procéder à une utilisation du Service susceptible de nuire à son bon fonctionnement (surcharge, extraction massive de données, ingénierie inverse).</li>
</ul>
<p>L'Utilisateur est seul responsable des données qu'il saisit dans le Service, notamment les données relatives à ses propres clients et aux invités de ses événements, ainsi que de la licéité de leur collecte et de leur traitement.</p>

<h2>6. Données personnelles</h2>
<p>Deux catégories de données personnelles sont traitées dans le cadre du Service :</p>
<ul>
<li><strong>Les données de compte</strong> (nom, email, mot de passe, organisation) des Utilisateurs eux-mêmes : pour ces données, ABhD agit en qualité de responsable de traitement, aux fins de gestion des comptes, de la relation contractuelle et de la facturation.</li>
<li><strong>Les données saisies par l'Utilisateur dans le Service</strong> (données de ses clients, des invités à ses événements, données de facturation associées) : pour ces données, ABhD agit en qualité de sous-traitant au sens du Règlement Général sur la Protection des Données (RGPD), pour le compte de l'Utilisateur qui en est le responsable de traitement. L'Utilisateur garantit disposer d'une base légale pour la collecte et le traitement de ces données et s'engage à respecter la réglementation applicable vis-à-vis de ses propres clients et invités.</li>
</ul>
<p>ABhD met en œuvre les mesures techniques et organisationnelles raisonnables pour assurer la sécurité et la confidentialité des données hébergées. Le paiement des abonnements est traité par le prestataire Stripe ; ABhD ne stocke aucune donnée de carte bancaire sur ses propres serveurs.</p>
<p>Les données sont hébergées par ABhD (abhd.fr) en France. Elles sont conservées pendant toute la durée du contrat, puis archivées ou supprimées conformément aux durées de conservation légales et à l'article « Réversibilité » des CGV en cas de résiliation.</p>
<p>Le Service propose les fonctionnalités concrètes suivantes pour l'exercice des droits RGPD :</p>
<ul>
<li>L'Utilisateur peut, à tout moment, exporter ses propres données de compte et supprimer son compte depuis la rubrique « Mon compte » de son espace ; si l'Utilisateur est le seul membre de son organisation, la suppression de son compte entraîne la suppression de l'organisation et de l'ensemble de ses données.</li>
<li>Un administrateur peut supprimer définitivement toute l'organisation (et les comptes de ses membres) depuis Paramètres &gt; Organisation.</li>
<li>Le client d'une organisation peut, depuis son portail client (lien personnel transmis par l'organisateur), exporter ses propres données (événements, devis, factures, messages) et transmettre une demande de suppression de ses données à l'organisation, laquelle devra y donner suite sous réserve de ses propres obligations légales de conservation (notamment comptables sur les factures).</li>
<li>Un invité à un événement peut, depuis son lien d'invitation, consulter, modifier ou supprimer les données qu'il a transmises (réponse RSVP, régime alimentaire, accompagnants).</li>
</ul>
<p>Pour toute autre demande relative à ses droits d'accès, de rectification, de limitation ou d'opposition, l'Utilisateur peut contacter ABhD via le formulaire de support de l'espace client. Vous disposez du droit d'introduire une réclamation auprès de la CNIL (<a href="https://www.cnil.fr">www.cnil.fr</a>).</p>

<h2>7. Propriété intellectuelle</h2>
<p>Le logiciel EventPlanner, sa structure, ses codes sources, bases de données, textes et éléments graphiques sont la propriété exclusive d'ABhD et sont protégés par le droit de la propriété intellectuelle. L'Utilisateur bénéficie d'un droit d'usage personnel, non exclusif et non transférable du Service, pour la durée de son abonnement.</p>
<p>L'Utilisateur conserve l'entière propriété des données et contenus qu'il importe ou saisit dans le Service.</p>

<h2>8. Disponibilité et maintenance</h2>
<p>ABhD met en œuvre tous les moyens raisonnables pour assurer un accès continu au Service, sans toutefois garantir une disponibilité ininterrompue. Le Service peut être temporairement suspendu pour des opérations de maintenance, de mise à jour, ou pour des raisons indépendantes de la volonté d'ABhD (panne, incident chez l'hébergeur, force majeure). Dans la mesure du possible, les interventions de maintenance planifiées sont annoncées à l'avance.</p>

<h2>9. Suspension et résiliation pour manquement</h2>
<p>En cas de manquement de l'Utilisateur aux présentes CGU, notamment en cas d'usage frauduleux, illicite ou portant atteinte au bon fonctionnement du Service, ABhD se réserve le droit de suspendre ou de résilier l'accès au compte concerné, après notification lorsque cela est possible, sans préjudice de toute action en réparation du dommage subi.</p>

<h2>10. Cookies</h2>
<p>Le Service utilise uniquement des cookies strictement nécessaires à son fonctionnement (session de connexion, sécurité). Aucun cookie de mesure d'audience ou publicitaire n'est déposé à ce jour ; les présentes CGU seront mises à jour si cela évolue.</p>

<h2>11. Modification des CGU</h2>
<p>ABhD peut modifier les présentes CGU à tout moment, notamment pour les adapter à des évolutions légales, réglementaires ou fonctionnelles. Les Utilisateurs seront informés de toute modification substantielle par email ou notification dans le Service, avec un préavis raisonnable avant son entrée en vigueur. La poursuite de l'utilisation du Service après cette date vaut acceptation des CGU modifiées.</p>

<h2>12. Droit applicable et litiges</h2>
<p>Les présentes CGU sont soumises au droit français. En cas de litige, une solution amiable sera recherchée en priorité avant toute action judiciaire, notamment via le dispositif de médiation de la consommation décrit dans les CGV pour les Utilisateurs consommateurs.</p>

<h2>13. Contact</h2>
<p>Pour toute question relative aux présentes CGU, l'Utilisateur peut contacter ABhD via le formulaire de support accessible depuis son espace client, ou par courrier à l'adresse indiquée dans les mentions légales.</p>
HTML;

$cgv = <<<'HTML'
<p><em>Dernière mise à jour : à compléter à la publication.</em></p>

<h2>1. Objet et champ d'application</h2>
<p>Les présentes Conditions Générales de Vente (« CGV ») régissent la souscription aux offres payantes du service EventPlanner, édité par ABhD, entreprise individuelle immatriculée sous le numéro SIREN 987 664 182, dont le siège est situé 2 rue Ricarde de Buffet, 46100 Capdenac, France (« l'Éditeur »). Elles complètent les Conditions Générales d'Utilisation (CGU) et s'appliquent à tout Client souscrivant à une offre payante, qu'il soit professionnel ou consommateur au sens du Code de la consommation.</p>
<p>La souscription à une offre payante emporte acceptation sans réserve des présentes CGV.</p>

<h2>2. Offres et tarifs</h2>
<p>Les offres, leurs fonctionnalités et leurs tarifs sont présentés sur la page tarifs du site et/ou dans l'espace client au moment de la souscription. Les prix sont indiqués en euros. ABhD relevant du régime de la franchise en base de TVA (article 293 B du Code général des impôts), les prix affichés ne comportent pas de TVA (mention « TVA non applicable, art. 293 B du CGI »).</p>
<p>ABhD se réserve le droit de modifier ses tarifs à tout moment pour les nouvelles souscriptions. Pour les Clients déjà abonnés, toute modification tarifaire leur sera notifiée avec un préavis d'au moins 30 jours avant son application ; le Client pourra résilier son abonnement avant l'entrée en vigueur du nouveau tarif s'il ne l'accepte pas.</p>

<h2>3. Souscription</h2>
<p>La souscription à une offre payante s'effectue en ligne depuis l'espace client, après création d'un compte. Le Client garantit l'exactitude des informations transmises lors de la souscription (identité, informations de facturation).</p>

<h2>4. Modalités de paiement</h2>
<p>Le paiement des abonnements s'effectue par carte bancaire, via le prestataire de paiement Stripe. En souscrivant, le Client autorise le prélèvement récurrent du montant de son abonnement selon la périodicité de l'offre choisie (mensuelle, sauf mention contraire). ABhD ne collecte ni ne stocke les coordonnées bancaires du Client, celles-ci étant traitées directement par Stripe.</p>
<p>En cas d'échec de paiement, ABhD peut suspendre l'accès aux fonctionnalités payantes jusqu'à régularisation, après en avoir informé le Client.</p>

<h2>5. Durée, résiliation et renouvellement</h2>
<p>Les abonnements payants sont conclus sans engagement de durée minimale et sont facturés par périodes mensuelles, reconduites tacitement à chaque échéance. Le Client peut résilier son abonnement à tout moment en supprimant son organisation depuis Paramètres &gt; Organisation (ou son propre compte depuis « Mon compte », si applicable), ou en le demandant via le formulaire de support ; la résiliation prend effet à la fin de la période de facturation en cours, sans remboursement de la période déjà entamée, sauf erreur de facturation imputable à ABhD.</p>
<p>ABhD peut résilier l'abonnement d'un Client en cas de manquement grave aux présentes CGV ou aux CGU, dans les conditions décrites à l'article « Suspension et résiliation pour manquement » des CGU.</p>

<h2>6. Droit de rétractation (Clients consommateurs)</h2>
<p>Conformément aux articles L. 221-18 et suivants du Code de la consommation, tout Client agissant en tant que consommateur (non-professionnel) dispose d'un délai de 14 jours à compter de la souscription pour exercer son droit de rétractation, sans avoir à justifier de motif.</p>
<p>Toutefois, l'accès au Service étant activé immédiatement après souscription, le Client consommateur qui souhaite bénéficier du Service sans attendre l'expiration de ce délai peut, lors de la souscription, demander expressément l'exécution immédiate du Service et reconnaît qu'il renonce ainsi à son droit de rétractation dès lors que l'exécution est complète, ou qu'il devra, en cas de rétractation après exécution partielle, payer un montant correspondant au service fourni jusqu'à la notification de sa rétractation.</p>
<p>Pour exercer son droit de rétractation, le Client peut notifier sa décision via le formulaire de support de son espace client ou par courrier à l'adresse indiquée dans les mentions légales, avant l'expiration du délai de 14 jours.</p>

<h2>7. Responsabilité et garanties</h2>
<p>ABhD s'engage à fournir le Service avec diligence et selon les règles de l'art, sans garantir une disponibilité ininterrompue ni l'absence totale d'erreurs (voir article « Disponibilité et maintenance » des CGU).</p>
<p>La responsabilité d'ABhD ne pourra être engagée qu'en cas de faute prouvée, et est limitée aux dommages directs subis par le Client. Elle est exclue pour tout dommage indirect (perte de chiffre d'affaires, perte de clientèle, préjudice commercial). Dans tous les cas, la responsabilité totale d'ABhD au titre d'un contrat est plafonnée au montant total payé par le Client au titre de son abonnement au cours des douze (12) mois précédant le fait générateur du dommage.</p>
<p>Ces limitations ne s'appliquent pas aux dommages résultant d'une faute lourde ou dolosive d'ABhD, ni aux cas où la loi interdit une telle limitation à l'égard des consommateurs.</p>

<h2>8. Réversibilité et export des données</h2>
<p>À tout moment de son abonnement, le Client peut exporter ses données depuis les fonctionnalités d'export prévues dans le Service (notamment les exports CSV clients/factures disponibles dans Paramètres, et l'export de ses propres données de compte depuis « Mon compte »). La suppression d'une organisation ou d'un compte étant immédiate et irréversible (voir CGU, article « Données personnelles »), il appartient au Client d'exporter les données qu'il souhaite conserver avant de procéder à une telle suppression.</p>

<h2>9. Force majeure</h2>
<p>Aucune des parties ne pourra être tenue responsable d'un manquement à ses obligations résultant d'un cas de force majeure au sens de l'article 1218 du Code civil.</p>

<h2>10. Médiation de la consommation et réclamations</h2>
<p>Pour toute réclamation, le Client peut contacter ABhD via le formulaire de support de son espace client. Conformément aux articles L. 616-1 et suivants du Code de la consommation, tout Client consommateur a le droit de recourir gratuitement à un médiateur de la consommation en vue de la résolution amiable d'un litige, après démarche préalable écrite auprès d'ABhD. Le médiateur compétent sera : <em>[NOM DU MÉDIATEUR DE LA CONSOMMATION À DÉSIGNER — coordonnées et site web à compléter avant publication]</em>.</p>
<p>Les Clients ressortissants de l'Union européenne peuvent également recourir à la plateforme européenne de règlement en ligne des litiges : <a href="https://ec.europa.eu/consumers/odr">https://ec.europa.eu/consumers/odr</a>.</p>

<h2>11. Droit applicable et juridiction compétente</h2>
<p>Les présentes CGV sont soumises au droit français. À défaut de résolution amiable, tout litige relève de la compétence des tribunaux français ; pour les Clients professionnels, compétence exclusive est attribuée aux tribunaux du ressort du siège social d'ABhD, sauf disposition légale impérative contraire.</p>

<h2>12. Contact</h2>
<p>Pour toute question relative aux présentes CGV, le Client peut contacter ABhD via le formulaire de support accessible depuis son espace client, ou par courrier à l'adresse indiquée dans les mentions légales.</p>
HTML;

$pages = [
    ['slug' => 'mentions-legales', 'title' => 'Mentions légales', 'content' => $mentionsLegales, 'meta_description' => "Mentions légales du service EventPlanner, édité par ABhD."],
    ['slug' => 'cgu', 'title' => "Conditions Générales d'Utilisation", 'content' => $cgu, 'meta_description' => "Conditions générales d'utilisation du service EventPlanner."],
    ['slug' => 'cgv', 'title' => 'Conditions Générales de Vente', 'content' => $cgv, 'meta_description' => "Conditions générales de vente des abonnements EventPlanner."],
];

$upsertPage = $pdo->prepare(
    'INSERT INTO site_pages (slug, title, content, meta_description, is_published)
     VALUES (:slug, :title, :content, :meta_description, 1)
     ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), meta_description = VALUES(meta_description)'
);

foreach ($pages as $page) {
    $upsertPage->execute($page);
    echo "Page « {$page['title']} » enregistrée (/page/{$page['slug']}).\n";
}

$footerLinks = [
    ['label' => 'Mentions légales', 'url' => '/page/mentions-legales', 'sort_order' => 90],
    ['label' => "CGU", 'url' => '/page/cgu', 'sort_order' => 91],
    ['label' => 'CGV', 'url' => '/page/cgv', 'sort_order' => 92],
];

$existsStmt = $pdo->prepare("SELECT id FROM site_menu_items WHERE location = 'footer' AND url = ? LIMIT 1");
$insertStmt = $pdo->prepare(
    "INSERT INTO site_menu_items (location, label, url, sort_order, is_active) VALUES ('footer', :label, :url, :sort_order, 1)"
);

foreach ($footerLinks as $link) {
    $existsStmt->execute([$link['url']]);
    if ($existsStmt->fetch()) {
        echo "Lien de pied de page vers {$link['url']} déjà présent, non dupliqué.\n";
        continue;
    }
    $insertStmt->execute($link);
    echo "Lien de pied de page « {$link['label']} » ajouté.\n";
}

echo "Terminé.\n";
