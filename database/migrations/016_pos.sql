-- Caisse virtuelle (point de vente) utilisable sur place le jour d'un
-- événement : ouverture/fermeture de session de caisse avec fond de caisse,
-- encaissements (espèces/carte/autre) décrémentant le stock du catalogue,
-- mouvements de caisse manuels (appro/retrait), et un ticket de caisse
-- accessible en autonomie par le client (lien email ou QR code) — voir
-- App\Controllers\PosController et PosReceiptController.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS stock_quantity INT DEFAULT NULL COMMENT 'NULL = stock non suivi (prestation/service)';

CREATE TABLE IF NOT EXISTS pos_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    opening_float DECIMAL(12,2) NOT NULL DEFAULT 0,
    counted_cash DECIMAL(12,2) DEFAULT NULL,
    cash_difference DECIMAL(12,2) DEFAULT NULL,
    opened_by INT UNSIGNED DEFAULT NULL,
    closed_by INT UNSIGNED DEFAULT NULL,
    notes TEXT,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_pos_session_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_session_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_pos_session_opened_by FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pos_session_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pos_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    pos_session_id INT UNSIGNED NOT NULL,
    sale_number VARCHAR(40) NOT NULL,
    client_id INT UNSIGNED DEFAULT NULL,
    buyer_name VARCHAR(190) DEFAULT '',
    buyer_email VARCHAR(190) DEFAULT '',
    payment_method ENUM('cash', 'card', 'other') NOT NULL DEFAULT 'cash',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('completed', 'refunded') NOT NULL DEFAULT 'completed',
    access_token VARCHAR(64) NOT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_sale_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_sale_session FOREIGN KEY (pos_session_id) REFERENCES pos_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_sale_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_pos_sale_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_pos_sale_number (organization_id, sale_number),
    UNIQUE KEY uniq_pos_sale_token (access_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pos_sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    pos_sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_pos_item_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_item_sale FOREIGN KEY (pos_sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pos_cash_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    pos_session_id INT UNSIGNED NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(190) DEFAULT '',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_move_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_move_session FOREIGN KEY (pos_session_id) REFERENCES pos_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_move_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO modules (module_key, name, description) VALUES
    ('pos', 'Caisse (point de vente)', "Caisse virtuelle sur place : encaissements, stock, moyens de paiement et tickets clients par email/QR code.");

INSERT IGNORE INTO plan_modules (plan_id, module_id)
    SELECT 2, id FROM modules WHERE module_key = 'pos';
