-- Script SQL pour ajouter le champ date d'anniversaire
-- À exécuter dans phpMyAdmin ou via un outil de gestion de base de données

-- Ajouter le champ birthday dans la table wp_usermeta sera automatique via update_user_meta
-- Mais si vous voulez créer un index pour améliorer les performances :

-- Vérifier les meta_keys 'birthday' existants
SELECT * FROM wp_usermeta WHERE meta_key = 'birthday';

-- Optionnel : Nettoyer les anciennes données si nécessaire
-- DELETE FROM wp_usermeta WHERE meta_key = 'birthday' AND meta_value = '';

-- Note : WordPress stocke automatiquement les user_meta dans wp_usermeta
-- Aucune modification de structure n'est nécessaire
-- Le champ 'birthday' sera créé automatiquement lors de la première sauvegarde

-- Pour vérifier les anniversaires d'aujourd'hui (mois-jour uniquement)
SELECT u.ID, u.user_email, u.user_login, 
       um1.meta_value as first_name,
       um2.meta_value as birthday
FROM wp_users u
LEFT JOIN wp_usermeta um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'birthday'
WHERE um2.meta_value IS NOT NULL
AND DATE_FORMAT(STR_TO_DATE(um2.meta_value, '%Y-%m-%d'), '%m-%d') = DATE_FORMAT(NOW(), '%m-%d');

-- Pour voir tous les utilisateurs avec leur date d'anniversaire
SELECT u.ID, u.user_email, 
       um1.meta_value as first_name,
       um2.meta_value as birthday,
       um3.meta_value as last_sent
FROM wp_users u
LEFT JOIN wp_usermeta um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'birthday'
LEFT JOIN wp_usermeta um3 ON u.ID = um3.user_id AND um3.meta_key = 'birthday_coupon_last_sent'
WHERE um2.meta_value IS NOT NULL
ORDER BY um2.meta_value;

-- Pour tester manuellement l'envoi d'un bon d'anniversaire à un utilisateur spécifique
-- (remplacer USER_ID par l'ID de l'utilisateur)
-- Exécuter ensuite dans le code PHP :
-- do_action('newsaiige_daily_birthday_check');

-- Vérifier les coupons d'anniversaire créés
SELECT p.ID, p.post_title, p.post_date, 
       pm1.meta_value as discount_amount,
       pm2.meta_value as expiry_date,
       pm3.meta_value as user_id
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'coupon_amount'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'date_expires'
LEFT JOIN wp_postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'newsaiige_user_id'
WHERE p.post_type = 'shop_coupon'
AND p.post_title LIKE 'BIRTHDAY%'
ORDER BY p.post_date DESC;
