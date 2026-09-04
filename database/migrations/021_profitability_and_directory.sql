-- Score de rentabilité par événement : aucune nouvelle colonne nécessaire,
-- calculé à la volée à partir des factures (revenu) et des prestataires
-- confirmés (event_providers.cost, déjà alimenté manuellement ou via les
-- bons de commande — voir PurchaseOrder::syncEventProvider()). Voir
-- App\Models\Event::profitability() / ReportController::profitability().

-- Annuaire public des organisations + avis clients vérifiés : une
-- organisation choisit de figurer dans l'annuaire public (App\Controllers\
-- DirectoryController), et chaque avis n'est publiable qu'avec le
-- consentement explicite du client donné au moment du sondage
-- (satisfaction_surveys.consent_public), l'organisation choisissant ensuite
-- lesquels afficher (is_published).

ALTER TABLE company_settings
    ADD COLUMN IF NOT EXISTS directory_listed TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS directory_slug VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS directory_description TEXT,
    ADD COLUMN IF NOT EXISTS directory_specialties VARCHAR(255) DEFAULT '';

ALTER TABLE company_settings
    ADD UNIQUE KEY uniq_directory_slug (directory_slug);

ALTER TABLE satisfaction_surveys
    ADD COLUMN IF NOT EXISTS consent_public TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS is_published TINYINT(1) NOT NULL DEFAULT 0;
