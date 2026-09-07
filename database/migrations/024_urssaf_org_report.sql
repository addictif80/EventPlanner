-- Même rapport URSSAF mensuel automatique que pour la plateforme
-- (migration 023), mais côté organisation : chaque organisateur peut
-- recevoir chaque mois un PDF du chiffre d'affaires (le sien, encaissé
-- auprès de ses propres clients) à déclarer — voir
-- App\Core\UrssafOrgReportPdf et bin/send_urssaf_org_reports.php.

ALTER TABLE company_settings
    ADD COLUMN IF NOT EXISTS urssaf_report_day TINYINT UNSIGNED DEFAULT NULL COMMENT 'Jour du mois (1-28) d''envoi du rapport URSSAF de l''organisation ; NULL = désactivé',
    ADD COLUMN IF NOT EXISTS urssaf_report_last_sent_month VARCHAR(7) DEFAULT NULL COMMENT 'AAAA-MM du dernier mois déjà envoyé, pour ne jamais doubler un envoi';
