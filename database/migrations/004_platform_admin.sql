-- Ajoute la couche d'administration plateforme (super admin, hors périmètre
-- d'une organisation) à une base déjà en multi-tenant (schema.sql +
-- migrations 002/003 déjà appliquées).

ALTER TABLE organizations
    ADD COLUMN status ENUM('active', 'suspended') NOT NULL DEFAULT 'active';

ALTER TABLE users
    ADD COLUMN is_super_admin TINYINT(1) NOT NULL DEFAULT 0;

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
    smtp_is_configured TINYINT(1) NOT NULL DEFAULT 0
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

-- Promouvoir votre compte en super admin (à adapter) :
-- UPDATE users SET is_super_admin = 1 WHERE email = 'vous@example.com';
-- ou utilisez : php bin/make_super_admin.php vous@example.com
