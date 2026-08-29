-- Mot de passe oublié + invitation d'équipe par email (au lieu d'un mot de
-- passe fixé directement par l'admin).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS password_reset_expires_at DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS invite_token VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS invited_at DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS invited_by INT UNSIGNED DEFAULT NULL;
