<?php
/**
 * Gestionnaire d'ordre de chargement des plugins NewSaiige
 * Évite les conflits entre gift-cards et loyalty
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Variable globale pour éviter les doubles chargements
global $newsaiige_plugins_loaded;
if (!isset($newsaiige_plugins_loaded)) {
    $newsaiige_plugins_loaded = array();
}

/**
 * Chargement sécurisé du système de fidélité UNIQUEMENT
 */
function newsaiige_safe_load_loyalty() {
    global $newsaiige_plugins_loaded;
    
    if (in_array('loyalty', $newsaiige_plugins_loaded)) {
        return;
    }
    
    // Charger le système de fidélité
    if (file_exists(get_template_directory() . '/Fidelity/newsaiige-loyalty-plugin.php')) {
        require_once get_template_directory() . '/Fidelity/newsaiige-loyalty-plugin.php';
        $newsaiige_plugins_loaded[] = 'loyalty';
    }
}

/**
 * DÉSACTIVER temporairement les cartes cadeaux
 */
function newsaiige_safe_load_gift_cards() {
    // DÉSACTIVÉ pour éviter les conflits
    return;
    
    global $newsaiige_plugins_loaded;
    
    if (in_array('gift_cards', $newsaiige_plugins_loaded)) {
        return;
    }
    
    // Charger les cartes cadeaux (DÉSACTIVÉ)
    // if (file_exists(get_template_directory() . '/Carte cadeau/newsaiige-gift-cards.php')) {
    //     require_once get_template_directory() . '/Carte cadeau/newsaiige-gift-cards.php';
    //     $newsaiige_plugins_loaded[] = 'gift_cards';
    // }
}

// Charger les systèmes dans l'ordre et avec priorités
add_action('after_setup_theme', 'newsaiige_safe_load_loyalty', 5);
add_action('after_setup_theme', 'newsaiige_safe_load_gift_cards', 10);

// Hook pour éviter les conflits d'admin
add_action('admin_init', function() {
    // Empêcher les conflits de menu admin
    remove_action('admin_menu', 'newsaiige_gift_cards_admin_menu', 10);
}, 1);
?>