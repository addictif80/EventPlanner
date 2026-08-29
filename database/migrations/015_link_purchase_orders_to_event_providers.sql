-- Relie un bon de commande prestataire confirmé (purchase_orders) à la ligne
-- "Prestataires" de l'événement correspondant (event_providers), pour que le
-- coût prestataire soit automatiquement repris sur la fiche événement et
-- puisse être imputé au client lors de la création d'un devis pour cet
-- événement (voir PurchaseOrderController::syncEventProvider() et
-- QuoteController::create()). Une seule ligne event_providers par BC : la
-- contrainte UNIQUE empêche la duplication si le statut du BC est mis à
-- jour plusieurs fois.

ALTER TABLE event_providers
    ADD COLUMN IF NOT EXISTS purchase_order_id INT UNSIGNED DEFAULT NULL AFTER provider_id;

ALTER TABLE event_providers
    ADD CONSTRAINT fk_ep_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uniq_ep_po (purchase_order_id);
