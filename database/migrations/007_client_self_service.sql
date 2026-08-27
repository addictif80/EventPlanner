-- Fonctionnalités RGPD en libre-service (côté utilisateur et côté client
-- final via le portail) + messagerie client <-> organisateur.

ALTER TABLE clients
    ADD COLUMN IF NOT EXISTS deletion_requested_at DATETIME DEFAULT NULL;

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
