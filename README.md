# EventPlanner

Panel complet **multi-tenant** de gestion pour organisateurs d'événements
(tous types : mariages, corporate, festivals, anniversaires...) : clients,
événements, invités/RSVP, billetterie, prestataires, lieux, matériel, devis,
factures, avoirs, contrats avec signature électronique, jour-J, portail
client, reporting — avec envoi d'emails via un **serveur SMTP
personnalisable**.

**Multi-tenant** : chaque organisateur (agence, cabinet...) crée son propre
espace via `/register` — sa propre équipe (rôles admin/manager/staff), ses
propres clients, catalogue, devis, factures, paramètres SMTP, numérotation...
Les organisations sont complètement cloisonnées : aucun utilisateur ne peut
voir ni modifier les données d'une autre organisation, y compris par accès
direct à une URL (voir « Isolation multi-tenant » plus bas). Au-dessus des
organisations, un rôle **super administrateur** donne accès à un panel de
gestion de la plateforme (organisations, utilisateurs, impersonation,
documents, tickets de support, blocage IP/email, SMTP système — voir
« Administration plateforme » plus bas), y compris la définition des
**offres d'abonnement payantes** facturées automatiquement via Stripe
Subscriptions (voir « Abonnements payants » plus bas).

Stack : PHP 8.1+ / MySQL (MariaDB) / Bootstrap 5 / Chart.js (CDN). Aucune
dépendance Composer requise (autoloader, client SMTP et intégration Stripe
maison via cURL) pour rester simple à déployer sur de l'hébergement mutualisé
type CyberPanel.

## Structure

```
public/          Racine web (front controller index.php, assets)
src/Core/        Framework maison (routeur, DB, Auth, Mailer/SMTP, ActivityLog...)
src/Controllers/ Contrôleurs
src/Models/      Modèles (PDO)
views/           Vues PHP (Bootstrap 5)
database/        schema.sql (structure complète, install neuve)
database/migrations/  Migrations incrémentales (mise à jour d'une install existante)
bin/             Scripts CLI (création admin, promotion super admin, cron facturation récurrente, relances)
storage/uploads/ Documents uploadés (hors du docroot public/)
config/          Configuration (.env)
```

## Déploiement sur VM Proxmox + CyberPanel

### 1. Base de données

Dans CyberPanel : **Databases > Create Database**, créez une base
(ex. `eventplanner`) et un utilisateur dédié avec tous les privilèges.

Importez le schéma :

```bash
mysql -u <db_user> -p <db_name> < database/schema.sql
```

(ou via phpMyAdmin, accessible depuis CyberPanel, onglet Importer)

### 2. Déploiement des fichiers

Dans CyberPanel, créez le site (Websites > Create Website), puis déployez
le contenu du dépôt dans le dossier du site (via Git, SFTP ou File Manager).

**Important : le docroot du site (dans le vHost / configuration OpenLiteSpeed
de CyberPanel) doit pointer vers le sous-dossier `public/`**, et non vers la
racine du dépôt. Cela garde tout le code applicatif (`src/`, `config/`,
`database/`) hors d'atteinte du web.

Dans CyberPanel : *Websites > List Websites > Manage > vHost Conf*, ajustez
`docRoot` pour qu'il pointe vers `.../public`.

### 3. Configuration

Copiez `.env.example` en `.env` à la racine du projet (pas dans `public/`) et
renseignez :

```
APP_URL=https://votre-domaine.tld
DB_HOST=localhost
DB_NAME=eventplanner
DB_USER=...
DB_PASS=...
APP_KEY=<chaîne aléatoire>
```

### 4. Créer votre organisation

Deux options :

