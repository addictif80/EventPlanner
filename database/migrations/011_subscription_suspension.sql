-- Suspension automatique (optionnelle) d'une organisation après un délai de
-- grâce configurable en cas d'abonnement impayé (statut Stripe past_due).

ALTER TABLE organization_subscriptions
    ADD COLUMN IF NOT EXISTS past_due_since DATETIME DEFAULT NULL;

ALTER TABLE organizations
    ADD COLUMN IF NOT EXISTS suspension_reason VARCHAR(20) DEFAULT NULL;

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS subscription_auto_suspend_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS subscription_grace_period_days INT UNSIGNED NOT NULL DEFAULT 7;
