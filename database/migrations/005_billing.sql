-- Ajoute le système d'abonnement payant (offres, modules, packages,
-- abonnement Stripe Subscriptions par organisation) à une base déjà en
-- multi-tenant + administration plateforme (schema.sql + migrations
-- 002/003/004 déjà appliquées).

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS stripe_secret_key VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS stripe_publishable_key VARCHAR(190) DEFAULT '',
    ADD COLUMN IF NOT EXISTS stripe_webhook_secret VARCHAR(190) DEFAULT '';

CREATE TABLE IF NOT EXISTS modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stripe_product_id VARCHAR(120) DEFAULT '',
    stripe_price_id VARCHAR(120) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_members INT UNSIGNED DEFAULT NULL,
    stripe_product_id VARCHAR(120) DEFAULT '',
    stripe_price_id VARCHAR(120) DEFAULT '',
    is_default_signup TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_modules (
    plan_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (plan_id, module_id),
    CONSTRAINT fk_plan_modules_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_modules_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stripe_product_id VARCHAR(120) DEFAULT '',
    stripe_price_id VARCHAR(120) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_package_items (
    package_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (package_id, module_id),
    CONSTRAINT fk_package_items_package FOREIGN KEY (package_id) REFERENCES module_packages(id) ON DELETE CASCADE,
    CONSTRAINT fk_package_items_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL UNIQUE,
    plan_id INT UNSIGNED DEFAULT NULL,
    status ENUM('trialing', 'active', 'past_due', 'canceled', 'incomplete') NOT NULL DEFAULT 'incomplete',
    stripe_customer_id VARCHAR(120) DEFAULT '',
    stripe_subscription_id VARCHAR(120) DEFAULT '',
    current_period_end DATETIME DEFAULT NULL,
    cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_sub_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_org_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_subscription_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_subscription_id INT UNSIGNED NOT NULL,
    item_type ENUM('plan', 'module', 'package') NOT NULL,
    module_id INT UNSIGNED DEFAULT NULL,
    package_id INT UNSIGNED DEFAULT NULL,
    stripe_subscription_item_id VARCHAR(120) DEFAULT '',
    stripe_price_id VARCHAR(120) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_items_sub FOREIGN KEY (organization_subscription_id) REFERENCES organization_subscriptions(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_items_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_items_package FOREIGN KEY (package_id) REFERENCES module_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO modules (module_key, name, description) VALUES
    ('contracts', 'Contrats & signature électronique', 'Génération de contrats et signature électronique en ligne.'),
    ('purchase_orders', 'Bons de commande fournisseurs', 'Émission de bons de commande auprès des prestataires.'),
    ('equipment', 'Gestion de stock matériel', 'Réservation de matériel avec détection de surbooking.'),
    ('ticketing', 'Billetterie & check-in', "Catégories de billets, génération et contrôle d'accès jour J."),
    ('guests', 'Invités, RSVP & plans de table', "Listes d'invités, confirmation RSVP publique, plans de table."),
    ('reports', 'Rapports & analytics avancés', 'Graphiques de chiffre d\'affaires, conversion, prévisionnel.'),
    ('client_portal', 'Portail client', 'Accès self-service en lecture seule pour vos clients.'),
    ('stripe_payments', 'Paiement en ligne (factures)', 'Génération de liens de paiement Stripe sur vos factures.'),
    ('recurring_invoices', 'Factures récurrentes', 'Génération automatique des échéances de factures récurrentes.'),
    ('satisfaction_survey', 'Sondage de satisfaction', 'Envoi de sondages post-événement.'),
    ('calendar_ics', 'Flux calendrier ICS', 'Abonnement Google Calendar / Outlook / Apple Calendar.');

-- Offre gratuite assignée automatiquement à l'inscription à partir de
-- maintenant (fonctionnalités de base uniquement, aucun module additionnel).
INSERT IGNORE INTO plans (id, name, description, monthly_price, max_members, is_default_signup, is_active, sort_order) VALUES
    (1, 'Découverte', 'Offre gratuite par défaut à l\'inscription : fonctionnalités de base, 3 membres maximum, sans module additionnel.', 0, 3, 1, 1, 0);

-- Offre « historique » réservée aux organisations déjà existantes avant
-- l'introduction des abonnements : tous les modules inclus, sans limite de
-- membres, gratuite, pour ne rien casser rétroactivement. Non proposée aux
-- nouvelles inscriptions (is_default_signup = 0). Le super admin peut ensuite
-- migrer ces organisations vers une offre payante depuis Administration >
-- Organisations si souhaité.
INSERT IGNORE INTO plans (id, name, description, monthly_price, max_members, is_default_signup, is_active, sort_order) VALUES
    (2, 'Historique (legacy)', 'Organisations créées avant la mise en place des abonnements : accès complet conservé.', 0, NULL, 0, 1, 999);

INSERT IGNORE INTO plan_modules (plan_id, module_id)
    SELECT 2, id FROM modules;

INSERT IGNORE INTO organization_subscriptions (organization_id, plan_id, status)
    SELECT id, 2, 'active' FROM organizations;

-- Pour créer vos propres offres payantes (avec prix, nombre de membres,
-- modules inclus) et packages de modules, utilisez le panel :
-- Administration plateforme > Offres.
