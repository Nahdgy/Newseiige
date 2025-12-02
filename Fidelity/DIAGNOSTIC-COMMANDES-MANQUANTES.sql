-- ============================================================================
-- DIAGNOSTIC COMPLET - Commandes Sans Points Attribués
-- Date: 2 décembre 2025
-- Objectif: Identifier TOUTES les commandes/abonnements sans points
-- ============================================================================

-- Section 1: TOUTES les commandes sans points (sans limite de date)
-- ============================================================================
SELECT 
    o.id as order_id,
    o.customer_id,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt as date_created,
    u.display_name,
    u.user_email,
    FLOOR(o.total_amount * 1) as points_estimes,
    DATEDIFF(NOW(), o.date_created_gmt) as jours_depuis_commande
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
ORDER BY o.date_created_gmt DESC;

-- Section 2: Statistiques par type de commande
-- ============================================================================
SELECT 
    o.type,
    COUNT(*) as nombre_commandes_sans_points,
    SUM(o.total_amount) as montant_total,
    SUM(FLOOR(o.total_amount * 1)) as points_totaux_manquants,
    MIN(o.date_created_gmt) as plus_ancienne,
    MAX(o.date_created_gmt) as plus_recente
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
GROUP BY o.type
ORDER BY nombre_commandes_sans_points DESC;

-- Section 3: Statistiques par statut
-- ============================================================================
SELECT 
    o.status,
    COUNT(*) as nombre_commandes_sans_points,
    SUM(o.total_amount) as montant_total,
    SUM(FLOOR(o.total_amount * 1)) as points_totaux_manquants
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
GROUP BY o.status
ORDER BY nombre_commandes_sans_points DESC;

-- Section 4: Top 20 utilisateurs avec le plus de commandes sans points
-- ============================================================================
SELECT 
    o.customer_id,
    u.display_name,
    u.user_email,
    COUNT(*) as nombre_commandes_sans_points,
    SUM(o.total_amount) as montant_total,
    SUM(FLOOR(o.total_amount * 1)) as points_totaux_manquants,
    GROUP_CONCAT(CONCAT('#', o.id) ORDER BY o.date_created_gmt DESC SEPARATOR ', ') as order_ids
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
GROUP BY o.customer_id, u.display_name, u.user_email
ORDER BY nombre_commandes_sans_points DESC, montant_total DESC
LIMIT 20;

-- Section 5: Commandes sans points par mois (derniers 12 mois)
-- ============================================================================
SELECT 
    DATE_FORMAT(o.date_created_gmt, '%Y-%m') as mois,
    COUNT(*) as nombre_commandes_sans_points,
    SUM(o.total_amount) as montant_total,
    SUM(FLOOR(o.total_amount * 1)) as points_totaux_manquants
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
GROUP BY DATE_FORMAT(o.date_created_gmt, '%Y-%m')
ORDER BY mois DESC;

-- Section 6: Vérification spécifique - Commande visible dans la capture
-- ============================================================================
-- Remplacer ORDER_ID par l'ID de la commande de votre capture d'écran
SELECT 
    o.id as order_id,
    o.customer_id,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt,
    u.display_name,
    u.user_email,
    CASE 
        WHEN EXISTS (SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p WHERE p.order_id = o.id)
        THEN 'Points déjà attribués'
        ELSE 'PAS de points attribués'
    END as statut_points,
    FLOOR(o.total_amount * 1) as points_estimes
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
WHERE o.id = 1033; -- REMPLACER par l'ID de votre commande

-- Section 7: Recherche des commandes avec meta _newsaiige_loyalty_processed
-- ============================================================================
SELECT 
    o.id as order_id,
    o.customer_id,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt,
    om.meta_value as loyalty_processed_timestamp,
    FROM_UNIXTIME(om.meta_value) as loyalty_processed_date,
    CASE 
        WHEN EXISTS (SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p WHERE p.order_id = o.id)
        THEN 'Points attribués ✓'
        ELSE 'ERREUR: Marquée traitée mais PAS de points ✗'
    END as verification
FROM uz13296618_jONS.wp_wc_orders o
INNER JOIN uz13296618_jONS.wp_wc_orders_meta om ON o.id = om.order_id
WHERE om.meta_key = '_newsaiige_loyalty_processed'
ORDER BY o.date_created_gmt DESC
LIMIT 50;

-- Section 8: Commandes marquées comme traitées SANS points
-- ============================================================================
SELECT 
    o.id as order_id,
    o.customer_id,
    u.display_name,
    u.user_email,
    o.type,
    o.status,
    o.total_amount,
    o.date_created_gmt,
    FROM_UNIXTIME(om.meta_value) as date_traitement
FROM uz13296618_jONS.wp_wc_orders o
LEFT JOIN uz13296618_jONS.wp_users u ON o.customer_id = u.ID
INNER JOIN uz13296618_jONS.wp_wc_orders_meta om ON o.id = om.order_id
WHERE om.meta_key = '_newsaiige_loyalty_processed'
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
)
ORDER BY o.date_created_gmt DESC;

-- Section 9: RÉSUMÉ GLOBAL
-- ============================================================================
SELECT 
    COUNT(*) as total_commandes_sans_points,
    SUM(o.total_amount) as montant_total,
    SUM(FLOOR(o.total_amount * 1)) as points_totaux_manquants,
    MIN(o.date_created_gmt) as commande_plus_ancienne,
    MAX(o.date_created_gmt) as commande_plus_recente,
    COUNT(DISTINCT o.customer_id) as nombre_utilisateurs_affectes
FROM uz13296618_jONS.wp_wc_orders o
WHERE o.customer_id IS NOT NULL
AND o.customer_id > 0
AND o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-processing', 'wc-active')
AND o.total_amount > 0
AND NOT EXISTS (
    SELECT 1 FROM uz13296618_jONS.wp_newsaiige_loyalty_points p 
    WHERE p.order_id = o.id
);
