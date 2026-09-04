-- Compte démo public : une organisation unique marquée is_demo=1, réseedée
-- périodiquement (bin/seed_demo_data.php), avec des identifiants publiés sur
-- la landing page. App\Core\Demo::isActive() court-circuite tout ce qui a un
-- effet réel hors de cette base (email, Stripe, appel payant à l'IA) pour
-- cette organisation, sans empêcher le reste (créer un client, un devis...).

ALTER TABLE organizations
    ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;