- **Self-service** : rendez-vous sur `https://votre-domaine.tld/register` et
  créez votre organisation + compte administrateur directement depuis le
  navigateur (c'est la voie normale pour chaque nouvel organisateur).
- **Via SSH** (utile pour le tout premier déploiement ou un script
  d'installation automatisé) :

```bash
cd /chemin/vers/EventPlanner
php bin/create_admin.php "Nom de l'agence" "Votre Nom" vous@example.com VotreMotDePasse
```

Chaque appel (self-service ou CLI) crée une **nouvelle organisation**
indépendante avec son propre catalogue, ses propres paramètres SMTP/entreprise
et sa numérotation de devis/factures repartant à 001.

### 5. Permissions

```bash
chown -R <user-cyberpanel>:<user-cyberpanel> storage/
chmod -R 775 storage/
```

### 6. Se connecter

Rendez-vous sur `https://votre-domaine.tld/login` et connectez-vous avec le
compte créé à l'étape 4.

## Configuration du serveur SMTP (envoi des devis/factures)

Une fois connecté en tant qu'administrateur, allez dans **Paramètres > Email
(SMTP)**. Vous pouvez y renseigner n'importe quel serveur SMTP :

- Un serveur mail que vous gérez vous-même sur la VM (ex. via CyberPanel >
  Email, qui embarque un serveur mail complet — utilisez alors
  `mail.votre-domaine.tld`, port 587, STARTTLS).
- Un fournisseur externe (OVH, Gmail avec mot de passe d'application, SendGrid
  en mode SMTP relay, etc.)

Champs : hôte, port, chiffrement (STARTTLS/587, SSL implicite/465, ou aucun),
identifiant, mot de passe, email et nom d'expéditeur. Un bouton **« Envoyer un
email de test »** permet de vérifier la configuration immédiatement.

Le client SMTP est implémenté nativement (`src/Core/SmtpClient.php`), sans
dépendance externe.

### Mise à jour d'une installation existante

Si vous avez déjà une base EventPlanner en production, n'importez pas
`schema.sql` (il recréerait tout) : appliquez les migrations incrémentales
manquantes, dans l'ordre :

```bash
mysql -u <db_user> -p <db_name> < database/migrations/002_advanced_features.sql
mysql -u <db_user> -p <db_name> < database/migrations/003_multi_tenant.sql
mysql -u <db_user> -p <db_name> < database/migrations/004_platform_admin.sql
mysql -u <db_user> -p <db_name> < database/migrations/005_billing.sql
```

La migration `003` (passage au multi-tenant) regroupe toutes vos données
existantes dans une organisation « Mon organisation » unique — voir le
commentaire en tête du fichier avant de l'exécuter. Si votre base ne contient
que des données de test, il est plus simple de repartir d'une base vide avec
`schema.sql`.

### Tâches planifiées (cron) recommandées

```cron
# Relance automatique des factures en retard, tous les jours à 9h
0 9 * * * php /chemin/vers/EventPlanner/bin/send_overdue_reminders.php

# Génération des prochaines échéances de factures récurrentes, tous les jours à 6h
0 6 * * * php /chemin/vers/EventPlanner/bin/generate_recurring_invoices.php
```

## Fonctionnalités couvertes

### Clients & CRM
Fiches client (particulier/entreprise), recherche, tags, notes internes,
historique complet (événements, devis, factures, documents), **portail client
en libre-service** (lien magique sans mot de passe, accès lecture seule).

### Événements
Fiche complète (type, lieu, budget, invités), checklist de tâches assignables,
prestataires liés avec coût, matériel réservé (avec détection de conflit de
stock par date), documents attachés, notes internes.

### Invités, RSVP & billetterie
Gestion des invités par événement, envoi d'invitations par email avec lien de
confirmation **RSVP public** (sans compte requis), plans de table simples
(tables + capacité + placement), catégories de billets, génération de billets
avec code unique, **page de check-in** pour le contrôle d'accès jour J.

### Devis & factures
Lignes dynamiques, calcul automatique, numérotation configurable, statuts,
envoi par email (modèles personnalisables), impression PDF (impression
navigateur), conversion devis → facture, paiements multiples, statut auto
(payée / partielle / en retard), **avoirs**, **factures récurrentes** (avec
script cron de génération automatique), **paiement en ligne Stripe optionnel**
(lien Checkout généré via l'API Stripe en cURL — nécessite vos propres clés).

### Prestataires & fournisseurs
Répertoire de prestataires, **bons de commande** avec lignes et impression,
**gestion de stock matériel** avec réservation par événement et détection de
surbooking.

### Contrats & documents
Génération de contrats depuis un modèle éditable, envoi pour **signature
électronique** (page publique avec capture de signature manuscrite + nom +
IP + horodatage, sans dépendance externe type DocuSign), gestion documentaire
(upload/téléchargement) attachée aux clients et événements.

### Jour-J
Feuille de route horaire, liste de contacts d'urgence, journal d'incidents —
pages optimisées mobile pour l'équipe sur le terrain.

### Communication
Notes internes (par client/événement), modèles d'emails éditables (devis,
facture, relance), relance manuelle ou automatique (cron) des impayés,
sondage de satisfaction post-événement (lien public).

### Reporting & administration
Tableau de bord (CA, impayés, devis en attente, événements à venir),
page Rapports avec graphiques (CA par mois/type d'événement, top clients,
top prestataires, taux de conversion des devis, prévisionnel de trésorerie),
export CSV comptable des factures, journal d'activité (audit log),
gestion des utilisateurs et rôles (admin/manager/staff).

### Intégrations
- **SMTP personnalisable** pour tous les envois d'emails
- **Flux calendrier ICS** abonnable (Google Calendar, Outlook, Apple Calendar)
- **Stripe** (paiement en ligne, optionnel, clés à fournir dans Paramètres)
- **Export CSV** des emails clients pour un outil d'emailing externe (Mailchimp...)

## Choix pragmatiques sur les intégrations tierces

Certains outils du marché (DocuSign, Twilio SMS, synchronisation OAuth
Google Calendar, Mailchimp API) nécessitent vos propres comptes/clés
développeur payants. Pour rester utilisable immédiatement sans configuration
tierce, ce panel propose des équivalents fonctionnels maison :
signature électronique intégrée (au lieu de DocuSign), flux ICS abonnable
(au lieu d'une synchro OAuth Google Calendar), export CSV (au lieu de l'API
Mailchimp). Le paiement Stripe est la seule intégration tierce câblée
nativement (via cURL, sans SDK), à activer avec vos propres clés API.

Non couvert dans cette version : application mobile native (l'interface est
responsive et utilisable sur mobile), envoi de SMS.

## Isolation multi-tenant

Toutes les tables métier portent une colonne `organization_id`. L'isolation
est appliquée à deux niveaux :

1. **`App\Core\Model`** (classe de base de tous les modèles) filtre
   automatiquement `find()`, `all()`, `where()`, `count()`, `update()` et
   `delete()` par `organization_id = Auth::organizationId()`, et l'injecte
   automatiquement sur `create()`.
2. **Requêtes SQL manuelles** (jointures, agrégats de rapports, exports...) :
   chacune a été auditée pour ajouter explicitement `AND organization_id = ?`.

Les routes **publiques** (RSVP invité, signature de contrat, sondage de
satisfaction, portail client, flux ICS, retour de paiement Stripe) n'ont pas
de session utilisateur : l'organisation y est retrouvée via le jeton unique
de l'URL (ou les métadonnées de la session Stripe), jamais via
`Auth::organizationId()`.

Les scripts cron (`bin/send_overdue_reminders.php`,
`bin/generate_recurring_invoices.php`) tournent hors contexte HTTP et
parcourent toutes les organisations : chaque itération positionne
manuellement le contexte d'organisation courant avant d'appeler les modèles
scopés.

Cette isolation a été testée bout en bout avec deux organisations distinctes
(clients, événements, devis avec numérotation indépendante, documents,
export CSV, utilisateurs, flux ICS, portail client, RSVP) : tout accès direct
d'une organisation aux données d'une autre renvoie une 404, et aucune liste
ou export ne mélange les deux.

## Administration plateforme (super admin)

Au-dessus des organisations (tenants), un rôle **super administrateur**
(`users.is_super_admin`) donne accès à un panel `/admin` séparé, réservé à
l'équipe qui exploite la plateforme elle-même :

- **Tableau de bord** : organisations, utilisateurs, tickets ouverts, IP/emails
  bloqués.
- **Organisations** : liste, détail, **suspension/réactivation** (un compte
  suspendu ne peut plus se connecter, quel que soit son mot de passe).
- **Utilisateurs** (toutes organisations confondues) : activation/désactivation,
  promotion/rétrogradation super admin, recherche, et **impersonation**
  (« se connecter en tant que ») pour du support sans connaître le mot de
  passe du client — une bannière visible indique quand une session est
  usurpée, avec un bouton de retour immédiat au compte admin d'origine.
- **Documents** : accès en lecture/suppression à tous les documents uploadés,
  toutes organisations confondues.
- **Blocage IP / email** : une IP bloquée reçoit un 403 sur toute la
  plateforme (vérifié à chaque requête) ; un email bloqué ne peut ni se
  connecter ni s'inscrire.
- **Tickets de support** : chaque organisation peut ouvrir un ticket depuis
  **Support** dans son propre panel ; le super admin y répond depuis
  `/admin/tickets`, avec notification email au client si le SMTP système est
  configuré.
- **Paramètres système** (`/admin/settings`) : serveur SMTP dédié aux emails
  envoyés par la plateforme elle-même (réponses aux tickets, notifications de
  compte...), entièrement distinct du SMTP propre à chaque organisation.
- **Journal d'activité** (`/admin/activity-log`) : audit séparé
  (`admin_activity_log`) de toutes les actions de niveau plateforme
  (suspension, blocage, impersonation, réponses aux tickets...).

Toutes ces routes sont protégées par `Auth::requireSuperAdmin()` (403 sinon)
et sont indépendantes du scoping `organization_id` des modèles standards —
elles interrogent volontairement toutes les organisations.

### Créer le premier super administrateur

```bash
# 1. Créez d'abord un compte normal (organisation + admin), si ce n'est pas déjà fait :
php bin/create_admin.php "Mon agence" "Votre Nom" vous@example.com VotreMotDePasse

# 2. Promouvez ce compte en super administrateur de la plateforme :
php bin/make_super_admin.php vous@example.com
```

## Abonnements payants

Le super admin définit des **offres** (plans), des **modules** à l'unité et
des **packages de modules**, facturés automatiquement chaque mois via
**Stripe Subscriptions** — sur le compte Stripe de la **plateforme**
(`system_settings.stripe_*`), entièrement distinct du compte Stripe que
chaque organisation peut renseigner elle-même pour encaisser ses propres
factures clients (`company_settings.stripe_secret_key`, voir plus haut).

### Modèle

- **Offres (plans)** : nom, prix mensuel, nombre de membres maximum
  (illimité si vide), liste de modules inclus. Une offre peut être marquée
  « par défaut à l'inscription » (offre gratuite `Découverte` fournie par
  défaut : fonctionnalités de base, 3 membres, aucun module).
- **Modules** : fonctionnalités avancées vendables à l'unité en supplément
  d'une offre — `contracts` (contrats + signature), `purchase_orders` (bons
  de commande), `equipment` (stock matériel), `ticketing` (billetterie +
  check-in), `guests` (invités/RSVP/plans de table), `reports` (rapports
  avancés), `client_portal` (portail client), `stripe_payments` (paiement en
  ligne sur les factures), `recurring_invoices` (factures récurrentes),
  `satisfaction_survey` (sondages), `calendar_ics` (flux calendrier). Les
  fonctionnalités de base (clients, événements, devis, factures, paramètres,
  utilisateurs) ne sont **jamais** verrouillées, quelle que soit l'offre.
