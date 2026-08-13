# EventPlanner

Panel complet de gestion pour organisateur d'événements (tous types : mariages,
corporate, festivals, anniversaires...) : clients, événements, invités/RSVP,
billetterie, prestataires, lieux, matériel, devis, factures, avoirs, contrats
avec signature électronique, jour-J, portail client, reporting — avec envoi
d'emails via un **serveur SMTP personnalisable**.

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
bin/             Scripts CLI (création admin, cron facturation récurrente, relances)
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

### 4. Créer le premier compte administrateur

Via SSH sur la VM :

```bash
cd /chemin/vers/EventPlanner
php bin/create_admin.php "Votre Nom" vous@example.com VotreMotDePasse
```

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
`schema.sql` (il recréerait tout) : appliquez uniquement la migration
incrémentale correspondante, par exemple :

```bash
mysql -u <db_user> -p <db_name> < database/migrations/002_advanced_features.sql
```

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
