# EventPlanner

Panel de gestion pour organisateur d'événements : clients, événements, prestataires,
lieux, catalogue, devis, factures, paiements, tâches et paramètres — avec envoi
d'emails (devis/factures) via un **serveur SMTP personnalisable**.

Stack : PHP 8.1+ / MySQL (MariaDB) / Bootstrap 5. Aucune dépendance Composer
requise (autoloader et client SMTP maison) pour rester simple à déployer sur
de l'hébergement mutualisé type CyberPanel.

## Structure

```
public/          Racine web (front controller index.php, assets)
src/Core/        Framework maison (routeur, DB, Auth, Mailer/SMTP, vues...)
src/Controllers/ Contrôleurs
src/Models/      Modèles (PDO)
views/           Vues PHP (Bootstrap 5)
database/        schema.sql (structure complète de la base)
bin/             Scripts CLI (création du premier admin)
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

## Fonctionnalités couvertes (V1)

- Authentification, gestion des utilisateurs et rôles (admin/manager/staff)
- Clients (CRM) : fiches, recherche, historique événements/devis/factures
- Événements : fiche complète, checklist de tâches, prestataires liés
- Prestataires, lieux, catalogue de produits/prestations
- Devis : lignes dynamiques, calcul auto, statuts, envoi par email, impression
  PDF (impression navigateur), conversion en facture
- Factures : lignes dynamiques, paiements multiples, statut auto (payée /
  partiellement payée / en retard), envoi par email, impression PDF
- Tableau de bord : indicateurs clés (CA encaissé, impayés, événements à
  venir, devis en attente)
- Paramètres entreprise (coordonnées, TVA, préfixes de numérotation, pied de
  page facture) et paramètres SMTP

## Pistes d'évolution (V2)

- Billetterie / gestion d'invités et RSVP pour les événements grand public
- Signature électronique des devis/contrats
- Export comptable (CSV) et rapports avancés
- Plans de salle / seating chart
- Application mobile / mode hors-ligne jour J
- Portail client en libre-service
