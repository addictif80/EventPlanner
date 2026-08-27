-- Couleur de marque de l'organisation, utilisée pour personnaliser
-- (marque blanche) le portail client et les emails envoyés à ses clients.
ALTER TABLE company_settings
    ADD COLUMN IF NOT EXISTS brand_color VARCHAR(9) NOT NULL DEFAULT '#3b56d9';
