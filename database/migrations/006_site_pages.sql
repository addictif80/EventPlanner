-- Pages de contenu éditables et éléments des menus haut/pied de page du site
-- public (landing page), gérés par le super admin.

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
