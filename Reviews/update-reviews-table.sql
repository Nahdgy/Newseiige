-- Script SQL pour mettre à jour la table newsaiige_reviews
-- Ajout des colonnes service_id et service_name pour lier les avis aux prestations

-- Vérifier si les colonnes existent avant de les ajouter
-- Exécuter ces commandes dans phpMyAdmin

-- Ajouter la colonne service_id (ID de la prestation/produit)
ALTER TABLE `wp_newsaiige_reviews` 
ADD COLUMN `service_id` INT(11) DEFAULT 0 AFTER `comment`;

-- Ajouter la colonne service_name (nom de la prestation)
ALTER TABLE `wp_newsaiige_reviews` 
ADD COLUMN `service_name` VARCHAR(255) DEFAULT '' AFTER `service_id`;

-- Ajouter un index pour améliorer les performances des requêtes
ALTER TABLE `wp_newsaiige_reviews` 
ADD INDEX `idx_service_id` (`service_id`);

ALTER TABLE `wp_newsaiige_reviews` 
ADD INDEX `idx_status_service` (`status`, `service_id`);

-- Vérifier la structure de la table mise à jour
DESCRIBE `wp_newsaiige_reviews`;

-- Compter les avis par prestation
SELECT service_name, COUNT(*) as total_reviews, AVG(rating) as avg_rating 
FROM `wp_newsaiige_reviews` 
WHERE status = 'approved' AND service_id > 0
GROUP BY service_id, service_name
ORDER BY total_reviews DESC;
