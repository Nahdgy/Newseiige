-- ============================================================================
-- DIAGNOSTIC RAPIDE - Commande Spécifique Manquante
-- Date: 2 décembre 2025
-- Objectif: Comprendre pourquoi une commande récente n'apparaît pas
-- ============================================================================

-- INSTRUCTION : Remplacer ORDER_ID par l'ID visible dans votre capture d'écran

-- Section 1: Informations complètes de la commande
-- ============================================================================
SELECT 
    o.id,
    o.customer_id,
    o.type as order_type,
    o.status as order_status,
    o.total_amount,
    o.date_created_gmt,
    o.date_updated_gmt,
    u.display_name,
    u.user_email,
    DATEDIFF(NOW(), o.date_created_gmt) as jours_depuis_creation
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
WHERE o.id = 1033; -- REMPLACER PAR L'ID DE VOTRE COMMANDE

-- Section 2: Vérifier si la commande a des points (même erronés)
-- ============================================================================
SELECT 
    p.id as point_id,
    p.user_id,
    p.order_id,
    p.points_earned,
    p.points_available,
    p.action_type,
    p.description,
    p.created_at,
    p.is_active
FROM uz13296618_jONS.wp_newsaiige_loyalty_points p
WHERE p.order_id = 1033; -- REMPLACER PAR L'ID DE VOTRE COMMANDE

-- Section 3: Vérifier les meta de la commande
-- ============================================================================
SELECT 
    om.meta_id,
    om.meta_key,
    om.meta_value
FROM uz13296618_jONS.wp_wc_orders_meta om
WHERE om.order_id = 1033 -- REMPLACER PAR L'ID DE VOTRE COMMANDE
AND om.meta_key IN (
    '_newsaiige_loyalty_processed',
    '_customer_user',
    '_order_total',
    '_payment_method',
    '_subscription_renewal'
)
ORDER BY om.meta_key;

-- Section 4: Vérifier TOUTES les meta de cette commande
-- ============================================================================
SELECT 
    om.meta_key,
    om.meta_value
FROM uz13296618_jONS.wp_wc_orders_meta om
WHERE om.order_id = 1033 -- REMPLACER PAR L'ID DE VOTRE COMMANDE
ORDER BY om.meta_key;

-- Section 5: Test de la condition de la requête
-- ============================================================================
SELECT 
    o.id,
    o.customer_id,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt,
    -- Test de chaque condition
    CASE WHEN o.customer_id IS NOT NULL AND o.customer_id > 0 THEN '✓' ELSE '✗' END as test_customer,
    CASE WHEN o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions') THEN '✓' ELSE '✗' END as test_type,
    CASE WHEN o.status IN ('wc-completed', 'wc-processing', 'wc-active') THEN '✓' ELSE '✗' END as test_status,
    CASE WHEN o.total_amount > 0 THEN '✓' ELSE '✗' END as test_montant,
    CASE WHEN o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL 12 MONTH) THEN '✓' ELSE '✗' END as test_date,
    CASE 
        WHEN EXISTS (SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p WHERE p.order_id = o.id)
        THEN '✗ Points déjà attribués'
        ELSE '✓ PAS de points'
    END as test_not_exists
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.id = 1033; -- REMPLACER PAR L'ID DE VOTRE COMMANDE

-- Section 6: Chercher des commandes similaires qui APPARAISSENT
-- ============================================================================
SELECT 
    o.id,
    o.customer_id,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt,
    u.display_name
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
WHERE o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL 2 DAY)
AND o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
ORDER BY o.date_created_gmt DESC;

-- Section 7: Tous les statuts possibles pour cette commande (cas rare)
-- ============================================================================
SELECT DISTINCT o.status
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.id = 1033; -- REMPLACER PAR L'ID DE VOTRE COMMANDE

-- Section 8: Tous les types possibles pour cette commande
-- ============================================================================
SELECT DISTINCT o.type
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.id = 1033; -- REMPLACER PAR L'ID DE VOTRE COMMANDE

-- Section 9: Historique des points de cet utilisateur
-- ============================================================================
SELECT 
    p.id,
    p.order_id,
    p.points_earned,
    p.points_available,
    p.action_type,
    p.description,
    p.created_at,
    p.is_active
FROM uz13296618_jONS.wp_newsaiige_loyalty_points p
WHERE p.user_id = (
    SELECT customer_id FROM uz13296618_jONS.wp_wc_orders WHERE id = 1033 -- REMPLACER
)
ORDER BY p.created_at DESC;

-- ============================================================================
-- INTERPRÉTATION DES RÉSULTATS
-- ============================================================================
-- 
-- Section 1: Donne toutes les infos de base
-- Section 2: Si elle retourne des lignes, les points EXISTENT déjà (même si 0)
-- Section 3: Vérifie si la commande est marquée comme traitée
-- Section 4: Toutes les meta pour analyse complète
-- Section 5: TESTE chaque condition - identifie laquelle échoue
-- Section 6: Commandes similaires qui apparaissent (pour comparaison)
-- Section 7-8: Types et statuts exacts
-- Section 9: Historique complet de l'utilisateur
-- 
-- ============================================================================
