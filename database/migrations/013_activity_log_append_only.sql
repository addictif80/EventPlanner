-- Rend le journal d'activité réellement immuable au niveau de la base de
-- données (pas seulement "personne n'écrit de code qui le modifie") :
-- toute tentative de MODIFIER une ligne échoue. La instruction est unique
-- (pas de bloc BEGIN...END), pour rester compatible avec le découpage
-- naïf par ";" du Migrator.
--
-- Volontairement PAS de trigger équivalent sur DELETE : la suppression en
-- masse des lignes d'une organisation se produit légitimement quand cette
-- organisation est supprimée (ON DELETE CASCADE depuis organizations, ou
-- la suppression RGPD du compte — voir AccountController::destroy() /
-- SettingsController::destroyOrganization()) ; MySQL déclenche les
-- triggers BEFORE DELETE même pour les suppressions en cascade, donc un
-- trigger anti-DELETE casserait cette fonctionnalité déjà en place.
-- Aucun code applicatif n'émet de DELETE ciblé sur une ligne du journal.

DROP TRIGGER IF EXISTS trg_activity_log_no_update;

CREATE TRIGGER trg_activity_log_no_update BEFORE UPDATE ON activity_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_log est en ajout seul (append-only) : modification interdite.';
