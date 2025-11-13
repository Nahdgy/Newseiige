<?php
/**
 * Configuration des identifiants OAuth pour les connexions sociales
 * À ajouter dans wp-config.php ou via l'administration WordPress
 */

// === IDENTIFIANTS GOOGLE ===
// Récupérés depuis Google Cloud Console
define('NEWSAIIGE_GOOGLE_CLIENT_ID', 'votre-google-client-id.apps.googleusercontent.com');
define('NEWSAIIGE_GOOGLE_CLIENT_SECRET', 'votre-google-client-secret');

// === IDENTIFIANTS FACEBOOK ===
// Récupérés depuis Facebook Developers
define('NEWSAIIGE_FACEBOOK_APP_ID', 'votre-facebook-app-id');
define('NEWSAIIGE_FACEBOOK_APP_SECRET', 'votre-facebook-app-secret');

// === URLS DE REDIRECTION ===
// Ces URLs doivent être configurées dans les consoles Google et Facebook
// Google OAuth redirect URIs :
// - https://votredomaine.com/wp-admin/admin-ajax.php?action=google_login_callback
// - https://votredomaine.com/wp-admin/admin-ajax.php?action=google_register_callback

// Facebook OAuth redirect URIs :
// - https://votredomaine.com/wp-admin/admin-ajax.php?action=facebook_login_callback
// - https://votredomaine.com/wp-admin/admin-ajax.php?action=facebook_register_callback

/**
 * Ou via l'administration WordPress (plus sécurisé) :
 * Aller dans Réglages > Général et ajouter ces champs :
 */

// Alternative via options WordPress
// update_option('newsaiige_google_client_id', 'votre-google-client-id');
// update_option('newsaiige_google_client_secret', 'votre-google-client-secret');
// update_option('newsaiige_facebook_app_id', 'votre-facebook-app-id');
// update_option('newsaiige_facebook_app_secret', 'votre-facebook-app-secret');
?>