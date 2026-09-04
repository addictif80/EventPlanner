-- Deux nouveaux modules différenciants :
--  - "ai_assistant" : assistant IA (Claude) pour générer des lignes de devis
--    à partir d'un brief libre et rédiger des relances de facture (voir
--    App\Core\ClaudeClient et App\Controllers\AiController).
--  - "appointments" : prise de rendez-vous en ligne (page publique par
--    organisation) pour qu'un prospect réserve un premier contact sans
--    échange d'emails (voir App\Controllers\BookingSettingsController /
--    PublicBookingController).

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS anthropic_api_key VARCHAR(255) DEFAULT '';

CREATE TABLE IF NOT EXISTS booking_settings (
    organization_id INT UNSIGNED PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    public_slug VARCHAR(80) NOT NULL,
    slot_duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    buffer_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    min_notice_hours INT UNSIGNED NOT NULL DEFAULT 24,
    max_advance_days INT UNSIGNED NOT NULL DEFAULT 60,
    weekly_hours TEXT COMMENT 'JSON {"1":{"start":"09:00","end":"18:00"}, ... "7":null}',
    location_type VARCHAR(60) NOT NULL DEFAULT 'Téléphone',
    meeting_instructions TEXT,
    CONSTRAINT fk_booking_settings_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_booking_slug (public_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    prospect_name VARCHAR(190) NOT NULL,
    prospect_email VARCHAR(190) NOT NULL,
    prospect_phone VARCHAR(40) DEFAULT '',
    subject VARCHAR(190) DEFAULT '',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    notes TEXT,
    cancel_token VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_appointment_token (cancel_token),
    KEY idx_appointment_org_time (organization_id, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO modules (module_key, name, description) VALUES
    ('ai_assistant', 'Assistant IA', "Génère des lignes de devis à partir d'un brief libre et rédige des relances de facture avec l'IA (Claude)."),
    ('appointments', 'Prise de rendez-vous en ligne', "Page publique de réservation de créneaux pour vos prospects, sans échange d'emails.");

INSERT IGNORE INTO plan_modules (plan_id, module_id)
    SELECT 2, id FROM modules WHERE module_key IN ('ai_assistant', 'appointments');
