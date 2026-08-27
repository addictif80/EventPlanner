-- EventPlanner database schema — multi-tenant (une organisation = un cabinet
-- d'organisateur d'événements, avec sa propre équipe, ses clients, son
-- catalogue, ses devis/factures...). Toutes les tables métier portent une
-- colonne organization_id filtrée systématiquement côté application.
-- Charset/engine chosen for broad compatibility with shared hosting (CyberPanel/MariaDB)

CREATE TABLE IF NOT EXISTS organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Administration plateforme (super admin, hors périmètre d'une organisation) ===

CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED PRIMARY KEY DEFAULT 1,
    platform_name VARCHAR(190) NOT NULL DEFAULT 'EventPlanner',
    smtp_host VARCHAR(190) DEFAULT '',
    smtp_port INT UNSIGNED NOT NULL DEFAULT 587,
    smtp_encryption ENUM('none', 'ssl', 'tls') NOT NULL DEFAULT 'tls',
    smtp_username VARCHAR(190) DEFAULT '',
    smtp_password VARCHAR(255) DEFAULT '',
    smtp_from_email VARCHAR(190) DEFAULT '',
    smtp_from_name VARCHAR(190) DEFAULT '',
    smtp_is_configured TINYINT(1) NOT NULL DEFAULT 0,
    stripe_secret_key VARCHAR(255) DEFAULT '',
    stripe_publishable_key VARCHAR(190) DEFAULT '',
    stripe_webhook_secret VARCHAR(190) DEFAULT '',
    vapid_public_key VARCHAR(255) DEFAULT '',
    vapid_private_key VARCHAR(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    audience ENUM('user', 'client', 'platform') NOT NULL,
    type VARCHAR(60) NOT NULL DEFAULT 'info',
    title VARCHAR(190) NOT NULL,
    message TEXT,
    link VARCHAR(255) DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read, created_at),
    INDEX idx_notif_client (client_id, is_read, created_at),
    INDEX idx_notif_platform (audience, is_read, created_at),
    CONSTRAINT fk_notif_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    is_platform TINYINT(1) NOT NULL DEFAULT 0,
    endpoint VARCHAR(500) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_push_endpoint (endpoint(255)),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_push_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_settings (id) VALUES (1);

CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(64) NOT NULL UNIQUE,
    reason VARCHAR(255) DEFAULT '',
    blocked_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_blocked_ips_user FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blocked_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    reason VARCHAR(255) DEFAULT '',
    blocked_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_blocked_emails_user FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    subject VARCHAR(190) NOT NULL,
    status ENUM('open', 'pending', 'closed') NOT NULL DEFAULT 'open',
    priority ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_tickets_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_ticket_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    is_staff_reply TINYINT(1) NOT NULL DEFAULT 0,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_msg_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_msg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(60) DEFAULT '',
    target_id INT UNSIGNED DEFAULT NULL,
    details VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_log_user FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_settings (
    organization_id INT UNSIGNED PRIMARY KEY,
    company_name VARCHAR(190) DEFAULT '',
    legal_form VARCHAR(120) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    city VARCHAR(120) DEFAULT '',
    country VARCHAR(120) DEFAULT 'France',
    phone VARCHAR(40) DEFAULT '',
    email VARCHAR(190) DEFAULT '',
    website VARCHAR(190) DEFAULT '',
    siret VARCHAR(40) DEFAULT '',
    vat_number VARCHAR(40) DEFAULT '',
    default_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
    logo_path VARCHAR(255) DEFAULT '',
    quote_prefix VARCHAR(20) NOT NULL DEFAULT 'DEV-',
    invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'FAC-',
    invoice_footer TEXT,
    stripe_secret_key VARCHAR(255) DEFAULT '',
    stripe_publishable_key VARCHAR(255) DEFAULT '',
    ics_feed_token VARCHAR(64) DEFAULT NULL,
    CONSTRAINT fk_company_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS smtp_settings (
    organization_id INT UNSIGNED PRIMARY KEY,
    host VARCHAR(190) DEFAULT '',
    port INT UNSIGNED NOT NULL DEFAULT 587,
    encryption ENUM('none', 'ssl', 'tls') NOT NULL DEFAULT 'tls',
    username VARCHAR(190) DEFAULT '',
    password VARCHAR(255) DEFAULT '',
    from_email VARCHAR(190) DEFAULT '',
    from_name VARCHAR(190) DEFAULT '',
    is_configured TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_smtp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    CONSTRAINT fk_event_types_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_event_type_name (organization_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    type ENUM('particulier', 'entreprise') NOT NULL DEFAULT 'particulier',
    first_name VARCHAR(120) DEFAULT '',
    last_name VARCHAR(120) DEFAULT '',
    company_name VARCHAR(190) DEFAULT '',
    email VARCHAR(190) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    city VARCHAR(120) DEFAULT '',
    country VARCHAR(120) DEFAULT 'France',
    tags VARCHAR(255) DEFAULT '',
    notes TEXT,
    deletion_requested_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    sender_type ENUM('client', 'staff') NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_client_msg_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_client_msg_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_client_msg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS venues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    address VARCHAR(255) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    city VARCHAR(120) DEFAULT '',
    capacity INT UNSIGNED DEFAULT NULL,
    contact_name VARCHAR(190) DEFAULT '',
    contact_phone VARCHAR(40) DEFAULT '',
    contact_email VARCHAR(190) DEFAULT '',
    notes TEXT,
    CONSTRAINT fk_venues_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    event_type_id INT UNSIGNED DEFAULT NULL,
    venue_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    location VARCHAR(255) DEFAULT '',
    guests_count INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    budget DECIMAL(12,2) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(120) DEFAULT '',
    contact_name VARCHAR(190) DEFAULT '',
    email VARCHAR(190) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    notes TEXT,
    rating TINYINT UNSIGNED DEFAULT NULL,
    CONSTRAINT fk_providers_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    provider_id INT UNSIGNED NOT NULL,
    cost DECIMAL(12,2) DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    CONSTRAINT fk_ep_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit VARCHAR(40) DEFAULT 'unité',
    category VARCHAR(120) DEFAULT '',
    CONSTRAINT fk_products_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    quote_number VARCHAR(40) NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'sent', 'accepted', 'refused', 'expired') NOT NULL DEFAULT 'draft',
    issue_date DATE NOT NULL,
    valid_until DATE DEFAULT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quotes_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_quotes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_quotes_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_quote_number (organization_id, quote_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    quote_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_qi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_qi_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    CONSTRAINT fk_qi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(40) NOT NULL,
    quote_id INT UNSIGNED DEFAULT NULL,
    client_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    type ENUM('deposit', 'final', 'standalone') NOT NULL DEFAULT 'standalone',
    status ENUM('draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft',
    issue_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    is_recurring TINYINT(1) NOT NULL DEFAULT 0,
    recurrence_interval ENUM('monthly', 'quarterly', 'yearly') DEFAULT NULL,
    recurrence_next_date DATE DEFAULT NULL,
    recurrence_parent_id INT UNSIGNED DEFAULT NULL,
    currency VARCHAR(10) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    CONSTRAINT fk_invoices_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_invoice_number (organization_id, invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_ii_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    method ENUM('virement', 'cb', 'especes', 'cheque', 'autre') NOT NULL DEFAULT 'virement',
    reference VARCHAR(120) DEFAULT '',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT,
    due_date DATE DEFAULT NULL,
    assigned_to INT UNSIGNED DEFAULT NULL,
    status ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
    priority ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_user FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(60) DEFAULT '',
    entity_id INT UNSIGNED DEFAULT NULL,
    details VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Invités, tables/placement, billetterie ===

CREATE TABLE IF NOT EXISTS event_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 8,
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_tables_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_tables_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
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
    CONSTRAINT fk_guests_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_guests_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_guests_table FOREIGN KEY (table_id) REFERENCES event_tables(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_rsvp_token (rsvp_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    quantity_available INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_ticketcat_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticketcat_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    ticket_category_id INT UNSIGNED NOT NULL,
    guest_id INT UNSIGNED DEFAULT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    holder_name VARCHAR(190) DEFAULT '',
    holder_email VARCHAR(190) DEFAULT '',
    status ENUM('valid', 'checked_in', 'cancelled') NOT NULL DEFAULT 'valid',
    checked_in_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_category FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Contrats, signature électronique, documents ===

CREATE TABLE IF NOT EXISTS contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
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
    CONSTRAINT fk_contracts_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_contracts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_contracts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_sign_token (sign_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    content MEDIUMTEXT,
    CONSTRAINT fk_ct_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    category VARCHAR(80) DEFAULT '',
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Fournisseurs : bons de commande, stock matériel ===

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    po_number VARCHAR(40) NOT NULL,
    provider_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'sent', 'confirmed', 'cancelled') NOT NULL DEFAULT 'draft',
    issue_date DATE NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_po_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_po_number (organization_id, po_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    purchase_order_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_poi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(120) DEFAULT '',
    total_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT,
    CONSTRAINT fk_equipment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment_bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    equipment_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_eb_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_eb_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    CONSTRAINT fk_eb_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Avoirs ===

CREATE TABLE IF NOT EXISTS credit_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    credit_note_number VARCHAR(40) NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cn_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_cn_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_cn_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_credit_note_number (organization_id, credit_note_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Jour-J : feuille de route, contacts d'urgence, incidents ===

CREATE TABLE IF NOT EXISTS run_sheet_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    time_slot TIME DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    responsible VARCHAR(190) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    position INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_rsi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_rsi_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    role VARCHAR(120) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_ec_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ec_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    reported_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_incidents_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidents_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidents_user FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Communication : notes internes, modèles d'emails, sondages ===

CREATE TABLE IF NOT EXISTS event_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    template_key VARCHAR(60) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    intro TEXT,
    CONSTRAINT fk_templates_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_template_key (organization_id, template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS satisfaction_surveys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    rating TINYINT UNSIGNED DEFAULT NULL,
    comments TEXT,
    sent_at DATETIME DEFAULT NULL,
    submitted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_survey_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Portail client ===

CREATE TABLE IF NOT EXISTS client_portal_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_portal_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_portal_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === Abonnements payants (offres définies par le super admin, facturées via
-- Stripe Subscriptions sur le compte Stripe de la PLATEFORME — distinct du
-- compte Stripe propre à chaque organisation, utilisé lui pour encaisser le
-- paiement de SES factures clients, voir company_settings.stripe_secret_key) ===

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

-- Offre gratuite assignée automatiquement à l'inscription : la gestion
-- d'événements de base (clients, événements, devis, factures) reste toujours
-- accessible quel que soit le plan ; seuls les modules listés ci-dessus sont
-- soumis à abonnement. Le super admin crée les offres payantes (Paramètres >
-- Offres) qui débloquent des modules et/ou davantage de membres.
INSERT IGNORE INTO plans (id, name, description, monthly_price, max_members, is_default_signup, is_active, sort_order) VALUES
    (1, 'Découverte', 'Offre gratuite par défaut à l\'inscription : fonctionnalités de base, 3 membres maximum, sans module additionnel.', 0, 3, 1, 1, 0);

-- Pages de contenu éditables et éléments des menus haut/pied de page de la
-- landing page publique, gérés par le super admin.
CREATE TABLE IF NOT EXISTS site_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    content MEDIUMTEXT,
    meta_description VARCHAR(255) DEFAULT '',
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location ENUM('header', 'footer') NOT NULL,
    label VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO site_menu_items (id, location, label, url, sort_order) VALUES
    (1, 'header', 'Fonctionnalités', '#fonctionnalites', 10),
    (2, 'header', 'Tarifs', '#tarifs', 20),
    (3, 'footer', 'Connexion', '/login', 10),
    (4, 'footer', 'Inscription', '/register', 20);
