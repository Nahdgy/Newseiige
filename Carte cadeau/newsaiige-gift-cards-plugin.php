<?php
/**
 * Plugin Name: NewSaiige Gift Cards
 * Plugin URI: https://newsaiige.com/gift-cards
 * Description: Système complet de cartes cadeaux pour WordPress avec intégration WooCommerce, paiement automatisé, envoi par email et interface d'administration.
 * Version: 1.0.0
 * Author: NewSaiige
 * Author URI: https://newsaiige.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: newsaiige-gift-cards
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Woo: 8.0.0
 * Network: false
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('NEWSAIIGE_GIFT_CARDS_VERSION', '1.0.0');
define('NEWSAIIGE_GIFT_CARDS_PLUGIN_FILE', __FILE__);
define('NEWSAIIGE_GIFT_CARDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEWSAIIGE_GIFT_CARDS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEWSAIIGE_GIFT_CARDS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Charger le plugin principal
require_once NEWSAIIGE_GIFT_CARDS_PLUGIN_DIR . 'newsaiige-gift-cards.php';

/**
 * Vérifier la compatibilité WooCommerce au moment de l'activation
 */
function newsaiige_gift_cards_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Plugin désactivé</h1>' .
            '<p><strong>NewSaiige Gift Cards</strong> nécessite WooCommerce pour fonctionner.</p>' .
            '<p>Veuillez installer et activer WooCommerce puis réactiver ce plugin.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&larr; Retour aux plugins</a></p>'
        );
    }
}

// Hook d'activation avec vérification WooCommerce
register_activation_hook(__FILE__, function() {
    newsaiige_gift_cards_check_woocommerce();
    newsaiige_gift_cards_activate();
});

// Hook de désactivation
register_deactivation_hook(__FILE__, 'newsaiige_gift_cards_deactivate');

/**
 * Déclarer la compatibilité avec WooCommerce HPOS (High-Performance Order Storage)
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Vérifier la compatibilité avec HPOS et utiliser les bonnes API
 */
function newsaiige_is_hpos_enabled() {
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
    return false;
}

/**
 * Obtenir une commande de manière compatible HPOS
 */
function newsaiige_get_order($order_id) {
    if (function_exists('wc_get_order')) {
        return wc_get_order($order_id);
    }
    return null;
}

/**
 * Ajouter un lien vers les paramètres dans la liste des plugins
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=newsaiige-gift-cards') . '">Paramètres</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Notice d'information après activation
 */
add_action('admin_notices', function() {
    if (get_transient('newsaiige_gift_cards_activated')) {
        delete_transient('newsaiige_gift_cards_activated');
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>NewSaiige Gift Cards activé !</strong></p>
            <p>Le système de cartes cadeaux a été installé avec succès. Vous pouvez maintenant :</p>
            <ul style="margin-left: 20px;">
                <li>• Gérer les cartes via <a href="<?php echo admin_url('admin.php?page=newsaiige-gift-cards'); ?>">Cartes Cadeaux</a></li>
                <li>• Ajouter le formulaire avec le shortcode <code>[newsaiige_gift_cards]</code></li>
                <li>• Ajouter la validation avec <code>[newsaiige_gift_card_validator]</code></li>
                <li>• Consulter la <a href="<?php echo admin_url('admin.php?page=newsaiige-gift-cards&tab=help'); ?>">documentation</a></li>
            </ul>
        </div>
        <?php
    }
});

// Programmer la notice d'activation
register_activation_hook(__FILE__, function() {
    set_transient('newsaiige_gift_cards_activated', true, 30);
});

/**
 * Ajouter les métadonnées du plugin pour WordPress.org
 */
add_action('plugins_loaded', function() {
    // Charger les traductions si disponibles
    load_plugin_textdomain('newsaiige-gift-cards', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/**
 * Vérifier les mises à jour de la base de données
 */
add_action('admin_init', function() {
    $current_version = get_option('newsaiige_gift_cards_version', '0.0.0');
    
    if (version_compare($current_version, NEWSAIIGE_GIFT_CARDS_VERSION, '<')) {
        // Mettre à jour la base de données si nécessaire
        $plugin = newsaiige_gift_cards_init();
        if (method_exists($plugin, 'upgrade_database')) {
            $plugin->upgrade_database($current_version);
        }
        
        // Mettre à jour la version
        update_option('newsaiige_gift_cards_version', NEWSAIIGE_GIFT_CARDS_VERSION);
    }
});

/**
 * Ajouter une page d'aide dans l'admin
 */
add_action('admin_menu', function() {
    // S'assurer que le menu principal existe avant d'ajouter le sous-menu
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'newsaiige-gift-cards',
            'Aide et Documentation',
            'Aide',
            'manage_options',
            'newsaiige-gift-cards-help',
            function() {
                ?>
                <div class="wrap">
                    <h1>NewSaiige Gift Cards - Aide</h1>
                    
                    <div class="card">
                        <h2>Installation et Configuration</h2>
                        <p>Le plugin est maintenant installé et configuré. Voici comment l'utiliser :</p>
                        
                        <h3>1. Créer une page de cartes cadeaux</h3>
                        <p>Ajoutez le shortcode suivant à une page ou un article :</p>
                        <pre><code>[newsaiige_gift_cards title="Nos Cartes Cadeaux" subtitle="Faites plaisir à vos proches"]</code></pre>
                        
                        <h3>2. Créer une page de validation</h3>
                        <p>Pour permettre aux clients de vérifier leurs cartes :</p>
                        <pre><code>[newsaiige_gift_card_validator title="Vérifier ma carte" subtitle="Entrez votre code"]</code></pre>
                        
                        <h3>3. Configuration WooCommerce</h3>
                        <p>Assurez-vous que :</p>
                        <ul>
                            <li>• WooCommerce est configuré avec vos moyens de paiement</li>
                            <li>• Les emails de commande sont activés</li>
                            <li>• Les produits virtuels sont autorisés</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <h2>Fonctionnalités</h2>
                        <ul>
                            <li>✅ Formulaire de commande responsive</li>
                            <li>✅ Intégration complète WooCommerce</li>
                            <li>✅ Génération automatique de codes uniques</li>
                            <li>✅ Envoi automatique par email</li>
                            <li>✅ Interface d'administration complète</li>
                            <li>✅ Validation publique des codes</li>
                            <li>✅ Gestion des expirations</li>
                            <li>✅ Statistiques détaillées</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <h2>Support</h2>
                        <p>Pour obtenir de l'aide :</p>
                        <ul>
                            <li>• Consultez l'onglet "Aide" dans la gestion des cartes</li>
                            <li>• Vérifiez les logs WordPress en cas d'erreur</li>
                            <li>• Contactez le support NewSaiige</li>
                        </ul>
                    </div>
                </div>
                <?php
            }
        );
    }
}, 20); // Priorité plus basse pour s'exécuter après le menu principal

?>