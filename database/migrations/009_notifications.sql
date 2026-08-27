-- Notifications in-app (staff, clients via portail, super admin plateforme)
-- + abonnements Web Push, façon familyboard.

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

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS vapid_public_key VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS vapid_private_key VARCHAR(255) DEFAULT '';
