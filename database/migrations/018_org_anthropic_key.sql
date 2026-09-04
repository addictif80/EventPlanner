-- Permet à chaque organisation de renseigner sa propre clé API Anthropic
-- (facturée sur son propre compte) plutôt que de dépendre uniquement de la
-- clé plateforme — voir App\Core\ClaudeClient, qui essaie d'abord la clé de
-- l'organisation puis retombe sur celle de system_settings si absente.
-- Même logique que smtp_settings/system SMTP ou stripe_secret_key déjà
-- présents à la fois au niveau organisation et plateforme.

ALTER TABLE company_settings
    ADD COLUMN IF NOT EXISTS anthropic_api_key VARCHAR(255) DEFAULT '';
