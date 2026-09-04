-- Invitations envoyées par le super admin à des prospects pour créer leur
-- propre organisation (distinct des invitations d'équipe — users.invite_token,
-- déjà en place — qui rattachent un membre à une organisation existante).
-- Voir App\Controllers\AdminOrganizationInviteController (envoi, plateforme)
-- et App\Controllers\JoinController (acceptation publique, /join/{token}).

CREATE TABLE IF NOT EXISTS organization_invites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    note VARCHAR(255) DEFAULT '',
    plan_id INT UNSIGNED DEFAULT NULL,
    invited_by INT UNSIGNED DEFAULT NULL,
    token VARCHAR(64) NOT NULL,
    status ENUM('pending', 'accepted', 'revoked') NOT NULL DEFAULT 'pending',
    accepted_organization_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_org_invite_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL,
    CONSTRAINT fk_org_invite_invited_by FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_org_invite_org FOREIGN KEY (accepted_organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_org_invite_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
