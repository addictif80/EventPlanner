-- @manual-only : NE PAS exécuter automatiquement (App\Core\Migrator ignore
-- ce fichier). Bascule d'une installation EventPlanner mono-tenant existante
-- vers le multi-tenant : crée une organisation "Migration" qui récupère
-- toutes les données existantes, puis ajoute organization_id partout. C'est
-- une opération destructive et non rejouable (elle supprime des clés
-- primaires, restructure des tables et déplace des données) : elle doit
-- rester une action manuelle et délibérée, jamais automatique.
--
-- ATTENTION : si votre base ne contient pas encore de données réelles
-- (installation de test/développement), il est bien plus simple de repartir
-- d'une base vide et de réimporter database/schema.sql. Cette migration n'est
-- utile que pour préserver des données de production déjà saisies.
--
-- À exécuter en une seule fois, dans l'ordre, sur une base ayant le schéma
-- mono-tenant précédent (schema.sql + migrations/002_advanced_features.sql).

CREATE TABLE IF NOT EXISTS organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO organizations (name) VALUES ('Mon organisation');
SET @org_id = LAST_INSERT_ID();

-- Ajoute organization_id (nullable dans un premier temps) sur chaque table,
-- la remplit avec l'organisation de migration, puis la rend NOT NULL.

ALTER TABLE users ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE users SET organization_id = @org_id;
ALTER TABLE users MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_users_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- company_settings / smtp_settings passent d'un singleton id=1 à une clé
-- primaire organization_id.
ALTER TABLE company_settings ADD COLUMN organization_id INT UNSIGNED NULL;
UPDATE company_settings SET organization_id = @org_id WHERE id = 1;
DELETE FROM company_settings WHERE id != 1 OR id IS NULL;
ALTER TABLE company_settings DROP PRIMARY KEY, DROP COLUMN id,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD PRIMARY KEY (organization_id),
    ADD CONSTRAINT fk_company_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE smtp_settings ADD COLUMN organization_id INT UNSIGNED NULL;
UPDATE smtp_settings SET organization_id = @org_id WHERE id = 1;
DELETE FROM smtp_settings WHERE id != 1 OR id IS NULL;
ALTER TABLE smtp_settings DROP PRIMARY KEY, DROP COLUMN id,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD PRIMARY KEY (organization_id),
    ADD CONSTRAINT fk_smtp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE event_types ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE event_types SET organization_id = @org_id;
ALTER TABLE event_types DROP INDEX name,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_event_types_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_event_type_name (organization_id, name);

ALTER TABLE email_templates ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE email_templates SET organization_id = @org_id;
ALTER TABLE email_templates DROP INDEX template_key,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_templates_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_template_key (organization_id, template_key);

-- Tables simples : ajout + backfill + NOT NULL + FK.
-- (Numérotation devis/factures/BC/avoirs : la contrainte UNIQUE globale
-- devient composite (organization_id, numéro) puisqu'il n'existe qu'une
-- organisation ici, l'ancienne unicité globale reste satisfaite.)

ALTER TABLE clients ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE clients SET organization_id = @org_id;
ALTER TABLE clients MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_clients_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE venues ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE venues SET organization_id = @org_id;
ALTER TABLE venues MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_venues_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE events ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE events SET organization_id = @org_id;
ALTER TABLE events MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_events_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE providers ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE providers SET organization_id = @org_id;
ALTER TABLE providers MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_providers_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE event_providers ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE event_providers SET organization_id = @org_id;
ALTER TABLE event_providers MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_ep_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE products ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE products SET organization_id = @org_id;
ALTER TABLE products MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_products_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE quotes ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE quotes SET organization_id = @org_id;
ALTER TABLE quotes DROP INDEX quote_number,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_quotes_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_quote_number (organization_id, quote_number);

ALTER TABLE quote_items ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE quote_items SET organization_id = @org_id;
ALTER TABLE quote_items MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_qi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE invoices ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE invoices SET organization_id = @org_id;
ALTER TABLE invoices DROP INDEX invoice_number,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_invoices_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_invoice_number (organization_id, invoice_number);

ALTER TABLE invoice_items ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE invoice_items SET organization_id = @org_id;
ALTER TABLE invoice_items MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_ii_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE payments ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE payments SET organization_id = @org_id;
ALTER TABLE payments MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_payments_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE tasks ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE tasks SET organization_id = @org_id;
ALTER TABLE tasks MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_tasks_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE activity_log ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE activity_log SET organization_id = @org_id;
ALTER TABLE activity_log MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_log_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE event_tables ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE event_tables SET organization_id = @org_id;
ALTER TABLE event_tables MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_tables_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE guests ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE guests SET organization_id = @org_id;
ALTER TABLE guests MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_guests_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE ticket_categories ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE ticket_categories SET organization_id = @org_id;
ALTER TABLE ticket_categories MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_ticketcat_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE tickets ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE tickets SET organization_id = @org_id;
ALTER TABLE tickets MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_tickets_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE contracts ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE contracts SET organization_id = @org_id;
ALTER TABLE contracts MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_contracts_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE contract_templates ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE contract_templates SET organization_id = @org_id;
ALTER TABLE contract_templates MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_ct_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE documents ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE documents SET organization_id = @org_id;
ALTER TABLE documents MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_documents_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE purchase_orders ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE purchase_orders SET organization_id = @org_id;
ALTER TABLE purchase_orders DROP INDEX po_number,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_po_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_po_number (organization_id, po_number);

ALTER TABLE purchase_order_items ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE purchase_order_items SET organization_id = @org_id;
ALTER TABLE purchase_order_items MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_poi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE equipment ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE equipment SET organization_id = @org_id;
ALTER TABLE equipment MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_equipment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE equipment_bookings ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE equipment_bookings SET organization_id = @org_id;
ALTER TABLE equipment_bookings MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_eb_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE credit_notes ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE credit_notes SET organization_id = @org_id;
ALTER TABLE credit_notes DROP INDEX credit_note_number,
    MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_cn_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_credit_note_number (organization_id, credit_note_number);

ALTER TABLE run_sheet_items ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE run_sheet_items SET organization_id = @org_id;
ALTER TABLE run_sheet_items MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_rsi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE emergency_contacts ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE emergency_contacts SET organization_id = @org_id;
ALTER TABLE emergency_contacts MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_ec_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE incidents ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE incidents SET organization_id = @org_id;
ALTER TABLE incidents MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_incidents_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE event_notes ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE event_notes SET organization_id = @org_id;
ALTER TABLE event_notes MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_notes_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE satisfaction_surveys ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE satisfaction_surveys SET organization_id = @org_id;
ALTER TABLE satisfaction_surveys MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_survey_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE client_portal_tokens ADD COLUMN organization_id INT UNSIGNED NULL AFTER id;
UPDATE client_portal_tokens SET organization_id = @org_id;
ALTER TABLE client_portal_tokens MODIFY organization_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_portal_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;
