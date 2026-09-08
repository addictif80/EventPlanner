-- Rapport mensuel URSSAF pour la plateforme elle-même (ABhD, auto-entrepreneur) :
-- un relevé PDF du chiffre d'affaires encaissé sur le compte Stripe plateforme
-- (abonnements/modules payés par les organisations), envoyé automatiquement
-- aux super admins à une date configurable. Voir App\Models\PlatformRevenueTransaction,
-- App\Core\UrssafPlatformReportPdf, bin/send_urssaf_platform_report.php.
--
-- Chaque encaissement Stripe réel (événement webhook invoice.paid — voir
-- SubscriptionController::webhook()) est journalisé ici ; ce n'est donc
-- alimenté qu'à partir du moment où cette migration est en place, pas
-- rétroactivement pour les paiements déjà passés.

CREATE TABLE IF NOT EXISTS platform_revenue_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED DEFAULT NULL,
    organization_name VARCHAR(190) NOT NULL DEFAULT '',
    stripe_invoice_id VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'eur',
    description VARCHAR(255) DEFAULT '',
    paid_at DATETIME NOT NULL,
    CONSTRAINT fk_prt_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_prt_invoice (stripe_invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS urssaf_report_day TINYINT UNSIGNED DEFAULT NULL COMMENT 'Jour du mois (1-28) d''envoi du rapport URSSAF plateforme ; NULL = désactivé',
    ADD COLUMN IF NOT EXISTS urssaf_report_last_sent_month VARCHAR(7) DEFAULT NULL COMMENT 'AAAA-MM du dernier mois déjà envoyé, pour ne jamais doubler un envoi';
