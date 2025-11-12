-- ====================================
-- Base de données du système de fidélité Newsaiige
-- ====================================

-- Table des points de fidélité
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_points (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    points_earned int(11) NOT NULL DEFAULT 0,
    points_used int(11) NOT NULL DEFAULT 0,
    points_available int(11) NOT NULL DEFAULT 0,
    order_id int(11) NULL,
    action_type varchar(50) NOT NULL,
    description text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    expires_at datetime NULL,
    is_active tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_points_user_id (user_id),
    KEY idx_points_order_id (order_id),
    KEY idx_points_expires_at (expires_at),
    KEY idx_points_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paliers de fidélité
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_tiers (
    id int(11) NOT NULL AUTO_INCREMENT,
    tier_name varchar(100) NOT NULL,
    tier_slug varchar(100) NOT NULL,
    points_required int(11) NOT NULL,
    tier_order int(11) NOT NULL,
    benefits text,
    birthday_bonus_percentage int(11) DEFAULT 0,
    email_template text,
    is_active tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_tier_slug (tier_slug),
    KEY idx_tier_order (tier_order),
    KEY idx_points_required (points_required),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des bons d'achat
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_vouchers (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    voucher_code varchar(50) NOT NULL,
    voucher_type varchar(50) NOT NULL,
    amount decimal(10,2) NOT NULL,
    percentage int(11) NULL,
    points_cost int(11) NOT NULL,
    is_used tinyint(1) DEFAULT 0,
    used_order_id int(11) NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    expires_at datetime NOT NULL,
    used_at datetime NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_voucher_code (voucher_code),
    KEY idx_voucher_user_id (user_id),
    KEY idx_voucher_type (voucher_type),
    KEY idx_voucher_is_used (is_used),
    KEY idx_voucher_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paliers utilisateurs
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_user_tiers (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    tier_id int(11) NOT NULL,
    achieved_at datetime DEFAULT CURRENT_TIMESTAMP,
    is_current tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_user_tiers_user_id (user_id),
    KEY idx_user_tiers_tier_id (tier_id),
    KEY idx_user_tiers_is_current (is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paramètres du système
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_settings (
    id int(11) NOT NULL AUTO_INCREMENT,
    setting_key varchar(100) NOT NULL,
    setting_value text NOT NULL,
    setting_type varchar(50) DEFAULT 'string',
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des règles de conversion points → euros (configuration manuelle)
CREATE TABLE IF NOT EXISTS wp_newsaiige_loyalty_conversion_rules (
    id int(11) NOT NULL AUTO_INCREMENT,
    points_required int(11) NOT NULL,
    voucher_amount decimal(10,2) NOT NULL,
    rule_order int(11) NOT NULL DEFAULT 0,
    is_active tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_points_required (points_required),
    KEY idx_rule_order (rule_order),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Insertion des données par défaut
-- ====================================

-- Paramètres par défaut du système
INSERT INTO wp_newsaiige_loyalty_settings (setting_key, setting_value, setting_type) VALUES
('points_per_euro', '1', 'integer'),
('points_expiry_days', '183', 'integer'),
('voucher_expiry_days', '183', 'integer'),
('min_points_conversion', '50', 'integer'),
('subscription_required', '1', 'boolean'),
('subscription_category_slug', 'soins', 'string'),
('email_notifications_enabled', '1', 'boolean'),
('system_enabled', '1', 'boolean'),
('birthday_check_enabled', '1', 'boolean'),
('use_conversion_rules', '1', 'boolean')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;

-- Règles de conversion par défaut (exemples configurables)
INSERT INTO wp_newsaiige_loyalty_conversion_rules (points_required, voucher_amount, rule_order) VALUES
(50, 1.00, 1),
(100, 2.50, 2),
(200, 6.00, 3),
(500, 18.00, 4),
(700, 28.00, 5),
(1000, 45.00, 6)
ON DUPLICATE KEY UPDATE 
voucher_amount = VALUES(voucher_amount),
rule_order = VALUES(rule_order),
updated_at = CURRENT_TIMESTAMP;

-- Paliers de fidélité par défaut
INSERT INTO wp_newsaiige_loyalty_tiers (tier_name, tier_slug, points_required, tier_order, benefits, birthday_bonus_percentage) VALUES
('Bronze', 'bronze', 700, 1, 'Bienvenue dans notre programme de fidélité ! Profitez d\'un bon d\'achat de 5€ offert applicable sur tout des soins.', 5),
('Argent', 'silver', 1300, 2, 'Bon d\'achat de 10€ offert applicable sur tout des soins', 10),
('Or', 'gold', 1900, 3, 'Bon d\'achat de 20€ offert applicable sur tout des soins', 20),
('Platine', 'platinum', 2500, 4, 'Bon d\'achat de 65€ offert applicable sur tout des soins', 65)
ON DUPLICATE KEY UPDATE 
tier_name = VALUES(tier_name),
benefits = VALUES(benefits),
birthday_bonus_percentage = VALUES(birthday_bonus_percentage);

-- ====================================
-- Vues utiles pour les statistiques
-- ====================================

-- Vue pour les statistiques des utilisateurs
CREATE OR REPLACE VIEW wp_newsaiige_loyalty_user_stats AS
SELECT 
    u.ID as user_id,
    u.user_email,
    u.display_name,
    COALESCE(SUM(CASE WHEN p.points_earned > 0 THEN p.points_earned ELSE 0 END), 0) as lifetime_points,
    COALESCE(SUM(CASE WHEN p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END), 0) as current_points,
    COALESCE(SUM(p.points_used), 0) as used_points,
    t.tier_name as current_tier,
    t.tier_slug as tier_slug,
    COUNT(DISTINCT v.id) as total_vouchers,
    COUNT(DISTINCT CASE WHEN v.is_used = 0 AND v.expires_at > NOW() THEN v.id END) as active_vouchers,
    ut.achieved_at as tier_achieved_at
FROM wp_users u
LEFT JOIN wp_newsaiige_loyalty_points p ON u.ID = p.user_id
LEFT JOIN wp_newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id AND ut.is_current = 1
LEFT JOIN wp_newsaiige_loyalty_tiers t ON ut.tier_id = t.id
LEFT JOIN wp_newsaiige_loyalty_vouchers v ON u.ID = v.user_id
GROUP BY u.ID, u.user_email, u.display_name, t.tier_name, t.tier_slug, ut.achieved_at;

-- Vue pour les statistiques des paliers
CREATE OR REPLACE VIEW wp_newsaiige_loyalty_tier_stats AS
SELECT 
    t.id,
    t.tier_name,
    t.tier_slug,
    t.points_required,
    t.benefits,
    t.birthday_bonus_percentage,
    COUNT(DISTINCT ut.user_id) as user_count,
    AVG(stats.lifetime_points) as avg_user_points
FROM wp_newsaiige_loyalty_tiers t
LEFT JOIN wp_newsaiige_loyalty_user_tiers ut ON t.id = ut.tier_id AND ut.is_current = 1
LEFT JOIN wp_newsaiige_loyalty_user_stats stats ON ut.user_id = stats.user_id
WHERE t.is_active = 1
GROUP BY t.id, t.tier_name, t.tier_slug, t.points_required, t.benefits, t.birthday_bonus_percentage
ORDER BY t.tier_order;

-- ====================================
-- Procédures stockées utiles
-- ====================================

DELIMITER //

-- Procédure pour nettoyer les points expirés
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredLoyaltyData()
BEGIN
    -- Désactiver les points expirés
    UPDATE wp_newsaiige_loyalty_points 
    SET is_active = 0 
    WHERE expires_at IS NOT NULL 
    AND expires_at < NOW() 
    AND is_active = 1;
    
    -- Marquer les bons d'achat expirés comme inutilisables
    UPDATE wp_newsaiige_loyalty_vouchers 
    SET is_used = -1 
    WHERE expires_at < NOW() 
    AND is_used = 0;
    
    SELECT 'Nettoyage terminé' as message;
END //

-- Procédure pour calculer les points d'un utilisateur
CREATE PROCEDURE IF NOT EXISTS CalculateUserPoints(IN user_id_param INT)
BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN points_earned > 0 THEN points_earned ELSE 0 END), 0) as lifetime_points,
        COALESCE(SUM(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) THEN points_available ELSE 0 END), 0) as available_points,
        COALESCE(SUM(points_used), 0) as used_points
    FROM wp_newsaiige_loyalty_points 
    WHERE user_id = user_id_param;
END //

DELIMITER ;

-- ====================================
-- Index supplémentaires pour les performances
-- ====================================

-- Index composites pour optimiser les requêtes fréquentes (éviter les doublons avec ceux déjà créés)
CREATE INDEX idx_points_user_active_expiry ON wp_newsaiige_loyalty_points(user_id, is_active, expires_at);
CREATE INDEX idx_points_user_created ON wp_newsaiige_loyalty_points(user_id, created_at);
CREATE INDEX idx_vouchers_user_status_expiry ON wp_newsaiige_loyalty_vouchers(user_id, is_used, expires_at);
CREATE INDEX idx_user_tiers_user_current ON wp_newsaiige_loyalty_user_tiers(user_id, is_current);

-- ====================================
-- Contraintes de clé étrangère (ajoutées après la création des tables)
-- ====================================

-- Ajouter la contrainte de clé étrangère pour les paliers utilisateurs
ALTER TABLE wp_newsaiige_loyalty_user_tiers 
ADD CONSTRAINT fk_user_tiers_tier_id 
FOREIGN KEY (tier_id) REFERENCES wp_newsaiige_loyalty_tiers(id) ON DELETE CASCADE;

-- ====================================
-- Triggers pour maintenir la cohérence (optionnels - peuvent être supprimés si problèmes)
-- ====================================

-- Note: Si vous rencontrez des erreurs avec les triggers, vous pouvez supprimer cette section
-- Le système fonctionnera sans les triggers, la logique étant gérée par PHP

DELIMITER //

-- Trigger pour mettre à jour automatiquement les points disponibles
DROP TRIGGER IF EXISTS update_available_points_after_use//

CREATE TRIGGER update_available_points_after_use
BEFORE UPDATE ON wp_newsaiige_loyalty_points
FOR EACH ROW
BEGIN
    -- Si les points utilisés ont changé, ajuster les points disponibles
    IF NEW.points_used != OLD.points_used THEN
        SET NEW.points_available = NEW.points_earned - NEW.points_used;
    END IF;
END//

DELIMITER ;

-- ====================================
-- Fin du script
-- ====================================

-- Afficher un résumé des tables créées
SELECT 
    'Tables du système de fidélité créées avec succès!' as message,
    (SELECT COUNT(*) FROM wp_newsaiige_loyalty_tiers) as paliers_crees,
    (SELECT COUNT(*) FROM wp_newsaiige_loyalty_settings) as parametres_crees;