- **Packages de modules** : bundle de plusieurs modules vendu à un prix
  global, généralement inférieur à la somme des modules pris séparément.

Tout se gère dans **Administration plateforme > Offres**
(`/admin/offers`) : création/modification des plans, modules et packages,
avec synchronisation automatique des `Product`/`Price` Stripe correspondants
(`App\Core\StripeBilling::syncPrice()` — un changement de prix crée un
nouveau `Price` Stripe, l'ancien étant conservé archivé pour les abonnés déjà
engagés dessus, les prix Stripe étant immuables).

### Souscription et paiement

Chaque organisation gère son abonnement depuis **Paramètres > Abonnement**
(`/subscription`) :

1. **Première souscription** (offre payante et/ou modules payants) : un
   Checkout Stripe (`mode=subscription`) est créé avec toutes les lignes
   choisies (offre + modules + packages) ; la carte est enregistrée sur ce
   premier paiement.
2. **Modifications ultérieures** (changer d'offre, ajouter/retirer un
   module) : utilisent directement l'API Stripe Subscription Items sur
   l'abonnement existant (pas de nouveau Checkout), le moyen de paiement
   étant déjà enregistré — proratisation gérée automatiquement par Stripe.
3. **Sélection entièrement gratuite** (offre `Découverte` sans module
   payant) : aucun appel Stripe, l'abonnement est activé localement.

