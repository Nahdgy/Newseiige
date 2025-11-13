<?php
/**
 * Plugin Name: NewSaiige Conflict Resolver
 * Description: Résout temporairement les conflits entre systèmes
 * Version: 1.0.0
 * Temporary fix only!
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Désactiver les cartes cadeaux
add_action('wp_loaded', function() {
    // Supprimer les hooks des cartes cadeaux s'ils existent
    remove_action('wp_ajax_process_gift_card', 'newsaiige_process_gift_card');
    remove_action('wp_ajax_nopriv_process_gift_card', 'newsaiige_process_gift_card');
    remove_action('admin_menu', 'newsaiige_gift_cards_admin_menu');
    
    // Charger SEULEMENT le système de fidélité
    $loyalty_file = get_template_directory() . '/Fidelity/newsaiige-loyalty-plugin.php';
    if (file_exists($loyalty_file) && !class_exists('NewsaiigeLoyalty')) {
        require_once $loyalty_file;
    }
}, 1);

// Message d'admin
add_action('admin_notices', function() {
    echo '<div class="notice notice-warning"><p>';
    echo '<strong>NewSaiige:</strong> Système de cartes cadeaux temporairement désactivé pour éviter les conflits.';
    echo '</p></div>';
});
?>