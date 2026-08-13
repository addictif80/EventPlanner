-- EventPlanner — extension complète : invités/RSVP, billetterie, contrats/signature,
-- documents, bons de commande, stock matériel, avoirs, jour-J, portail client,
-- notes internes, modèles d'emails, sondages, journal d'activité, intégrations.
-- Peut être importé sur une base déjà initialisée avec schema.sql / 001.

CREATE TABLE IF NOT EXISTS event_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 8,
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_tables_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED DEFAULT NULL,
    first_name VARCHAR(120) DEFAULT '',
    last_name VARCHAR(120) DEFAULT '',
    email VARCHAR(190) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    rsvp_status ENUM('pending', 'confirmed', 'declined') NOT NULL DEFAULT 'pending',
    plus_ones INT UNSIGNED NOT NULL DEFAULT 0,
    dietary_notes VARCHAR(255) DEFAULT '',
    notes TEXT,
    rsvp_token VARCHAR(64) DEFAULT NULL,
    invited_at DATETIME DEFAULT NULL,
    responded_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guests_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_guests_table FOREIGN KEY (table_id) REFERENCES event_tables(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_rsvp_token (rsvp_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    quantity_available INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_ticketcat_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_category_id INT UNSIGNED NOT NULL,
    guest_id INT UNSIGNED DEFAULT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    holder_name VARCHAR(190) DEFAULT '',
    holder_email VARCHAR(190) DEFAULT '',
    status ENUM('valid', 'checked_in', 'cancelled') NOT NULL DEFAULT 'valid',
    checked_in_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_category FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    quote_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    content MEDIUMTEXT,
    status ENUM('draft', 'sent', 'signed', 'cancelled') NOT NULL DEFAULT 'draft',
    sign_token VARCHAR(64) DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    signed_at DATETIME DEFAULT NULL,
    signer_name VARCHAR(190) DEFAULT '',
    signature_data MEDIUMTEXT,
    signer_ip VARCHAR(64) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contracts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_contracts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_sign_token (sign_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    content MEDIUMTEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED DEFAULT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    category VARCHAR(80) DEFAULT '',
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(40) NOT NULL UNIQUE,
    provider_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'sent', 'confirmed', 'cancelled') NOT NULL DEFAULT 'draft',
    issue_date DATE NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_po_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(120) DEFAULT '',
    total_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment_bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_eb_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    CONSTRAINT fk_eb_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS credit_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    credit_note_number VARCHAR(40) NOT NULL UNIQUE,
    invoice_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cn_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_cn_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS recurrence_interval ENUM('monthly', 'quarterly', 'yearly') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS recurrence_next_date DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS recurrence_parent_id INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS run_sheet_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    time_slot TIME DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    responsible VARCHAR(190) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    position INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_rsi_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    role VARCHAR(120) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_ec_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    reported_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_incidents_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidents_user FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED DEFAULT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(60) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    intro TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO email_templates (template_key, subject, intro) VALUES
    ('quote', 'Votre devis {number}', 'Veuillez trouver ci-dessous le récapitulatif de votre devis.'),
    ('invoice', 'Votre facture {number}', 'Veuillez trouver ci-dessous votre facture.'),
    ('reminder', 'Rappel — facture {number} en attente de paiement', 'Nous vous rappelons que la facture suivante reste impayée à ce jour.');

CREATE TABLE IF NOT EXISTS satisfaction_surveys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    rating TINYINT UNSIGNED DEFAULT NULL,
    comments TEXT,
    sent_at DATETIME DEFAULT NULL,
    submitted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_survey_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_portal_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_portal_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE company_settings
    ADD COLUMN IF NOT EXISTS stripe_secret_key VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS stripe_publishable_key VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS ics_feed_token VARCHAR(64) DEFAULT NULL;