Le nombre d'utilisateurs d'une organisation est bloqué à `max_members` de son
offre (`ModuleAccess::memberLimitReached()`), et l'accès à chaque module
verrouillé passe par `ModuleAccess::requireModule('...')` côté contrôleur
(403 si non souscrit) + masquage des liens de menu correspondants.

Le super admin peut aussi **assigner une offre manuellement** depuis la fiche
d'une organisation (`/admin/organizations/{id}`), sans passer par Stripe —
utile pour les comptes historiques, gratuits ou promotionnels (c'est
d'ailleurs ce que fait automatiquement la migration `005_billing.sql` pour
les organisations existant avant l'introduction des abonnements : elles sont
basculées sur une offre « Historique (legacy) » gratuite avec tous les
modules inclus, pour ne rien casser rétroactivement).

### Configuration Stripe côté plateforme

Dans **Administration plateforme > Paramètres système** (`/admin/settings`),
renseignez :

- **Clé secrète** et **clé publiable** Stripe de votre compte plateforme.
- **Secret du webhook** : créez un endpoint Stripe (Développeurs > Webhooks)
  pointant vers `https://votre-domaine.tld/subscription/webhook`, écoutant
  les événements `checkout.session.completed`,
  `customer.subscription.updated`, `customer.subscription.deleted` et
  `invoice.payment_failed` ; copiez le secret de signature (`whsec_...`)
  dans ce champ. La vérification de signature est implémentée nativement
  (`StripeBilling::verifyWebhookSignature()`, HMAC SHA-256), sans SDK Stripe.
